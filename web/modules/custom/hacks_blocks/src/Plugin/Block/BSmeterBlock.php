<?php
namespace Drupal\hacks_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a 'BSmeter' Block.
 *
 * @Block(
 *   id = "bsmeter_block",
 *   admin_label = @Translation("BS Meter Block"),
 *   category = @Translation("Custom")
 * )
 */
class BSmeterBlock extends BlockBase implements ContainerFactoryPluginInterface
{

  protected $routeMatch;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  public function build() {
    $node = $this->routeMatch->getParameter('node');
    $percentage = 0;
    $odds = 1;
    $netDifferential = 0;
    $handicapIndex = 0;
    $grossScore = 0;
    $courseRating = null;
    $courseSlope = null;
    $courseUrl = null;

    if ($node instanceof NodeInterface && $node->getType() === 'men_s_night_score') {
      $grossScore = (float) $node->get('field_total_gross_score')->value;
      if(($grossScore) <= 0) {
        return ['#markup' => ''];
      }
      $handicapIndex = (float) $node->get('field_handicap_index')->value;

      $roundNode = $this->getRelatedRoundNode($node);
      if ($roundNode) {
        $courseNode = $roundNode->get('field_course')->entity;
        if ($courseNode) {
          $courseRating = (float) $courseNode->get('field_grint_course_mr')->value;
          $courseSlope = (float) $courseNode->get('field_grint_course_ms')->value;

          // Handicap Differential calculation
          $handicapDifferential = ($grossScore - $courseRating) * 113 / $courseSlope;

          // Net Differential calculation
          $netDifferential = $handicapDifferential - $handicapIndex;

          // Fixing the net differential within the bounds for the odds calculation
          $fixedNetDifferential = $netDifferential > 0 ? 0 : ($netDifferential < -10 ? -10 : $netDifferential);

          $odds = $this->getOddsFromDifferential($fixedNetDifferential, $handicapIndex);

          // Assuming you want to convert the net differential to a percentage somehow
          $percentage =  $fixedNetDifferential * -15;
          if ($percentage >= 110) {
            $percentage = 110;
          }

          $courseUrl = Url::fromRoute('entity.node.canonical', ['node' => $courseNode->id()])->toString();
        }
      }
    }

    $build = [
      '#theme' => 'bsmeter_block',
      '#percentage' => $percentage,
      '#odds' => $odds,
      '#handicapDifferential' => $netDifferential,
      '#handicapIndex' => $handicapIndex,
      '#grossScore' => $grossScore,
      '#courseRating' => $courseRating,
      '#courseSlope' => $courseSlope,
      '#linktoCourse' => $courseUrl,
      '#attached' => [
        'library' => [
          'hacks_blocks/tachometer',
        ],
      ],
    ];

    return $build;
  }

  private function getOddsFromDifferential($differential, $handicapIndex) {
    // Define the odds table based on the handicap index ranges
    $oddsTable = [
      '0-5' => [5, 10, 23, 57, 151, 379, 790, 2349, 20111, 48219, 125000],
      '6-12' => [5, 10, 22, 51, 121, 276, 536, 1200, 4467, 27877, 84300],
      '13-21' => [6, 10, 21, 43, 87, 174, 323, 552, 1138, 3577, 37000],
      '22-30' => [5, 8, 13, 23, 40, 72, 130, 229, 382, 695, 1650],
      'GREATER THAN 30' => [5, 7, 10, 15, 22, 35, 60, 101, 185, 359, 874]
    ];

    // Determine the range the player's handicap index falls into
    if ($handicapIndex <= 5) {
      $range = '0-5';
    } elseif ($handicapIndex <= 12) {
      $range = '6-12';
    } elseif ($handicapIndex <= 21) {
      $range = '13-21';
    } elseif ($handicapIndex <= 30) {
      $range = '22-30';
    } else {
      $range = 'GREATER THAN 30';
    }

    // Round the differential to the nearest integer and get the odds from the table
    $roundedDifferential = round($differential);
    $index = $roundedDifferential * -1;

    return $oddsTable[$range][$index];
  }
  // Helper function to get the related men_s_night_round node
  private function getRelatedRoundNode($scoreNode) {
    // Example logic to retrieve the related men_s_night_round node
    // You would need to implement the actual query logic based on your Drupal setup
    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'men_s_night_round')
      ->condition('field_scores', $scoreNode->id());
    $nids = $query->execute();

    if (!empty($nids)) {
      $nid = reset($nids);
      return \Drupal\node\Entity\Node::load($nid);
    }
    return NULL;
  }

}
