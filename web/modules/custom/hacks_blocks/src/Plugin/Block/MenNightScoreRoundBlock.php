<?php

namespace Drupal\hacks_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Men Night Score Round' block.
 *
 * @Block(
 *   id = "men_night_score_round_block",
 *   admin_label = @Translation("Men Night Score Round Block"),
 *   category = @Translation("Custom")
 * )
 */
class MenNightScoreRoundBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new MenNightScoreProgressBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $currentNode = $this->routeMatch->getParameter('node');
    if ($currentNode && $currentNode->bundle() === 'men_s_night_score') {
      // Load the men_s_night_round that references the current men_s_night_score
      $round_ids = \Drupal::entityQuery('node')
        ->condition('type', 'men_s_night_round')
        ->condition('field_scores', $currentNode->id())
        ->accessCheck(FALSE)
        ->execute();

      if ($round_ids) {
        $roundNode = \Drupal\node\Entity\Node::load(reset($round_ids));
        $scoreNodeIds = array_column($roundNode->get('field_scores')->getValue(), 'target_id');
        //$scoreNodeIds = array_diff($scoreNodeIds, [$currentNode->id()]);  // Exclude the current node

        $totalPercentage = 0;
        $count = 0;
        foreach ($scoreNodeIds as $scoreNodeId) {
          $scoreNode = \Drupal\node\Entity\Node::load($scoreNodeId);
          if ($scoreNode && $scoreNode->get('field_total_gross_score')->value !== null && $scoreNode->get('field_total_gross_score')->value > 0) {
            $hole_points = $scoreNode->get('field_18_hole_points')->getValue();
            $skins = $scoreNode->get('field_skins')->value;

            // Calculate the total points and percentage for each node
            $total_points = array_sum(array_column($hole_points, 'value'));
            $average_points = 36;
            $max_points = 5 * 18;
            $base_percentage = ($total_points >= $average_points) ?
              80 + (($total_points - $average_points) / ($max_points - $average_points)) * 20 :
              ($total_points / $average_points) * 80;
            $skins_percentage = min(100, $base_percentage + $skins * 2);

            $totalPercentage += $skins_percentage;
            $count++;
          }
        }

        if ($count > 0) {
          $averagePercentage = $totalPercentage / $count;

          // Determine the color based on average percentage
          $red = 255 * (1 - ($averagePercentage / 100));
          $green = 255 * ($averagePercentage / 100);
          $color = sprintf("rgb(%d, %d, 0)", $red, $green);

          return [
            '#theme' => 'progress_bar',
            '#percent' => round($averagePercentage),
            '#label' => $roundNode->getTitle().' Average 💪:',
            '#markup' => '<div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: ' . $averagePercentage . '%" aria-valuenow="' . $averagePercentage . '" aria-valuemin="0" aria-valuemax="100" style="background-color: ' . $color . ';">' . round($averagePercentage) . '%</div>
                                  </div>',
            '#color' => $color, // Pass the color as a variable to the template
          ];
        }
      }
    }

    return ['#markup' => ''];
  }
}
