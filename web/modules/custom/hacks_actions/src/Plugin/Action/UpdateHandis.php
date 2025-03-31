<?php

namespace Drupal\hacks_actions\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides a custom action.
 *
 * @Action(
 *   id = "update_handis",
 *   label = @Translation("Update all Handicaps"),
 * )
 */
class UpdateHandis extends ActionBase {
  protected $grintAPI;
  protected $gcApi;


  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->grintAPI = \Drupal::service('grint_api.grint_api_service');
    $this->gcApi = \Drupal::service('gc_api.golf_canada_api_service');



    $user_ids = \Drupal::entityQuery('user')
      ->condition('status', 1) // Active users
      ->condition('roles', 'hack')
      ->accessCheck(FALSE)
      ->execute();

    $users = User::loadMultiple($user_ids);

    foreach ($users as $user) {
      $grint_ghap = $user->get('field_ghap_id')->value;
      $gc_index = $user->get('field_gc_id')->value;
      $grint_image_url = $this->grintAPI->getGrintProfileImg($grint_ghap);
      $user->set('field_grint_image_url', 'https://profile.static.thegrint.com/'.$grint_image_url);


      $grint_user_id = $user->get('field_grint_userid')->value;
      $handicap_index = $this->grintAPI->getHandicapIndex($grint_user_id);


      \Drupal::logger('gc_api')->notice('Grint handicap for @email is @handicap', [
        '@email' => $user->getEmail(),
        '@handicap' => $handicap_index,
      ]);

      $gc_handicap_index = $this->gcApi->getHandicapIndex($gc_index);
      \Drupal::logger('gc_api')->notice('GC handicap for @email is @handicap', [
        '@email' => $user->getEmail(),
        '@handicap' => $gc_handicap_index,
      ]);


      // Update the handicap index field and save the user account.
      $user->set('field_handicap_index', $handicap_index);
      $user->set('field_gc_handicap_index', $gc_handicap_index);
      $user->save();
    }

    $current_date = new \DateTime('now', new \DateTimeZone('UTC'));
    $date_string = $current_date->format('Y-m-d');

    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'men_s_night_round')
      ->condition('field_round_date', $date_string, '>=');
    $round_nids = $query->execute();

    foreach ($round_nids as $nid) {
      $men_s_night_round = Node::load($nid);

      $course_nid = $men_s_night_round->get('field_course')->target_id;
      $course_node = Node::load($course_nid);

      // Find all 'men_s_night_score' nodes referenced by 'field_scores'
      $score_nids = $men_s_night_round->get('field_scores')->getValue();
      foreach ($score_nids as $score_nid) {
        $score_node = Node::load($score_nid['target_id']);

        // Get the user object from 'field_player'
        $uid = $score_node->get('field_player')->target_id;
        $user = User::load($uid);

        // Get 'field_handicap_index' from the user object
        $handicap_index = $user->get('field_handicap_index')->value;

        // Assuming you have a service for grintAPI with a method getCourseHandicap()
        $course_handicap = $this->grintAPI->getCourseHandicap(
          $user->get('field_grint_userid')->value,
          $handicap_index,
          $this->grintAPI->getCourseIdFromString($course_node->get('field_grint_course')->value),
          $course_node->get('field_grint_tee_color')->value
        );

        // Update 'field_course_handicap' and 'field_handicap_index'
        $score_node->set('field_course_handicap', $course_handicap);
        $score_node->set('field_handicap_index', $handicap_index);
        $score_node->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Check access here. For example, only allow if the user has permission to edit the entity.

    return AccessResult::allowed();

  }

}
