<?php

namespace Drupal\hacks_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
/**
 * Provides a 'ModalFormBlock' block.
 *
 * @Block(
 *   id = "hacks_scorecard_enter_block",
 *   admin_label = @Translation("Hacks Scorecard Enter Block"),
 *   category = @Translation("Custom")
 * )
 */
class HacksScorecardEnterBlock extends BlockBase implements ContainerFactoryPluginInterface {
  protected $routeMatch;

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
    $markup = '';
    $disabled_message = '';

    if ($node && $node instanceof \Drupal\node\NodeInterface) {
      $currentUser = \Drupal::currentUser();
      $currentUserId = $currentUser->id();
      $playerId = $node->get('field_player')->target_id;

      // Check if the current user is the player or has the admin role
      $isAdmin = $currentUser->hasPermission('administer site configuration'); // Or another permission that only admins have
      $isPlayer = $currentUserId == $playerId;

      if ($isPlayer || $isAdmin) {
        $round_ids = \Drupal::entityQuery('node')
          ->condition('type', 'men_s_night_round')
          ->condition('field_scores', $node->id())
          ->accessCheck(FALSE)
          ->execute();

        if (!empty($round_ids)) {
          $round_node = \Drupal\node\Entity\Node::load(reset($round_ids));

          $cutoff_date = new \DateTime($round_node->get('field_score_cutoff')->value);
          $now = new \DateTime();

          // Admins see buttons enabled always, others check the cutoff date
          $is_disabled = !$isAdmin && $now > $cutoff_date;
          $disabled_class = $is_disabled ? 'disabled' : '';

          if ($is_disabled) {
            $disabled_message = '<small>Score entry has been disabled as the cutoff date has passed.</small>';
          }

          $btn_class = ['use-ajax', 'btn', 'btn-secondary', $disabled_class];
          $link1 = Link::createFromRoute(
            $this->t('Enter Scorecard TheGrint'),
            'hacks_forms.scorecard_enter_grint_form',
            [
              'scorecardID' => $node->id(),
              'UID' => $playerId,
              'grintUID' => $node->get('field_player')->entity->get('field_grint_userid')->value,
            ],
            [
              'attributes' => [
                'class' => $btn_class,
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode(['width' => 900]),
              ]
            ]
          )->toString();

          $btn_class = ['btn', 'btn-warning', $disabled_class];
          $link2 = Link::createFromRoute(
            $this->t('Enter Scorecard Manually'),
            'hacks_forms.scorecard_enter_manual_form',
            [
              'scorecardID' => $node->id(),
              'UID' => $playerId,
            ],
            [
              'attributes' => [
                'class' => $btn_class,
              ]
            ]
          )->toString();

          $btn_class = ['use-ajax', 'btn', 'btn-danger', $disabled_class];
          $link3 = Link::createFromRoute(
            $this->t('Enter Scorecard Golf Canada'),
            'hacks_forms.scorecard_enter_gc_form',
            [
              'scorecardID' => $node->id(),
              'UID' => $playerId,
              'GCID' => $node->get('field_player')->entity->get('field_gc_id')->value,

            ],
            [
              'attributes' => [
                'class' => $btn_class,
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode(['width' => 900]),
              ]
            ]
          )->toString();

          $markup = $disabled_message . '<p class="my-3">' . $link1 . '</p><p>' . $link3 . '</p><p>' . $link2 . '</p>';
        }
      }
    }

    return [
      '#markup' => $markup,
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
  }


}
