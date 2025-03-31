<?php

namespace Drupal\hacks_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class HacksScorecardEnterGCForm extends FormBase {

  protected $gcApi;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hacks_scorecard_enter_gc_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $scorecardID = 0, $UID = 0, $GCID = 0) {
    $this->gcApi = \Drupal::service('gc_api.golf_canada_api_service');

    // Get last 20 rounds from Golf Canada
    $rounds = $this->gcApi->getHistory($GCID);

    if (!$rounds || empty($rounds)) {
      $form['message'] = [
        '#markup' => '<p>No rounds found for this Golf Canada user.</p>',
      ];
      return $form;
    }

    $counter = 0;
    foreach ($rounds as $round) {
      $round_id = $round['id'];
      $course = $round['course'] ?? 'Unknown Course';
      $date = $round['date'] ?? '';
      $tee = $round['tee'] ?? '';
      $score = $round['score'] ?? '';
      $hole = $round['holes'] ?? '';
      $formatted_date = '';

      // Format the date (optional)
      if ($date) {
        try {
          $date_obj = new \DateTime($date);
          $formatted_date = $date_obj->format('F j, Y');
        } catch (\Exception $e) {
          $formatted_date = $date;
        }
      }

      $label = "{$hole} | {$score} | {$course} [{$tee}] on {$formatted_date}";

      $form['round'][$round_id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['round-container'],
          'data-radio-id' => $round_id,
        ],
      ];

      $form['round'][$round_id]['select'] = [
        '#type' => 'radio',
        '#return_value' => $round_id,
        '#parents' => ['selected_round'],
        '#attributes' => [
          'class' => ['form-check-input'],
          'id' => $round_id,
        ],
        '#default_value' => ($counter == 0) ? $round_id : NULL,
      ];

      $form['round'][$round_id]['markup'] = [
        '#type' => 'markup',
        '#markup' => '<div class="round-markup">' . $label . '</div>',
      ];

      $counter++;
      if ($counter >= 5) {
        break;
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
      '#attributes' => ['class' => ['btn btn-danger']],
      '#submit' => ['::submitForm'],
    ];

    $form['#attached']['library'][] = 'hacks_forms/radio-click';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $scorecardID = \Drupal::routeMatch()->getParameter('scorecardID');
    $UID = \Drupal::routeMatch()->getParameter('UID');
    $roundId = $form_state->getValue('selected_round');
    \Drupal::logger('hacks_forms')->notice('testy: @id', ['@id' => $roundId]);
    // Redirect to the target manual form
    $form_state->setRedirect(
      'hacks_forms.scorecard_enter_manual_form',
      [
        'scorecardID' => $scorecardID,
        'UID' => $UID,
        'grintRID' => 0, // Still using this key unless you want to rename it
        'GCRID' => $roundId, // Still using this key unless you want to rename it
      ]
    );
  }
}
