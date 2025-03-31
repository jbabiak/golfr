<?php

namespace Drupal\hacks_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hacks_forms\HacksApi;
use Drupal\node\Entity\Node;

class HacksScorecardEnterManualForm extends FormBase {

  protected $hacksAPI;
  protected $grintAPI;
  protected $gcApi;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hacks_scorecard_enter_manual_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $scorecardID = 0, $UID = 0, $grintRID = 0, $GCRID = 0) {

    $this->hacksAPI = new HacksApi();

    if (!empty($grintRID) && is_numeric($grintRID)) {
      $this->grintAPI = \Drupal::service('grint_api.grint_api_service');
      $scores = $this->grintAPI->getRoundScore($grintRID);
      $form = $this->hacksAPI->getScorecardFormElements($scorecardID, 0, $scores);
    }
    elseif (!empty($GCRID) && is_numeric($GCRID)) {
      $this->gcApi = \Drupal::service('gc_api.golf_canada_api_service');
      $scores = $this->gcApi->getRoundScore($GCRID);
      $form = $this->hacksAPI->getScorecardFormElements($scorecardID, 0, $scores);
    }
    else {
      $form = $this->hacksAPI->getScorecardFormElements($scorecardID, 0);
    }


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['#attached'] = [
      'library' => [
        'core/drupal.dialog.ajax',
        'hacks_forms/scorecard-helper',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Extract the node ID from the current path
    $currentPath = \Drupal::request()->getRequestUri();
    $pathArgs = explode('/', $currentPath);
    $nodeId = $pathArgs[3];  // Assuming the node ID is always the 4th segment in the path

    // Load the node to get the full entity
    $node = Node::load($nodeId);
    if ($node) {
      $values = $form_state->getValues();

      // Prepare the scores and putts data
      $scores = array_merge($values['front']['score'], $values['back']['score']);
      //$putts = array_merge($values['front']['putts'], $values['back']['putts']);

      // Format the data for Drupal field
      $formattedScores = array_map(function($item) { return ['value' => $item]; }, $scores);
      //$formattedPutts = array_map(function($item) { return ['value' => $item]; }, $putts);

      // Assign the values to the node fields
      $node->field_18_hole_gross_score = $formattedScores;
      //$node->field_18_hole_putt_score = $formattedPutts;

      // Save the node
      $node->save();

      // After updating the node, redirect to a desired path
      $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nodeId);
      $form_state->setRedirectUrl(\Drupal\Core\Url::fromUri('internal:' . $alias));
    } else {
      // Handle the case where the node does not exist
      drupal_set_message(t('Node with ID @nid does not exist.', ['@nid' => $nodeId]), 'error');
    }
  }
}
