<?php

namespace Drupal\hacks_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ScorecardBlock' block.
 *
 * @Block(
 *   id = "scorecard_block",
 *   admin_label = @Translation("Scorecard Block")
 * )
 */
class ScorecardBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ScorecardBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node && $node->bundle() == 'men_s_night_score') {
      // Fetch the men_s_night_round node that references this score node
      $round_ids = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('type', 'men_s_night_round')
        ->condition('field_scores', $node->id())
        ->execute();

      if ($round_id = reset($round_ids)) {
        $round_node = $this->entityTypeManager->getStorage('node')->load($round_id);

        // Fetch the course node from the round node
        if ($round_node && $course_id = $round_node->get('field_course')->target_id) {
          $course_node = $this->entityTypeManager->getStorage('node')->load($course_id);

          // Now you have $node for scores and $course_node for course details
          // Construct your scorecard here
          $build = [
            '#theme' => 'scorecard',
            '#score_node' => $node,
            '#course_node' => $course_node,
          ];
        }
      }
    }
    $build['#attached']['library'][] = 'hacks_blocks/scorecard';
    return $build;
  }
}
