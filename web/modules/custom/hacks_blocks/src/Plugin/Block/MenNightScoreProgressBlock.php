<?php

namespace Drupal\hacks_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Men Night Score Progress' block.
 *
 * @Block(
 *   id = "men_night_score_progress_block",
 *   admin_label = @Translation("Men Night Score Progress Block"),
 *   category = @Translation("Custom")
 * )
 */
class MenNightScoreProgressBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $node = $this->routeMatch->getParameter('node');
    if ($node && $node->bundle() === 'men_s_night_score') {
      $grossScore = (float) $node->get('field_total_gross_score')->value;
      if(($grossScore) <= 0) {
        return ['#markup' => ''];
      }
      $hole_points = $node->get('field_18_hole_points')->getValue();
      $skins = $node->get('field_skins')->value;

      // Calculate the total points and percentage
      $total_points = array_sum(array_column($hole_points, 'value'));
      $average_points = 36;
      $max_points = 5 * 18;
      $base_percentage = ($total_points >= $average_points) ?
        80 + (($total_points - $average_points) / ($max_points - $average_points)) * 20 :
        ($total_points / $average_points) * 80;
      $base_percentage = max(0, min($base_percentage, 100));
      $skins_percentage = min(100, $base_percentage + $skins * 2);

      // Determine the color based on percentage
      // Transition from red (0%) to green (100%)
      $red = 255 * (1 - ($skins_percentage / 100));
      $green = 255 * ($skins_percentage / 100);
      $color = sprintf("rgb(%d, %d, 0)", $red, $green);
      return [
        '#theme' => 'progress_bar',
        '#percent' => round($skins_percentage),
        '#label' => $node->getTitle().' 💪:',
        '#markup' => '<div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: ' . $skins_percentage . '%" aria-valuenow="' . $skins_percentage . '" aria-valuemin="0" aria-valuemax="100" style="background-color: ' . $color . ';">' . round($skins_percentage) . '%</div>
                          </div>',
        '#color' => $color, // Pass the color as a variable to the template
      ];
    }

    return ['#markup' => t('Not a Men Night Score node.')];
  }
}
