<?php

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\miniorange_oauth_client\MiniorangeOAuthClientCustomer;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\Utilities;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class for handling register/login tab.
 */
class MiniorangeOAuthClientCustomerSetup extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'miniorange_oauth_client_customer_setup';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $current_status = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_status');
    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "miniorange_oauth_client/miniorange_oauth_client.admin",
          "miniorange_oauth_client/miniorange_oauth_client.style_settings",
          "miniorange_oauth_client/miniorange_oauth_client.module",
          "core/drupal.dialog.ajax",
        ],
      ],
    ];

    if ($current_status == 'VALIDATE_OTP') {
      $form['header_top_style_1'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

      $form['markup_top'] = [
        '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_63">',
      ];

      $admin_mail = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email');

      $form['markup_top_vt_start'] = [
        '#type' => 'fieldset',
        '#title' => t('OTP Validation'),
        '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],
        '#markup' => '<br><hr><br>',
      ];

      $form['markup_top_vt_start']['mo_user_profile'] = [
        '#markup' => '<h3>Please enter the OTP sent to <i>' . $admin_mail . '</i>: </h3><br>',
      ];

      $form['markup_top_vt_start']['miniorange_oauth_client_customer_otp_token'] = [
        '#type' => 'textfield',
        '#title' => t('OTP'),
        '#attributes' => ['style' => 'width:30%;', 'placeholder' => 'Enter OTP'],
      ];

      $form['markup_top_vt_start']['mo_btn_brk'] = ['#markup' => '<br><br>'];

      $form['markup_top_vt_start']['miniorange_oauth_client_customer_validate_otp_button'] = [
        '#type' => 'submit',
        '#value' => t('Validate OTP'),
        '#submit' => ['::miniorangeOauthClientValidateOtpSubmit'],
      ];

      $form['markup_top_vt_start']['miniorange_oauth_client_customer_setup_resendotp'] = [
        '#type' => 'submit',
        '#value' => t('Resend OTP'),
        '#submit' => ['::miniorangeOauthClientResendOtp'],
      ];

      $form['markup_top_vt_start']['miniorange_oauth_client_customer_setup_back'] = [
        '#type' => 'submit',
        '#value' => t('Back'),
        '#submit' => ['::miniorangeOauthClientBack'],
        '#suffix' => '<br><br><br><br><br><br><br><br>',
      ];

      Utilities::nofeaturelisted($form, $form_state);
      $form['markup_top_vt_start']['header_top_div_end'] = ['#markup' => '</div>'];
      Utilities::moOauthShowCustomerSupportIcon($form, $form_state);

      return $form;

    }
    elseif ($current_status == 'PLUGIN_CONFIGURATION') {
      $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('miniorange_oauth_client');
      $modules_version = $modules_info['version'];
      $form['markup_top'] = [
        '#markup' => t('<div class="mo_oauth_table_layout_1"><div class="mo_oauth_table_layout mo_oauth_container_63">
                                  <div class="mo_oauth_client_welcome_message">Thank you for registering with miniOrange</div>'),
      ];

      $form['mo_oauth_profile_details_tab'] = [
        '#type' => 'fieldset',
        '#title' => t('Profile Details:'),
        '#attributes' => ['style' => 'padding:2% 2% 6%; margin-bottom:2%'],
      ];

      $header = [t('Attribute'), t('Value')];
      $options = [
            ['Customer Email', \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email')],
            ['Customer ID', \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_id')],
            ['Drupal Version', \DRUPAL::VERSION],
            ['PHP Version', phpversion()],
            ['Module Version', $modules_version],
      ];

      $form['mo_oauth_profile_details_tab']['fieldset']['customerinfo'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $options,
        '#prefix' => '<br>',
        '#attributes' => ['style' => 'margin:1% 0% 7%;'],
      ];

      $form['mo_oauth_profile_details_tab']['miniorange_oauth_customer_Remove_Account_info'] = [
        '#markup' => t('<br/><h4>Remove Account:</h4><p>This section will help you to remove your current
                        logged in account without losing your current configurations.</p>'),
      ];

      $form['mo_oauth_profile_details_tab']['miniorange_oauth_customer_Remove_Account'] = [
        '#type' => 'link',
        '#title' => $this->t('Remove Account'),
        '#url' => Url::fromRoute('miniorange_oauth_client.removeAccount'),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
          ],
        ],
        '#suffix' => '</div>',
      ];

      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      Utilities::nofeaturelisted($form, $form_state);
      $form['mo_markup_div_end2'] = ['#markup' => '</div>'];
      Utilities::moOauthShowCustomerSupportIcon($form, $form_state);
      return $form;

    }
    elseif ($current_status == 'already_registered') {
      $form['header_top_style_1'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

      $form['markup_top'] = [
        '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_63 login_form"><h2>Login with mini<span class="orange">O</span><span>range</h2><hr>',
      ];

      $form['mo_oauth_client_customer_email'] = [
        '#type' => 'textfield',
        '#title' => t('Email'),
        '#required' => TRUE,
        '#attributes' => [
          'style' => 'width:50%',
        ],
      ];

      $form['mo_oauth_client_customer_password'] = [
        '#type' => 'password',
        '#title' => t('Password'),
        '#required' => TRUE,
        '#attributes' => [
          'style' => 'width:50%',
        ],
      ];

      $form['login_submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Login'),
      ];

      $form['back_button'] = [
        '#type' => 'submit',
        '#submit' => ['::backToRegisterTab'],
        '#value' => t('Create an account?'),
        '#suffix' => '</div>',
      ];

      Utilities::nofeaturelisted($form, $form_state);
      $form['mo_markup_div_end2'] = ['#markup' => '</div>'];
      Utilities::moOauthShowCustomerSupportIcon($form, $form_state);

      return $form;
    }
    $form['header_top_style_1'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

    $form['markup_top'] = [
      '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_63 login_form"><h2>Register with mini<span class="orange">O</span><span>range</h2><hr>',
    ];

    $form['mo_oauth_registration_tab'] = [
      '#type' => 'fieldset',
      '#title' => t('Why should I register?'),
      '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],
    ];

    $form['mo_oauth_registration_tab']['markup_msg_1'] = [
      '#markup' => t('<br><div class="mo_oauth_highlight_background_note">You should register so that in case you need help, we can help you with step-by-step instructions.
                <b>You will also need a miniOrange account to upgrade to the premium version of the module.</b>
                We do not store any information except the email that you will use to register with us. Please enter a valid email ID that you have access to. We will send OTP to this email for verification.</div><br>'),
    ];
    $form['mo_oauth_registration_tab']['mo_register'] = [
      '#markup' => t('<div class="mo_oauth_highlight_background_note" style="width: auto">If you face any issues during registration then you can <b><a href="https://www.miniorange.com/businessfreetrial" target="_blank">click here</a></b> to register and use the same credentials below to login into the module.</div><br><div id="Register_Section">'),
    ];

    $form['mo_oauth_registration_tab']['miniorange_oauth_client_customer_setup_username'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#attributes' => ['style' => 'width:50%;', 'placeholder' => 'Enter your email'],
      '#required' => TRUE,
    ];

    $form['mo_oauth_registration_tab']['miniorange_oauth_client_customer_setup_password'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
    ];

    $form['mo_oauth_registration_tab']['miniorange_oauth_client_customer_setup_button'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Register'),
      '#attributes' => ['style' => 'float:left;'],
      '#prefix' => '<br><span>',
    ];

    $form['mo_oauth_registration_tab']['miniorange_oauth_client_customer_setup_alredy_registered_button'] = [
      '#type' => 'submit',
      '#value' => t('Already have an account?'),
      '#submit' => ['::alreadyRegistred'],
      '#limit_validation_errors' => [],
      '#suffix' => '</span>',
    ];

    $form['mo_oauth_registration_tab']['markup_divEnd'] = [
      '#markup' => '</div></div>',
    ];

    Utilities::nofeaturelisted($form, $form_state);
    $form['mo_markup_div_end2'] = ['#markup' => '</div>'];
    Utilities::moOauthShowCustomerSupportIcon($form, $form_state);

    return $form;
  }

  /**
   * Submit handler for register/login.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_status = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_status');
    if ($current_status == 'already_registered') {
      $username = trim($form['mo_oauth_client_customer_email']['#value']);
      $password = trim($form['mo_oauth_client_customer_password']['#value']);
    }
    else {
      $username = trim($form['mo_oauth_registration_tab']['miniorange_oauth_client_customer_setup_username']['#value']);
      $password = trim($form['mo_oauth_registration_tab']['miniorange_oauth_client_customer_setup_password']['#value']['pass1']);
    }

    if (empty($username) || empty($password)) {
      \Drupal::messenger()->addMessage(t('The <b><u>Email </u></b> and <b><u>Password</u></b> fields are mandatory.'), 'error');
      return;
    }
    if (!\Drupal::service('email.validator')->isValid($username)) {
      \Drupal::messenger()->addMessage(t('The email address <i>' . $username . '</i> is not valid.'), 'error');
      return;
    }
    $customer_config = new MiniorangeOAuthClientCustomer($username, NULL, $password, NULL);
    $check_customer_response = json_decode($customer_config->checkCustomer());

    if (is_object($check_customer_response) && $check_customer_response->status == 'CUSTOMER_NOT_FOUND') {
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_email', $username)->save();
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_password', $password)->save();
      if ($current_status == 'already_registered') {
        \Drupal::messenger()->addMessage(t('Account with username @username is not registered with miniOrange, Please Register with miniOrange to login', [
          '@username' => $username,
        ]), 'error');
        return;
      }
      $send_otp_response = json_decode($customer_config->sendOtp());

      if (is_object($send_otp_response) && $send_otp_response->status == 'SUCCESS') {
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_tx_id', $send_otp_response->txId)->save();
        $current_status = 'VALIDATE_OTP';
        \Drupal::messenger()->addMessage(t('Verify email address by entering the passcode sent to @username', [
          '@username' => $username,
        ]));
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
      }
      elseif (is_object($send_otp_response) && $send_otp_response->status == 'FAILED') {
        \Drupal::messenger()->addMessage(t('An error has been occured. Please try after some time or contact us at <a href="mailto:drupalsupport@xecurify.com" target="_blank">drupalsupport@xecurify.com</a>.'), 'error');
      }
    }
    elseif (is_object($check_customer_response) && $check_customer_response->status == 'CURL_ERROR') {
      \Drupal::messenger()->addMessage(t('cURL is not enabled. Please enable cURL'), 'error');
    }
    else {
      $content = $customer_config->getCustomerKeys();
      if (isset($content)) {
        $customer_keys_response = json_decode($content);
      }
      else {
        $customer_keys_response = NULL;
      }

      if (json_last_error() == JSON_ERROR_NONE && isset($customer_keys_response)) {
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_id', $customer_keys_response->id)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_token', $customer_keys_response->token)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_email', $username)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_api_key', $customer_keys_response->apiKey)->save();
        $current_status = 'PLUGIN_CONFIGURATION';
        $customerid = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_id');

        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
        \Drupal::messenger()->addMessage(t('Successfully retrieved your account.'));
        $register_to_upgrade = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_redi_upgrade');
        if ($register_to_upgrade == 1) {
          \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_redi_upgrade', '0')->save();
          global $base_url;
          $response = new RedirectResponse($base_url . "/admin/config/people/miniorange_oauth_client/licensing");
          $response->send();
        }
      }
      elseif (is_object($check_customer_response) && $check_customer_response->status == 'TRANSACTION_LIMIT_EXCEEDED') {
        \Drupal::messenger()->addMessage(t('An error has occurred. Please try after some time or contact us at <a href="mailto:drupalsupport@xecurify.com" target="_blank">drupalsupport@xecurify.com</a>.'), 'error');
      }
      else {
        \Drupal::messenger()->addMessage(t('Invalid credentials'), 'error');
      }
    }
  }

  /**
   * Changes status of register/login flow.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function miniorangeOauthClientBack(&$form, $form_state) {
    $current_status = 'CUSTOMER_SETUP';
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_miniorange_oauth_client_customer_admin_email')->save();
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_tx_id')->save();
    \Drupal::messenger()->addMessage(t('Register/Login with your miniOrange Account'), 'status');
  }

  /**
   * Resends otp to user on request while registration.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function miniorangeOauthClientResendOtp(&$form, $form_state) {
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_tx_id')->save();
    $username = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email');
    $customer_config = new MiniorangeOAuthClientCustomer($username, NULL, NULL, NULL);
    $send_otp_response = json_decode($customer_config->sendOtp());
    if (is_object($send_otp_response) && $send_otp_response->status == 'SUCCESS') {
      // Store txID.
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_tx_id', $send_otp_response->txId)->save();
      $current_status = 'VALIDATE_OTP';
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
      \Drupal::messenger()->addMessage(t('Verify email address by entering the passcode resent to @username', ['@username' => $username]));
    }
  }

  /**
   * Validates otp entered while registration.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function miniorangeOauthClientValidateOtpSubmit(&$form, $form_state) {
    $otp_token = trim($form['markup_top_vt_start']['miniorange_oauth_client_customer_otp_token']['#value']);
    if ($otp_token == NULL) {
      \Drupal::messenger()->addMessage(t('Please enter OTP first.'), 'error');
      return;
    }
    $username = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email');
    $tx_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_tx_id');
    $customer_config = new MiniorangeOAuthClientCustomer($username, $phone, NULL, $otp_token);
    $validate_otp_response = json_decode($customer_config->validateOtp($tx_id));
    if (is_object($validate_otp_response) && $validate_otp_response->status == 'SUCCESS') {
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_tx_id')->save();
      $password = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_password');
      $customer_config = new MiniorangeOAuthClientCustomer($username, $phone, $password, NULL);
      $create_customer_response = json_decode($customer_config->createCustomer());
      if (is_object($create_customer_response) && $create_customer_response->status == 'SUCCESS') {
        $current_status = 'PLUGIN_CONFIGURATION';
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_email', $username)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_token', $create_customer_response->token)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_id', $create_customer_response->id)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_api_key', $create_customer_response->apiKey)->save();
        \Drupal::messenger()->addMessage(t('Account created successfully.'));
      }
      elseif (is_object($create_customer_response) && trim($create_customer_response->message) == 'Email is not enterprise email.' || ($create_customer_response->status) == "INVALID_EMAIL_QUICK_EMAIL") {
        \Drupal::messenger()->addMessage(t('There was an error creating an account for you. You may have entered an invalid Email-Id
                        <strong>(We discourage the use of disposable emails) </strong>
                        <br>Please try again with a valid email.'), 'error');
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', 'CUSTOMER_SETUP')->save();
      }
      else {
        \Drupal::messenger()->addMessage(t('Error in creating an account for you. Please try again.'), 'error');
      }
    }
    else {
      \Drupal::messenger()->addMessage(t('Invalid OTP provided. Please enter the correct OTP'), 'error');
    }
  }

  /**
   * Sets register status as alredy registered.
   */
  public function alreadyRegistred() {
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', 'already_registered')->save();
  }

  /**
   * Sets register status as empty.
   */
  public function backToRegisterTab() {
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', '')->save();
  }

}
