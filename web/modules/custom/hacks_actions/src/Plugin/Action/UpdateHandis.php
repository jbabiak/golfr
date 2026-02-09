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


      $grint_image_url = '';
      if (!empty($grint_ghap)) {
        $grint_image_url = $this->grintAPI->getGrintProfileImg($grint_ghap);
      }

      // Set default if GHAP is empty or image URL is not returned
      if (empty($grint_ghap) || empty($grint_image_url)) {
        $user->set('field_grint_image_url', 'https://profile.static.thegrint.com/profile_default.jpg');
      } else {
        $user->set('field_grint_image_url', 'https://profile.static.thegrint.com/' . $grint_image_url);
      }

      $grint_user_id = $user->get('field_grint_userid')->value;
      if (!empty($grint_user_id)) {
        $handicap_index = $this->grintAPI->getHandicapIndex($grint_user_id);
      } else {
        $handicap_index = NULL;
      }

      $gc_index = $user->get('field_gc_id')->value;
      if (!empty($gc_index)) {
        $gc_handicap_index = $this->gcApi->getHandicapIndex($gc_index);
      } else {
        $gc_handicap_index = NULL;
      }


//      \Drupal::logger('gc_api')->notice('Grint handicap for @email is @handicap', [
//        '@email' => $user->getEmail(),
//        '@handicap' => $handicap_index,
//      ]);

//      \Drupal::logger('gc_api')->notice('GC handicap for @email is @handicap', [
//        '@email' => $user->getEmail(),
//        '@handicap' => $gc_handicap_index,
//      ]);


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
        $handicap_system_value = $user->get('field_handicap_system')->value;

        if ($handicap_system_value === '1') {
          //GRINT
          $handicap_index = $user->get('field_handicap_index')->value;

//          $course_handicap = $this->grintAPI->getCourseHandicap(
//            $user->get('field_grint_userid')->value,
//            $handicap_index,
//            $this->grintAPI->getCourseIdFromString($course_node->get('field_grint_course')->value),
//            $course_node->get('field_grint_tee_color')->value
//          );

          $grint_course_ms = $course_node->get('field_grint_course_ms')->value;
          $grint_course_rating = $course_node->get('field_grint_course_mr')->value;
          $grint_course_par = $course_node->get('field_course_par_total')->value;
          //$course_handicap = $this->grintAPI->getCourseHandicapManual($handicap_index, $grint_course_ms);
          $course_handicap = $this->grintAPI->getCourseHandicapWHS($handicap_index, $grint_course_ms, $grint_course_rating, $grint_course_par);
          $course_handicap = $course_handicap;

          if ((float) $course_handicap == 0) {
            $course_handicap = 0;
          }
//          \Drupal::logger('grint_api')->notice('Grint Test @email is @handicap', [
//            '@email' => $user->getEmail(),
//            '@handicap' => $course_handicap,
//          ]);
          // Update 'field_course_handicap' and 'field_handicap_index'
          $score_node->set('field_course_handicap', $course_handicap);
          $score_node->set('field_handicap_index', $handicap_index);
          $score_node->save();
        }
        elseif ($handicap_system_value === '2') {
          //GOLF CANADA
          $handicap_index = $user->get('field_gc_handicap_index')->value;

          //Adjust GC Handicap:
          if ($handicap_index < 5) {
            $handicap_index -= 0.6;
          } elseif ($handicap_index < 10) {
            $handicap_index -= 1;
          } else {
            $handicap_index -= 2;
          }

          $handicap_index = round((float) $handicap_index);

          if ((float) $handicap_index == 0) {
            $handicap_index = 0;
          }
          //End Adjust

//          $course_handicap = $this->gcApi->getCourseHandicap(
//            $user->get('field_gc_id')->value,
//            23210,//pine view golf course
//            23282,//executive course
//            $course_node->get('field_grint_tee_color')->value
//          );
          $grint_course_ms = $course_node->get('field_grint_course_ms')->value;
          $grint_course_rating = $course_node->get('field_grint_course_mr')->value;
          $grint_course_par = $course_node->get('field_course_par_total')->value;
          //$course_handicap = $this->grintAPI->getCourseHandicapManual($handicap_index, $grint_course_ms);
          $course_handicap = $this->grintAPI->getCourseHandicapWHS($handicap_index, $grint_course_ms, $grint_course_rating, $grint_course_par);
          $course_handicap = $course_handicap;



          \Drupal::logger('gc_api')->notice('Golf Canada Test @email is @handicap', [
            '@email' => $user->getEmail(),
            '@handicap' => $course_handicap,
          ]);
          // Update 'field_course_handicap' and 'field_handicap_index'
          $score_node->set('field_course_handicap', $course_handicap);
          $score_node->set('field_handicap_index', $handicap_index);
          $score_node->save();
        }
        else {
          // Optional fallback case
          \Drupal::logger('update_handis')->notice('Updating handicaps on score nodes did not work');
        }
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
