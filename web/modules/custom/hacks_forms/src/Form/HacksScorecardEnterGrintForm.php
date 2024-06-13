<?php

namespace Drupal\hacks_forms\Form;
use DOMDocument;
use DOMXPath;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class HacksScorecardEnterGrintForm extends FormBase {

  protected $grintAPI;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hacks_scorecard_enter_grint_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $scorecardID = 0, $UID = 0, $grintUID = 0) {
    $this->grintAPI = \Drupal::service('grint_api.grint_api_service');
    $rounds = $this->extractRounds($this->grintAPI->getRoundFeed($grintUID));
    // Add your form elements here
    $counter = 0;
    foreach ($rounds as $round) {
      // Create a container for each round
      $form['round'][$round['numberPost']] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['round-container'],
          'data-radio-id' => $round['numberPost'], // Add a data attribute to store the radio button ID
        ],
      ];

      // Add a radio button for the round
      $form['round'][$round['numberPost']]['select'] = [
        '#type' => 'radio',
        '#return_value' => $round['numberPost'],
        '#parents' => ['selected_round'],
        '#attributes' => [
          'class' => ['form-check-input'],
          'id' => $round['numberPost'], // Use the unique ID here
        ],
        '#default_value' => ($counter == 0) ? $round['numberPost'] : NULL,
      ];
      // Add markup for the round
      $form['round'][$round['numberPost']]['markup'] = [
        '#type' => 'markup',
        '#markup' => '<div class="round-markup">' . $round['linkText'] . ' on ' . $round['dateText'].'</div>',
      ];


      $counter++;
      // Check if the counter has reached 5
      if ($counter >= 5) {
        break; // Exit the loop
      }
    }

    // Add a single submit button for the form
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit Selected Round'),
      '#attributes' => ['class' => ['form-submit-button']],
      // The submit handler will process the selected round
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
    // Redirect to the target form with the existing parameters
    $form_state->setRedirect(
      'hacks_forms.scorecard_enter_manual_form',
      [
        'scorecardID' => $scorecardID,
        'UID' => $UID,
        'grintRID' => $roundId
      ]
    );
  }



  public function extractRounds($html) {
    $rounds = [];

    // Create a new DOMDocument and load the HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress parse errors and warnings
    $dom->loadHTML($html);
    libxml_clear_errors();

    // Create a new DOMXPath object
    $xpath = new DOMXPath($dom);

// Find all div elements with the 'newsfeed-container' class and 'number-post' attribute
    $divNodes = $xpath->query("//div[contains(@class, 'newsfeed-container')][@number-post]");
    $extractedData = [];

    foreach ($divNodes as $divNode) {
      // Extract the 'number-post' attribute
      $numberPost = $divNode->getAttribute('number-post');

      // Extract the text from 'newsfeed-link-message'
      $linkNode = $xpath->query(".//a[contains(@class, 'newsfeed-link-message')]", $divNode)->item(0);
      $linkText = $linkNode ? $linkNode->nodeValue : null;
      $linkTextRemoved = substr(trim($linkText), 9);
      $linkTextRemoved = $linkTextRemoved;
      // Extract the text from 'newsfeed-date'
      $dateNode = $xpath->query(".//span[contains(@class, 'newsfeed-date')]", $divNode)->item(0);
      $dateText = $dateNode ? $dateNode->nodeValue : null;

      // Store the extracted data
      if ($numberPost > 0) {
        $rounds[] = [
          'numberPost' => $numberPost,
          'linkText' => ucfirst($linkTextRemoved),
          'dateText' => $dateText
        ];
      }
    }
    return $rounds;
  }
}
