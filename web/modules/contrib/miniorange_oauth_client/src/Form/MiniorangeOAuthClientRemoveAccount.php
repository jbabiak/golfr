<?php

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class for handling remove account from the module.
 */
class MiniorangeOAuthClientRemoveAccount extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'miniorange_oauth_remove_license';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['miniorange_oauth_markup_library'] = [
      '#attached' => [
        'library' => [
          "miniorange_oauth_client/miniorange_oauth_client.admin",
        ],
      ],
    ];
    $form['#prefix'] = '<div id="modal_example_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['miniorange_oauth_content'] = [
      '#markup' => t('<strong>Are you sure you want to remove account? The configurations saved will not be lost.</strong>'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Confirm'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitRemoveAccountForm'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * Submit handler for removing account from module on confirmation.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function submitRemoveAccountForm(array $form, FormStateInterface $form_state) {
    $editConfig = \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings');
    
    $response   = new AjaxResponse();
    // If there are any form errors, AJAX replace the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_example_form', $form));
    }
    else {
      $editConfig->clear('miniorange_oauth_client_customer_admin_email')
        ->clear('miniorange_oauth_client_customer_api_key')
        ->clear('miniorange_oauth_client_customer_admin_token')
        ->clear('miniorange_oauth_client_customer_id')
        ->set('miniorange_oauth_client_status', 'CUSTOMER_SETUP')
        ->save();

      \Drupal::messenger()->addMessage(t('Your Account Has Been Removed Successfully!'), 'status');

      $response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_oauth_client.customer_setup')->toString()));
    }
    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
