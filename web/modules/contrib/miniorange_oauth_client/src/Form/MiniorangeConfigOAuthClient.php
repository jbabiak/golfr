<?php

/**
 * @file
 * Contains \Drupal\miniorange_oauth_client\Form\MiniorangeConfigOAuthClient.
 */

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\MiniorangeOAuthClientConstants;
use Drupal\miniorange_oauth_client\Utilities;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class for handling OAuth Client configurations.
 */
class MiniorangeConfigOAuthClient extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'miniorange_oauth_client_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_disabled', FALSE)->save();
    $baseUrl = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
    $baseUrlValue = empty($baseUrl) ? Utilities::getOauthBaseURL($base_url) : $baseUrl;
    $attachments['#attached']['library'][] = 'miniorange_oauth_client/miniorange_oauth_client.admin';
    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "miniorange_oauth_client/miniorange_oauth_client.oauth_config",
          "miniorange_oauth_client/miniorange_oauth_client.admin",
          "miniorange_oauth_client/miniorange_oauth_client.testconfig",
          "miniorange_oauth_client/miniorange_oauth_client.returnAttribute",
          "miniorange_oauth_client/miniorange_oauth_client.style_settings",
          "miniorange_oauth_client/miniorange_oauth_client.Vtour",
          "miniorange_oauth_client/miniorange_oauth_client.mo_tooltip",
          "core/drupal.dialog.ajax",
        ],
      ],
    ];

    $app_name = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
    $client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
    if (!empty($app_name) && !empty($client_id)) {
      $disabled = TRUE;
    }

    $disableButton = NULL;
    if (empty($app_name)  || empty($client_id)) {
      $disableButton = 'disabled';
    }

    $app_name_selected = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
    $client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
    $callback_uri = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_callback_uri');

    if(empty($callback_uri)){
      $request = \Drupal::request();
      $base_Url = $request->getSchemeAndHttpHost().$request->getBaseUrl();
      $callbackUrl = $base_Url."/mo_login";
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_callback_uri', $callbackUrl)->save();
    }

    if(isset($_GET['action']) && $_GET['action'] == 'update' ){

      if(!empty($client_app) && !empty($client_id)){
        $disabled = TRUE;
      }
      else{
        $disabled = FALSE;
      }

      $form['mo_oauth_top_div'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

      $form['mo_oauth_inside_div'] = [
        '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container">',
      ];

      $form['miniorange_oauth_client_summary'] = [
          '#type' => 'table',
          '#responsive' => TRUE ,
          '#caption' => $this->t('<h3>APPLICATION DETAILS</h3><hr>'),
          '#attributes' => ['style' => 'border-collapse: separate;','class' => ['configtable'],],
      ];

      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

      $configurations = self::getOauthConfigurations(0);

      foreach ($configurations as $key => $value) {
        $row = self::generateMoOauthConfigurationTableRow($key, $value);
        $form['miniorange_oauth_client_summary'][$key] = $row;
      }

          //table2
          $form['miniorange_oauth_client_summary_1'] = [
              '#type' => 'table',
              '#responsive' => TRUE ,
              '#attributes' => ['style' => 'border-collapse: separate;width: 126%;','class' => ['configtable','configtable2'],],
              '#prefix' => '<div id="tour_client_id_secret_table">',
              '#suffix' => '</div>',
          ];
          $configurations = self::getOauthConfigurations(1);
          foreach($configurations as $key => $value){
            $row = self::generateMoOauthConfigurationTableRow($key, $value);
            $form['miniorange_oauth_client_summary_1'][$key] = $row;
          }

          //table3
          $form['miniorange_oauth_client_summary_2'] = [
              '#type' => 'table',
              '#responsive' => TRUE ,
              '#attributes' => ['style' => 'border-collapse: separate;width: 104%;','class' => ['configtable','configtable3'],],
              '#prefix' => '<div id="tour_server_endpoints_table">',
              '#suffix' => '</div>',
          ];
          $configurations = self::getOauthConfigurations(2);
          foreach($configurations as $key => $value){
            $row = self::generateMoOauthConfigurationTableRow($key, $value);
            $form['miniorange_oauth_client_summary_2'][$key] = $row;
          }

          //table4
          $form['miniorange_oauth_client_summary_3'] = [
              '#type' => 'table',
              '#responsive' => TRUE ,
              '#attributes' => ['style' => 'border-collapse: separate;width:105%','class' => ['configtable'],],
          ];
          $configurations = self::getOauthConfigurations(3);
          foreach($configurations as $key => $value){
            $row = self::generateMoOauthConfigurationTableRow($key, $value);
            $form['miniorange_oauth_client_summary_3'][$key] = $row;
          }

        $form['miniorange_oauth_client_config_submit'] = [
          '#type' => 'submit',
          '#id' => 'save_button',
          '#value' => t('Save Configuration'),
          '#button_type' => 'primary',
          '#prefix' => '<div class="oauth_config_buttons">'
        ];

         $baseUrlValue = Utilities::getOAuthBaseURL($base_url);

         $client_app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_app');


          $form['mo_reset'] = [
                '#markup' => "<a class='button button--danger' id ='vt_reset_config' ".$disableButton." href=".$base_url .'/admin/config/people/miniorange_oauth_client/config_clc?action=delete&button=configurationReset&app=' . $client_app.">Reset Configuration</a></div>",
            ];

            $form['miniorange_oauth_login_link'] = [
                '#id'  => 'miniorange_oauth_login_link',
                '#markup' => "<br><br><div class='mo_oauth_instruction_style'>
                    <br><strong><div class='mo_custom_font_size_1'>Instructions to add login link to different pages in your Drupal site: </div></strong><br>
                    <div class='mo_custom_font_size_2'>After completing your configurations, by default you will see a login link on your drupal site's login page.
                    However, if you want to add login link somewhere else, please follow the below given steps:</div>
                    <div class='mo_custom_font_size_3'>
                    <li>Go to <b>Structure</b> -> <b>Blocks</b></li>
                    <li> Click on <b>Add block</b></li>
                    <li>Enter <b>Block Title</b> and the <b>Block description</b></li>
                    <li>Under the <b>Block body</b> add the following URL to add a login link:
                        <ol> <h6>&lt;a href= '".$baseUrlValue."/moLogin'> Click here to Login&lt;/a&gt;</h6></ol>
                    </li>
                    <li>From the text filtered dropdown select either <b>Filtered HTML</b> or <b>Full HTML</b></li>
                    <li>From the division under <b>REGION SETTINGS</b> select where do you want to show the login link</li>
                    <li>Click on the <b>SAVE block</b> button to save your settings</li><br>
                    </div>
                    </div>",
                '#attributes' => [],
            ];

            $form['mo_header_style_end'] = ['#markup' => '</div></div>'];

            Utilities::moOAuthShowCustomerSupportIcon($form, $form_state);
            return $form;
        }
        else if((isset($_GET['action']) && $_GET['action'] == 'delete' )){
            $config = \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings');
            $config  ->set('miniorange_oauth_enable_login_with_oauth',1)
                    ->clear('miniorange_oauth_client_app')
                    ->clear('miniorange_oauth_client_appval')
                    ->clear('miniorange_auth_client_app_name')
                    ->clear('miniorange_auth_client_display_name')
                    ->clear('miniorange_auth_client_client_id')
                    ->clear('miniorange_auth_client_client_secret')
                    ->clear('miniorange_auth_client_scope')
                    ->clear('miniorange_auth_client_authorize_endpoint')
                    ->clear('miniorange_auth_client_access_token_ep')
                    ->clear('miniorange_auth_client_user_info_ep')
                    ->clear('miniorange_auth_client_stat')
                    ->clear('miniorange_auth_client_callback_uri')
                    ->clear('miniorange_oauth_send_with_header_oauth')
                    ->clear('miniorange_oauth_client_attr_list_from_server')
                    ->clear('miniorange_oauth_client_base_url')
                    ->clear('miniorange_oauth_client_enable_logging')
                    ->clear('miniorange_oauth_client_email_attr_val')
                    ->clear('miniorange_oauth_client_name_attr_val')
                    ->clear('miniorange_oauth_client_other_field_for_name')
                    ->clear('miniorange_oauth_client_other_field_for_email')
                    ->set('miniorange_oauth_send_with_body_oauth',1)
                    ->save();
           $miniorange_auth_client_callback_uri = $base_url."/mo_login";
           \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_callback_uri',$miniorange_auth_client_callback_uri)->save();
           \Drupal::messenger()->addMessage("Your Configurations have been deleted successfully");
           $response = new RedirectResponse($base_url."/admin/config/people/miniorange_oauth_client/config_clc");
           $response->send();
        }

        $client_app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_app');
        if($client_app != NULL)
        {
            $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

            $form['mo_oauth_top_div'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

            $form['mo_style_header_start'] = ['#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_center">',];

            $form['miniorange_oauth_client_msgs'] = [
                '#markup' => "<div class='mo_oauth_highlight_background_note_add_new_provider'>
                           <b class='mo_note_css'>Please Note:</b> Attribute Mapping is mandatory for login. Select the Email Attribute from Test Configuration and click on the <b>Done</b> button.</div><br>",
            ];

            $form['miniorange_oauth_client'] = [
                '#markup' => '<a data-dialog-type="modal" href="addnewprovider" class="use-ajax button button--primary add_new_provider">+Add New Provider</a>'
            ];

            $header = [
                'idp_name' => [
                  'data' => t('Provider Name')
                ],
                'client_id' => [
                    'data' => t('Client ID')
                ],
                'test' => [
                  'data' => t('Test')
                ],
                'action' => [
                  'data' => t('Action')
                ],
                'mapping' => [
                  'data' => t('Attribute & Role Mapping')
                ],
              ];


                $drop_button = [
                  '#type' => 'dropbutton',
                  '#dropbutton_type' => 'small',
                  '#links' => [
                    'edit' => [
                      'title' => $this->t('Edit'),
                      'url' => Url::fromUri($base_url . '/admin/config/people/miniorange_oauth_client/config_clc?action=update&app=' . $client_app),
                    ],
                    'delete' => [
                      'title' => $this->t('Delete'),
                      'url' => Url::fromUri($base_url . '/admin/config/people/miniorange_oauth_client/config_clc?action=delete&button=dropdownReset&app=' . $client_app),
                    ],

                    'Backup/Import' => [
                        'title' => $this->t('Backup/Import'),
                        'url' => Url::fromUri($base_url.'/admin/config/people/miniorange_oauth_client/backup'),
                        'localized_options' => [
                            'attributes' => [
                               'class' => ['use-ajax']
                            ],
                    ],
                  ],
                ]
                ];

                $client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
                $client_id = strlen($client_id) > 25 ? substr($client_id,0,24).'...' : $client_id;

                $table_content['rows'] = [
                  'idp_name' => $client_app,
                  'client_id' => $client_id,
                  'test' => Markup::create('<span id="base_Url" name="base_Url" data='. $baseUrlValue.'></span><a class="button button--primary button--small " href="#" id="testConfigButton">Perform Test Configuration</a>'),
                  'action' => [
                    'data' => $drop_button,
                  ],
                  'mapping' => Markup::create('<a class="button button--small" href="mapping">Configure</a>'),
                ];

                $form['mo_oauth_client_idplist_table'] = [
                  '#type' => 'table',
                  '#header' => $header,
                  '#rows' => $table_content,
                  '#empty' => t('<b>You have not configured any provider yet, Please Add provider by clicking above "Add New SP" button</b>'),
                  '#prefix' => '<br><br><br>',
                  '#suffix' => '</div>',
                  '#disabled' => $disabled,
                  '#attributes' => [
                    'class' => ['tableborder'],],
                ];

            $form['div2_close'] = [
                '#markup' => '</div></div>'
            ];
            Utilities::moOAuthShowCustomerSupportIcon($form,$form_state);
            return $form;
        }

        if(!empty($app_name_selected) && !empty($client_id)){
            $disabled = TRUE;
        }
        else{
            $disabled = FALSE;
        }

        $form['mo_oauth_top_div'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

        $form['mo_oauth_inside_div'] = [
          '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container">',
        ];

        $form['miniorange_oauth_client_summary'] = [
            '#type' => 'table',
            '#responsive' => TRUE ,
            '#caption' => $this->t('<span><h3>CONFIGURE APPLICATION</h3><div class ="container-inline" id="setupguide"></div><hr></span>'),
            '#attributes' => ['style' => 'border-collapse: separate;','class' => ['configtable'],],
        ];

        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

        $configurations =  self::getOauthConfigurations(0);
        $configurations['miniorange_oauth_client_app'] = 'Select Application';

        foreach($configurations as $key => $value){
            $row = self::generateMoOauthConfigurationTableRow($key, $value);
            $form['miniorange_oauth_client_summary'][$key] = $row;
        }

      //table for client id and client secret
      $form['miniorange_oauth_client_summary_1'] = [
          '#type' => 'table',
          '#responsive' => TRUE ,
          '#attributes' => ['style' => 'border-collapse: separate;width: 126%;','class' => ['configtable','configtable2'],],
          '#prefix' => '<div id="tour_client_id_secret_table">',
          '#suffix' => '</div>',
      ];

      foreach(self::getOauthConfigurations(1) as $key => $value){
        $row = self::generateMoOauthConfigurationTableRow($key, $value);
        $form['miniorange_oauth_client_summary_1'][$key] = $row;
      }


      //table for the scope and endpoints of server
      $form['miniorange_oauth_client_summary_2'] = [
          '#type' => 'table',
          '#responsive' => TRUE ,
          '#attributes' => ['style' => 'border-collapse: separate;width: 104%;','class' => ['configtable','configtable3'],],
          '#prefix' => '<div id="tour_server_endpoints_table">',
          '#suffix' => '</div>',
      ];
      foreach(self::getOauthConfigurations(2) as $key => $value){
        $row = self::generateMoOauthConfigurationTableRow($key, $value);
        $form['miniorange_oauth_client_summary_2'][$key] = $row;
      }


      //table for client id and client secret location and enable login
      $form['miniorange_oauth_client_summary_3'] = [
          '#type' => 'table',
          '#responsive' => TRUE ,
          '#attributes' => ['style' => 'border-collapse: separate;width:105%','class' => ['configtable'],],
      ];
      foreach(self::getOauthConfigurations(3) as $key => $value){
        $row = self::generateMoOauthConfigurationTableRow($key, $value);
        $form['miniorange_oauth_client_summary_3'][$key] = $row;
      }


        $form['miniorange_oauth_client_config_submit'] = [
            '#type' => 'submit',
            '#id' => 'save_button',
            '#value' => t('Save Configuration'),
            '#button_type' => 'primary',
            '#prefix' => '<div class="oauth_config_buttons">',
             '#suffix'=> '</div>'
        ];

        $baseUrlValue = Utilities::getOAuthBaseURL($base_url);

        $form['miniorange_oauth_login_link'] = [
            '#id'  => 'miniorange_oauth_login_link',
            '#markup' => "<br><br><div class='mo_oauth_instruction_style'>
                <br><strong><div class='mo_custom_font_size_1'>Instructions to add login link to different pages in your Drupal site: </div></strong><br>
                <div class='mo_custom_font_size_2'>After completing your configurations, by default you will see a login link on your drupal site's login page.
                However, if you want to add login link somewhere else, please follow the below given steps:</div>
                <div class='mo_custom_font_size_3'>
                <li>Go to <b>Structure</b> -> <b>Blocks</b></li>
                <li> Click on <b>Add block</b></li>
                <li>Enter <b>Block Title</b> and the <b>Block description</b></li>
                <li>Under the <b>Block body</b> add the following URL to add a login link:
                    <ol> <h6>&lt;a href= '".$baseUrlValue."/moLogin'> Click here to Login&lt;/a&gt;</h6></ol>
                </li>
                <li>From the text filtered dropdown select either <b>Filtered HTML</b> or <b>Full HTML</b></li>
                <li>From the division under <b>REGION SETTINGS</b> select where do you want to show the login link</li>
                <li>Click on the <b>SAVE block</b> button to save your settings</li><br>
                </div>
                </div>",
            '#attributes' => [],
        ];

        $form['mo_header_style_end'] = ['#markup' => '</div></div></div>'];

        Utilities::moOAuthShowCustomerSupportIcon($form, $form_state);
        return $form;
    }

  /**
   * Displays OAuth Client configuration fields.
   *
   * @param string $key
   *   The config variable of field.
   * @param string $value
   *   The title of field.
   */
    public static function generateMoOauthConfigurationTableRow($key, $value){

        global $base_url;
        $config = \Drupal::config('miniorange_oauth_client.settings');
        $module_path = \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client');

        $description = self::getDescriptionForConfiguration();

        if($key == 'send_client_id_secret'){
            $send_credentials = $value;
            $value = 'Send Client ID and secret in: <span role="tooltip" tabindex="0" aria-expanded="false" class="mo-oauth--help js-miniorange-oauth-help miniorange-oauth-help" data-miniorange-oauth-help="This option depends upon the OAuth provider. In case you are unaware about what to save, keeping this default is the best practice."><span aria-hidden="true">?</span></span>';
        }

        if($value == 'Callback/Redirect URL'){
            $value = 'Callback/Redirect URL <div class="mo_oauth_tooltip_cb"><img src="'.$base_url.'/'. $module_path . '/includes/images/info.png" alt="info icon" height="15px" width="15px"></div><div class="mo_oauth_tooltiptext_cb"><b>Note:</b> If your provider only support HTTPS <b>Callback/Redirect URL </b>and you have HTTP site, just save your base site URL with HTTPS in the <b>Sign In Settings</b> tab.
            </div>';
        }

        if($value == 'Enable Login with OAuth' || $value == 'Enforce HTTPS Callback URL' || $key == 'miniorange_auth_client_callback_uri' || $key == 'grant_type_selected' || $value == 'Login link on the login page' || $key == 'send_client_id_secret'){
            $required = false;
            $row[$key.$value] = [
                '#markup' => '<div class="container-inline mo-table_app1"><strong>'.$value.'</strong>',
            ];
        }
        else{
            $required = true;
            $row[$key.$value] = [
                '#markup' => '<div class="container-inline mo-table_app1"><strong>'.$value.'<span class="mo_note_css">*<span></strong>',
            ];
        }

        if($value == 'Enable Login with OAuth' || $value == 'Enforce HTTPS Callback URL'){
            $row[$key] = [
                '#type' => 'checkbox',
                '#default_value' => $config->get($key),
            ];

            $row[$key]['#title'] = ($value == 'Enable Login with OAuth') ? t('<i>( Note: Check this option to show SSO link on the Login page) </i>') : t('<i>( Note: Check this option if the OAuth Provider only support HTTPS Callback URL and you have an HTTP site.</i>');

        }
        else {
          if ($key == 'miniorange_auth_client_callback_uri') {
            $row[$key][$key] = [
                '#markup' => '<span id="callbackurl">' . $config->get($key) . '&nbsp;</span>',
                '#prefix' => '<span class="container-inline" id="tour_callback">',
            ];

            $row[$key]['miniorange_oauth_copy'] = [
                '#markup' => '<div class="callback_tooltip">
                <span class="button mo_copy_url button--small" id="copy_button">
                  <span class="tooltiptext" id="myTooltip">Copy to Clipboard</span>
                  &#128461; Copy
                  </span>
                </div></span>',
            ];
          } else if ($value == 'Application Name') {
            $row[$key] = [
                '#type' => 'textfield',
                '#id' => $key,
                '#disabled' => true,
                '#default_value' => $config->get($key),
            ];

          } else if ($key == 'grant_type_selected') {

            $row[$key] = [
                '#type' => 'radios',
                '#options' => [
                    'authorization_code' => self::grantTypeWithDescription(MiniorangeOAuthClientConstants::AUTH_CODE_GRANT),
                    'authorization_code_with_pkce' => self::grantTypeWithDescription(MiniorangeOAuthClientConstants::AUTH_CODE_PKCE_GRANT),
                    'password' => self::grantTypeWithDescription(MiniorangeOAuthClientConstants::PASS_GRANT),
                    'implicit' => self::grantTypeWithDescription(MiniorangeOAuthClientConstants::IMPLICIT_GRANT),
                ],
                '#default_value' => 'authorization_code',
                '#disabled' => TRUE,
                '#prefix' => '<span class="container-inline client_cred">',
                '#suffix' => '</span>',
                '#attributes' => [
                    'class' => ['container-inline'],],
            ];
          } else if ($key == 'send_client_id_secret') {
            $row[$key . '_oauth'][$send_credentials[0]] = [
                '#type' => 'checkbox',
                '#title' => '&nbsp;Header',
                '#default_value' => $config->get($send_credentials[0]),
                '#prefix' => '<span class="container-inline client_cred">'
            ];

            $row[$key . '_oauth'][$send_credentials[1]] = [
                '#type' => 'checkbox',
                '#title' => '&nbsp;&nbsp;&nbsp;Body',
                '#attributes' => [
                    'class' => ['client_cred'],],
                '#attributes' => ['style' => 'margin-left: 30px;'],
                '#default_value' => $config->get($send_credentials[1]),
                '#suffix' => '</span>'
            ];

          } else if ($key == 'miniorange_oauth_client_app') {
            $row[$key] = [
                '#type' => 'select',
                '#options' => self::getOauthProviders(),
                '#id' => $key,
                '#required' => true,
                '#description' => t('Select an OAuth Server'),
                '#attributes' => ['style' => 'width:86%'],
            ];
          } else if ($key == 'miniorange_auth_client_user_info_ep' || $key == 'miniorange_auth_client_access_token_ep' || $key == 'miniorange_auth_client_authorize_endpoint') {
            $row[$key] = [
                '#type' => 'url',
                '#id' => $key,
                '#default_value' => $config->get($key),
                '#required' => $required,
            ];
          } else {
            $row[$key] = [
                '#type' => 'textfield',
                '#id' => $key,
                '#default_value' => $config->get($key),
                '#required' => $required,
                '#description' => isset($description[$key]) ? $description[$key] : '',
            ];
          }
        }

      return $row;

    }

  /**
   * List of OAuth/OpendID providers.
   *
   * @return array
   *   Returns array of providers.
   */
  public static function getOauthProviders() {
    $oauth_providers = [
      'Azure AD' => t('Azure AD'),
      'Box' => t('Box'),
      'Discord' => t('Discord'),
      'Facebook' => t('Facebook'),
      'FitBit' => t('FitBit'),
      'GitHub' => t('GitHub'),
      'Google' => t('Google'),
      'Keycloak' => t('Keycloak'),
      'Line' => t('Line'),
      'LinkedIn' => t('LinkedIn'),
      'Okta' => t('Okta (OAuth)'),
      'Paypal' => t('Paypal'),
      'Salesforce' => t('Salesforce'),
      'Slack' => t('Slack'),
      'Strava' => t('Strava'),
      'Wild Apricot' => t('Wild Apricot'),
      'Zendesk' => t('Zendesk'),
      'Custom' => t('Custom OAuth 2.0 Provider'),
      'Azure AD B2C' => t('Azure AD B2C (Premium and Enterprise)'),
      'AWS Cognito' => t('AWS Cognito (Premium and Enterprise)'),
      'Onelogin' => t('Onelogin (Premium and Enterprise)'),
      'miniOrange' => t('miniOrange (Premium and Enterprise)'),
      'Okta_openid' => t('Okta (OpenID) (Premium and Enterprise)'),
      'Custom_openid' => t('Custom OpenID Provider (We support OpenID protocol in Premium and Enterprise version)'),
    ];
    return $oauth_providers;
  }

  /**
   * The config variable => title array of configuration fields.
   *
   * @return array
   *   Returns array of fields with config variable
   */
   public static function getOauthConfigurations($section){

        global $base_url;
        $url_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client'). '/includes/images';

        $OAuth_configuration[0] =  [
            'miniorange_oauth_client_app' => 'Application Name',
            'miniorange_auth_client_callback_uri' => 'Callback/Redirect URL',
            'miniorange_auth_client_app_name' => 'Display Name',
            'miniorange_auth_client_display_name' => 'Login link on the login page',
            'grant_type_selected' => 'Grant Types<a href="licensing"><img src="' . $url_path . '/pro.png" alt="ENTERPRISE" class="shortened-image"><span class="mo_pro_tooltip">Available in the Enterprise version</span></a>',
        ];

        $OAuth_configuration[1] = [
            'miniorange_auth_client_client_id' => 'Client ID',
            'miniorange_auth_client_client_secret' => 'Client Secret',
        ];

      $OAuth_configuration[2] = [
          'miniorange_auth_client_scope' => 'Scope',
          'miniorange_auth_client_authorize_endpoint' => 'Authorize Endpoint',
          'miniorange_auth_client_access_token_ep' => 'Access Token Endpoint',
          'miniorange_auth_client_user_info_ep' => 'Get User Info Endpoint',
      ];

      $OAuth_configuration[3] = [
          'miniorange_oauth_client_enforce_https_in_callback_url' => 'Enforce HTTPS Callback URL',
          'send_client_id_secret' => ['miniorange_oauth_send_with_header_oauth','miniorange_oauth_send_with_body_oauth'],
          'miniorange_oauth_enable_login_with_oauth' => 'Enable Login with OAuth',
      ];

        return $OAuth_configuration[$section];
    }

  /**
   * The config variable => field description array.
   *
   * @return array
   *   Returns description array for fields.
   */
  public static function getDescriptionForConfiguration() {
    $oauth_desc = [];

    $oauth_desc = [
      'miniorange_auth_client_display_name' => t('<b>Note:</b> The login link will appear on the user login page in this manner'),
      'miniorange_auth_client_scope' => t('Scope decides the range of data that you will be getting from your OAuth Provider'),
    ];

    return $oauth_desc;
  }

  /**
   * Validate handler for oauth client configuration.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
        $form_values = $form_state->getValues();
        $enable_with_header = $form_values['miniorange_oauth_client_summary_3']['send_client_id_secret']['send_client_id_secret_oauth']['miniorange_oauth_send_with_header_oauth'];
        $enable_with_body = $form_values['miniorange_oauth_client_summary_3']['send_client_id_secret']['send_client_id_secret_oauth']['miniorange_oauth_send_with_body_oauth'];

        if ($enable_with_header == 0 && $enable_with_body == 0 ) {
            $form_state->setErrorByName('miniorange_oauth_client', t('This state is not allowed. Please select at least one of the options to send Client ID and Secret.'));
        }
    }

  /**
   * Submit handler for saving oauth client configuration.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   *
   * @return void
   *   Returns void.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
        global $base_url;
        $baseUrlValue = Utilities::getOAuthBaseURL($base_url);
        $form_values = $form_state->getValues();

        if(isset($form_values['miniorange_oauth_client_summary']['miniorange_oauth_client_app']))
            $client_app =  trim($form_values['miniorange_oauth_client_summary']['miniorange_oauth_client_app']['miniorange_oauth_client_app']);
        if(isset($form_values['miniorange_oauth_client_summary']['miniorange_auth_client_app_name']))
            $app_name = trim( $form_values['miniorange_oauth_client_summary']['miniorange_auth_client_app_name']['miniorange_auth_client_app_name'] );
        $app_name = str_replace(' ', '', $app_name);

        if(isset($form_values['miniorange_oauth_client_summary']['miniorange_auth_client_display_name']))
            $display_name = trim( $form_values['miniorange_oauth_client_summary']['miniorange_auth_client_display_name']['miniorange_auth_client_display_name']);

        if(isset($form_values['miniorange_oauth_client_summary_1']['miniorange_auth_client_client_id']))
            $client_id = trim( $form_values['miniorange_oauth_client_summary_1']['miniorange_auth_client_client_id']['miniorange_auth_client_client_id'] );
        if(isset($form_values['miniorange_oauth_client_summary_1']['miniorange_auth_client_client_secret']))
            $client_secret = trim( $form_values['miniorange_oauth_client_summary_1']['miniorange_auth_client_client_secret']['miniorange_auth_client_client_secret']);

        if(isset($form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_scope']))
            $scope = trim( $form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_scope']['miniorange_auth_client_scope']);
        if(isset($form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_authorize_endpoint']))
            $authorize_endpoint = trim($form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_authorize_endpoint']['miniorange_auth_client_authorize_endpoint']);
        if(isset($form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_access_token_ep']))
            $access_token_ep = trim($form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_access_token_ep']['miniorange_auth_client_access_token_ep']);
        if(isset($form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_user_info_ep']))
            $user_info_ep = trim($form_values['miniorange_oauth_client_summary_2']['miniorange_auth_client_user_info_ep']['miniorange_auth_client_user_info_ep']);
        $email_attr = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_email_attr_val');


        if(($client_app=='Select') || empty($client_app) || empty($app_name) || empty($client_id) || empty($client_secret) || empty($authorize_endpoint) || empty($access_token_ep)
            || empty($user_info_ep)) {
            if(empty($client_app)|| $client_app == 'Select'){
                \Drupal::messenger()->addMessage(t('The <b>Select Application</b> dropdown is required. Please Select your application.'), 'error');
                return;
            }
            \Drupal::messenger()->addMessage(t('The <b>Display name</b>, <b>Client ID</b>, <b>Client Secret</b>, <b>Authorize Endpoint</b>, <b>Access Token Endpoint</b>
                , <b>Get User Info Endpoint</b> fields are required.'), 'error');
            return;
        }

        if(empty($client_app))
        {
            $client_app =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_app');
        }
        if(empty($app_name))
        {
            $client_app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
        }
        if (empty($display_name))
        {
            $display_name = '';
        }
        if(empty($client_id))
        {
            $client_id =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
        }
        if(empty($client_secret))
        {
            $client_secret = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_secret');
        }
        if(empty($scope))
        {
            $scope = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_scope');
        }
        if(empty($authorize_endpoint))
        {
            $authorize_endpoint = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_authorize_endpoint');
        }
        if(empty($access_token_ep))
        {
            $access_token_ep =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_access_token_ep');
        }
        if(empty($user_info_ep))
        {
            $user_info_ep = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_user_info_ep');
        }

        $callback_uri = $baseUrlValue."/mo_login";

        $app_values = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_appval');
        if(!is_array($app_values))
            $app_values = array();
        $app_values['client_id'] = $client_id;
        $app_values['client_secret'] = $client_secret;
        $app_values['app_name'] = $app_name;
        $app_values['display_name'] = $display_name;
        $app_values['scope'] = $scope;
        $app_values['authorize_endpoint'] = $authorize_endpoint;
        $app_values['access_token_ep'] = $access_token_ep;
        $app_values['user_info_ep'] = $user_info_ep;
        $app_values['callback_uri'] = $callback_uri;
        $app_values['client_app'] = $client_app;
        $app_values['miniorange_oauth_client_email_attr'] = $email_attr;

        $enable_login_with_oauth = $form_values['miniorange_oauth_client_summary_3']['miniorange_oauth_enable_login_with_oauth']['miniorange_oauth_enable_login_with_oauth'];
        $enable_login = $enable_login_with_oauth == 1 ? TRUE : FALSE;
        $enable_with_header = $form_values['miniorange_oauth_client_summary_3']['send_client_id_secret']['send_client_id_secret_oauth']['miniorange_oauth_send_with_header_oauth'];
        $enable_with_body = $form_values['miniorange_oauth_client_summary_3']['send_client_id_secret']['send_client_id_secret_oauth']['miniorange_oauth_send_with_body_oauth'];
        $enable_header = $enable_with_header == 1 ? TRUE : FALSE ;
        $enable_body = $enable_with_body == 1 ? TRUE : FALSE ;
        $enforceHttpsInCallbackUrl = $form_values['miniorange_oauth_client_summary_3']['miniorange_oauth_client_enforce_https_in_callback_url']['miniorange_oauth_client_enforce_https_in_callback_url'];
        $enforceHttpsInCallbackUrl = $enforceHttpsInCallbackUrl == 1 ? TRUE : FALSE;

        $config = \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings');
        $request = \Drupal::request();
        if($enforceHttpsInCallbackUrl){
          $baseUrl = 'https://'.$request->getHost().$request->getBaseUrl();
          $callbackUrl = $baseUrl."/mo_login";
        }else{
          $baseUrl = $request->getSchemeAndHttpHost().$request->getBaseUrl();
          $callbackUrl = $baseUrl."/mo_login";
        }

        $config->set('miniorange_oauth_enable_login_with_oauth', $enable_login)
          ->set('miniorange_oauth_client_app', $client_app)
          ->set('miniorange_oauth_client_appval', $app_values)
          ->set('miniorange_auth_client_app_name', $app_name)
          ->set('miniorange_auth_client_display_name', $display_name)
          ->set('miniorange_auth_client_client_id', $client_id)
          ->set('miniorange_auth_client_client_secret', $client_secret)
          ->set('miniorange_auth_client_scope', $scope)
          ->set('miniorange_auth_client_authorize_endpoint', $authorize_endpoint)
          ->set('miniorange_auth_client_access_token_ep', $access_token_ep)
          ->set('miniorange_auth_client_user_info_ep', $user_info_ep)
          ->set('miniorange_auth_client_stat', "Review Config")
          ->set('miniorange_auth_client_callback_uri', $callback_uri)
          ->set('miniorange_oauth_send_with_header_oauth', $enable_header)
          ->set('miniorange_oauth_send_with_body_oauth', $enable_body)
          ->set('miniorange_oauth_client_enforce_https_in_callback_url', $enforceHttpsInCallbackUrl)
          ->set('miniorange_auth_client_callback_uri', $callbackUrl)
          ->save();
        \Drupal::messenger()->addMessage(t('Configurations saved successfully. Please click on the <b>Test Configuration</b> button to test the connection.'), 'status');
        if(isset($_GET['action']) && $_GET['action'] == 'update'){
            $response = new RedirectResponse($base_url.'/admin/config/people/miniorange_oauth_client/config_clc');
            $response->send();
            return new Response();
        }
    }


    /**
     * @param $grant_type  : Name of the grant type
     *
     */
  public static function grantTypeWithDescription($grant_type){
      return Markup::create($grant_type.''.Utilities::getTooltipIcon($grant_type,self::getGrantDescription($grant_type)));
    }

  public static function getGrantDescription($grant_type){
      $grant_description = [];
      $grant_description[MiniorangeOAuthClientConstants::AUTH_CODE_GRANT] = 'Authorization Code Grant is used by web and mobile applications. It requires the client to exchange authorization code with OAuth server for access token.<br><br><a href="'.MiniorangeOAuthClientConstants::AUTH_CODE_GRANT_GUIDE.'" target="_blank">Know more..</a>';
      $grant_description[MiniorangeOAuthClientConstants::AUTH_CODE_PKCE_GRANT] = 'Authorization Code Grant with PKCE is an extension of the standard Authorization Code Grant flow. It is considered best for Single Page Apps (SPA) or Mobile Apps. Client Secret is not required while using PKCE flow.<br><br><a href="'.MiniorangeOAuthClientConstants::AUTH_CODE_PKCE_GRANT_GUIDE.'" target="_blank">Know more..</a>';
      $grant_description[MiniorangeOAuthClientConstants::PASS_GRANT] = 'Password Grant is used by applications to exchange users credentials for access token. This, generally, should be used by internal applications.<br><br><a href="'.MiniorangeOAuthClientConstants::PASS_GRANT_GUIDE.'" target="_blank">Know more..</a>';
      $grant_description[MiniorangeOAuthClientConstants::IMPLICIT_GRANT] = 'The Implicit Grant is a simplified version of Authorization Code Grant flow. OAuth providers directly offer access token after authenticating user when using this grant type.<br><br><a href="'.MiniorangeOAuthClientConstants::IMPLICIT_GRANT_GUIDE.'" target="_blank">Know more..</a>';

      return $grant_description[$grant_type];
    }
}