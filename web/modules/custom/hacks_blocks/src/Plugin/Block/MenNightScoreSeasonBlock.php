<?php

namespace Drupal\hacks_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Men Night Score Season' block.
 *
 * @Block(
 *   id = "men_night_score_season_block",
 *   admin_label = @Translation("Men Night Score Season Block"),
 *   category = @Translation("Custom")
 * )
 */
class MenNightScoreSeasonBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      // Step 1: Get the associated round ID from the current score node
      $round_ids = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('type', 'men_s_night_round')
        ->condition('field_scores', $currentNode->id())  // 'field_scores' is the field in the round node that references score nodes
        ->execute();

      $round_id = !empty($round_ids) ? reset($round_ids) : NULL;
      $roundNode = \Drupal\node\Entity\Node::load($round_id);

        if ($roundNode) {
          // Step 2: Get the associated men_s_night (season) node
          $season_ids = \Drupal::entityQuery('node')
            ->accessCheck(FALSE)
            ->condition('type', 'men_s_night')
            ->condition('field_weekly_rounds', $roundNode->id())
            ->execute();
          $seasonNode = \Drupal\node\Entity\Node::load(reset($season_ids));

          if ($seasonNode) {
            // Step 3: Collect all rounds in the season
            $roundNodeIds = array_column($seasonNode->get('field_weekly_rounds')->getValue(), 'target_id');

            $totalPercentage = 0;
            $count = 0;

            foreach ($roundNodeIds as $roundNodeId) {
              $roundNode = \Drupal\node\Entity\Node::load($roundNodeId);
              $scoreNodeIds = array_column($roundNode->get('field_scores')->getValue(), 'target_id');

              foreach ($scoreNodeIds as $scoreNodeId) {
                $scoreNode = \Drupal\node\Entity\Node::load($scoreNodeId);

                // Step 4: Process scores with non-empty, non-zero field_total_gross_score
                if ($scoreNode && $scoreNode->get('field_total_gross_score')->value !== null && $scoreNode->get('field_total_gross_score')->value > 0) {
                  $hole_points = $scoreNode->get('field_18_hole_points')->getValue();
                  $skins = $scoreNode->get('field_skins')->value;

                  // Calculate the total points and percentage for each node
                  $total_points = array_sum(array_column($hole_points, 'value'));
                  $base_percentage = $this->calculatePercentage($total_points, $skins);

                  $totalPercentage += $base_percentage;
                  $count++;
                }
              }
            }

            if ($count > 0) {
              $averagePercentage = $totalPercentage / $count;

              // Determine the color based on average percentage
              $color = $this->calculateColor($averagePercentage);

              return [
                '#theme' => 'progress_bar',
                '#percent' => round($averagePercentage),
                '#label' => 'Season Average 💪:',
                '#markup' => '<div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: ' . $averagePercentage . '%" aria-valuenow="' . $averagePercentage . '" aria-valuemin="0" aria-valuemax="100" style="background-color: ' . $color . ';">' . round($averagePercentage) . '%</div>
                                      </div>',
                '#color' => $color, // Pass the color as a variable to the template
              ];
            }
          }
        }
    }

    return ['#markup' => ''];
  }

  private function calculatePercentage($total_points, $skins) {
    $average_points = 36;
    $max_points = 5 * 18;
    $base_percentage = ($total_points >= $average_points) ?
      80 + (($total_points - $average_points) / ($max_points - $average_points)) * 20 :
      ($total_points / $average_points) * 80;
    return min(100, $base_percentage + $skins * 2);
  }

  private function calculateColor($percentage) {
    $red = 255 * (1 - ($percentage / 100));
    $green = 255 * ($percentage / 100);
    return sprintf("rgb(%d, %d, 0)", $red, $green);
  }
}
