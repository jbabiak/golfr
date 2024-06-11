<?php

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\miniorange_oauth_client\MiniorangeOAuthClientSupport;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\miniorange_oauth_client\Utilities;

/**
 * Class for handling customer support queries.
 */
class MoOAuthCustomerRequest extends FormBase {

  /**
   * The immmutable config property.
   *
   * @var Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * The messenger property.
   *
   * @var object
   */
  protected $messenger;

  /**
   * Constructs a new MoOAuthCustomerRequest object.
   */
  public function __construct() {
    $this->config = \Drupal::config('miniorange_oauth_client.settings');
    $this->messenger = \Drupal::messenger();
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'mo_client_request_customer_support';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="modal_support_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $user_email = $this->config->get('miniorange_oauth_client_customer_admin_email');
    $form['mo_oauth_client_customer_support_email_address'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#default_value' => $user_email,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => t('Enter valid email'), 'style' => 'width:99%;margin-bottom:1%;'],
    ];

    $form['mo_oauth_client_customer_support_method'] = [
      '#type' => 'select',
      '#title' => t('What are you looking for'),
      '#attributes' => ['style' => 'width:99%;height:30px;margin-bottom:1%;'],
      '#options' => [
        'I need Technical Support' => t('I need Technical Support'),
        'I want to Schedule a Setup Call/Demo' => t('I want to Schedule a Setup Call/Demo'),
        'I have Sales enquiry' => t('I have Sales enquiry'),
        'I have a custom requirement' => t('I have a custom requirement'),
        'My reason is not listed here' => t('My reason is not listed here'),
      ],
    ];

    $timezone = [];

    foreach (Utilities::$zones as $key => $value) {
      $timezone[$value] = $key;
    }

    $form['date_and_time'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name = "mo_oauth_client_customer_support_method"]' => ['value' => 'I want to Schedule a Setup Call/Demo'],
        ],
      ],
    ];

    $form['date_and_time']['miniorange_oauth_client_timezone'] = [
      '#type' => 'select',
      '#title' => t('Select Timezone'),
      '#options' => $timezone,
      '#default_value' => 'Etc/GMT',
    ];

    $form['date_and_time']['miniorange_oauth_client_meeting_time'] = [
      '#type' => 'datetime',
      '#title' => 'Date and Time',
      '#format' => '',
      '#default_value' => DrupalDateTime::createFromTimestamp(time()),
    ];

    $form['mo_oauth_client_customer_support_query'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => t('How can we help you?'),
      '#attributes' => ['placeholder' => t('Describe your query here!'), 'style' => 'width:99%'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button--primary',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * Submit handler for sending support query.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $response = new AjaxResponse();
    // If there are any form errors, AJAX replace the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
    }
    else {
      $email = $form_values['mo_oauth_client_customer_support_email_address'];
      $support_for = $form_values['mo_oauth_client_customer_support_method'];
      $query = $form_values['mo_oauth_client_customer_support_query'];
      $query_type = 'Contact Support';
      if ($support_for == 'I want to Schedule a Setup Call/Demo') {
        $timezone = $form_values['miniorange_oauth_client_timezone'];
        $mo_date = $form['date_and_time']['miniorange_oauth_client_meeting_time']['#value']['date'];
        $mo_time = $form['date_and_time']['miniorange_oauth_client_meeting_time']['#value']['time'];
        $query_type = 'Call Request';
      }

      $timezone = !empty($timezone) ? $timezone : NULL;
      $mo_date  = !empty($mo_date) ? $mo_date : NULL;
      $mo_time = !empty($mo_time) ? $mo_time : NULL;

      $support = new MiniorangeOAuthClientSupport($email, '', $query, $query_type, $timezone, $mo_date, $mo_time, $support_for);
      $support_response = $support->sendSupportQuery();
      if ($support_response) {
        \Drupal::messenger()->addStatus(t('Support query successfully sent. We will get back to you shortly.'));
      }
      else {
        \Drupal::messenger()->addError(t('An error has occurred. Please try again to submit your Query, or you can also reach out to <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>.'));
      }
      $response->addCommand(new RedirectCommand(Url::fromRoute('miniorange_oauth_client.config_clc')->toString()));
    }
    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

}
