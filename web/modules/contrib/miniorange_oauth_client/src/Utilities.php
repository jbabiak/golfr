<?php

/**
 * @package    miniOrange
 * @subpackage Plugins
 * @license    GNU/GPLv3
 * @copyright  Copyright 2015 miniOrange. All Rights Reserved.
 *
 *
 * This file is part of miniOrange Drupal OAuth Client module.
 *
 * miniOrange Drupal OAuth Client modules is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * miniOrange Drupal OAuth Client module is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with miniOrange Drupal OAuth Client module.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Drupal\miniorange_oauth_client;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class for handling utility functions in project.
 */
class Utilities {

  /**
   * Checks Drupal version of site.
   *
   * @return string
   *   Returns Drupal version of site.
   */
  public static function moGetDrupalCoreVersion() {
    $drupal_version = explode(".", \DRUPAL::VERSION);
    return $drupal_version[0];
  }

  /**
   * Displays customer support button.
   */
  public static function moOauthShowCustomerSupportIcon(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $support_image_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client') . '/includes/images';
    $form['mo_oauth_client_customer_support_icon'] = [
      '#markup' => t('<a class="use-ajax mo-bottom-corner" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:&quot;40%&quot;}" href="CustomerSupportClient"><img src="' . $support_image_path . '/mo-customer-support.png" alt="test image"></a>'),
    ];
  }

  /**
   * Checks base_url of site.
   *
   * @return string
   *   Returns base url of site.
   */
  public static function getOauthBaseURL($base_url) {
    if (!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url'))) {
      $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
    }
    else {
      $baseUrlValue = $base_url;
    }

    return $baseUrlValue;
  }

  /**
   * Checks curl extension is installed or not.
   *
   * @return int
   *   Return 1 if curl is installed else false.
   */
  public static function isCurlInstalled() {
    if (in_array('curl', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Makes api request to respective endpoint.
   *
   * @param string $url
   *   The endpoint where we make api request.
   * @param array $fields
   *   The api request body.
   * @param array $header
   *   The api request header.
   * @param string $get_post
   *   The method of api request.
   */
  public static function callService($url, $fields, $header = FALSE, $get_post = '') {
    if (!Utilities::isCurlInstalled()) {
      return json_encode([
        "statusCode" => 'ERROR',
        "statusMessage" => 'cURL is not enabled on your site. Please enable the cURL module.',
      ]);
    }

    $fieldString = is_string($fields) ? $fields : json_encode($fields);
    if ($get_post == 'GET') {
      try {
        $response = \Drupal::httpClient()
          ->get($url, [
            'headers' => $header,
            'verify' => FALSE,
          ]);
        return $response->getBody();
      }
      catch (\Exception $exception) {
        $error = [
          '%error' => $exception->getMessage(),
        ];
        \Drupal::logger('miniorange_oauth_client')->notice('Error: %error', $error);
        self::showErrorMessage($error);
      }
    }
    else {
      try {
        $response = \Drupal::httpClient()
          ->post($url, [
            'body' => $fieldString,
            'allow_redirects' => TRUE,
            'http_errors' => FALSE,
            'decode_content' => TRUE,
            'verify' => FALSE,
            'headers' => $header,
          ]);
        return $response->getBody()->getContents();
      }
      catch (\Exception $exception) {
        $error = [
          '%error' => $exception->getMessage(),
        ];
        \Drupal::logger('miniorange_oauth_client')->notice('Error:  %error', $error);
        self::showErrorMessage($error);
      }
    }
    return NULL;
  }

  /**
   * Checks if module is getting uninstallled on cli.
   *
   * @return bool
   *   Returns true if cli else false
   */
  public static function drupalIsCli() {
    $server = \Drupal::request()->server;
    $server_software = $server->get('SERVER_SOFTWARE');
    $server_argc = $server->get('argc');

    if (!isset($server_software) && (php_sapi_name() == 'cli' || (is_numeric($server_argc) && $server_argc > 0))) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Displays error message.
   */
  public static function showErrorMessage($get) {
    echo '<div style="font-family:Calibri;padding:0 3%;">';
    echo '
            <div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;">
            ERROR
            </div>
            <div style="color: #a94442;font-size:14pt; margin-bottom:20px;">';

    foreach ($get as $key => $val) {
      if ($key == 'state') {
        continue;
      }
      echo '<p><strong>' . $key . ': </strong>' . $val . '</p>';
    }
    echo '</div>
            </div>';
    exit;
  }

  /**
   * Checks if customer is registered or not.
   *
   * @return bool
   *   Returns true if customer registered else false.
   */
  public static function isCustomerRegistered() {
    if (
        empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email'))||
        empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_id')) ||
        empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_token')) ||
        empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_api_key'))) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Displays block of setup guides to configure OAuth Client module with various oauth servers.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public static function spConfigGuide(&$form, &$form_state) {
    $form['miniorange_idp_setup_guide_link'] = [
      '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_2" id="mo_oauth_guide_vt">',
    ];

    $form['miniorange_idp_guide_link1'] = [
      '#markup' => '<div><h5>To see detailed documentation of how to configure Drupal OAuth Client with any OAuth Server.</h5></div></br>',
    ];

    $form['miniorange_oauth_guide_table_list'] = [
      '#markup' => '<div class="table-responsive mo_guide_text-center" style="font-family: sans-serif;font-size: 15px;">
                <table class="mo_guide_table mo_guide_table-striped mo_guide_table-bordered" style="border: 1px solid #ddd;max-width: 100%;border-collapse: collapse;">
                    <thead>
                        <tr><th class="mo_guide_text-center" colspan="2">Providers</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/setup-guide-to-configure-azure-ad-with-drupal-oauth-client" target="_blank">Azure AD</a></td> <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/setup-guide-to-configure-line-with-drupal-oauth-client" target="_blank">Line</a></td></tr>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-slack-as-as-oauth-openid-connect-server-in-drupal" target="_blank">Slack</a></td>        <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-fitbit-oauth-server-for-drupal-8" target="_blank">Fitbit</a></td></tr>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-google-oauth-server-drupal-8" target="_blank">Google</a></td>                       <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-linkedin-as-an-oauth-openid-connect-server-for-drupal-8-client" target="_blank">LinkedIn</a></td></tr>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/guide-to-configure-keycloak-for-drupal-oauth-client-module" target="_blank">Keycloak</a></td><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/guide-salesforce-configuration-drupal-oauth-client-module" target="_blank">Salesforce</a></td></tr>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-github-oauthopenid-connect-server-drupal-8" target="_blank">Github</a></td>         <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/guide-configure-box-drupal" target="_blank">Box</a></td></tr>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-facebook-oauth-server-for-drupal-8" target="_blank">Facebook</a> </td>              <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-instagram-as-an-oauth-openid-connect-server-for-drupal-8-client" target="_blank">Instagram</a></strong></td></tr>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/setup-guide-to-configure-discord-with-drupal-oauth-client" target="_blank">Discord</a> </td>  <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/configure-reddit-oauthopenid-connect-server-drupal-8" target="_blank">Reddit</a></strong></td></tr>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/guide-to-configure-wildapricot-with-drupal" target="_blank">Wild Apricot</a> </td>            <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/guide-configure-zendesk-drupal" target="_blank">Zendesk</a> </td>
                        <tr><td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/guide-to-configure-okta-with-drupal" target="_blank">Okta</a> </td>            <td class="mo_guide_text-center"><a class="mo_guide_text-color" href="https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal" target="_blank">Other Servers</a></strong></td></tr>
                    </tbody>
                </table>
                <div>In case you do not find your desired OAuth Server listed here, please mail us on <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>
                    and we will help you to set it up.</div>
                      </div>',
    ];
    self::faq($form, $form_state);
  }

  /**
   * Displays form to get feedback if new is needed to customer.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public static function nofeaturelisted(&$form, &$form_state) {
    $module_path = \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client');
    global $base_url;

    $form['miniorange_no_feature_list'] = [
      '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_2">',
    ];

    $form['mo_oauth_horizontal_tabs'] = [
      '#type' => 'horizontal_tabs',
    ];

    $form['miniorange_more_features'] = [
      '#type' => 'fieldset',
      '#title' => t('<strong>Looking for more features?</strong>'),
      '#group' => 'mo_oauth_horizontal_tabs',
    ];

    $form['miniorange_more_features']['miniorange_no_feature_list1'] = [
      '#markup' => '<div class="mo_oauth_no_feature">
                                   <img src="' . $base_url . '/' . $module_path . '/includes/images/more_features.jpg" alt="More Features icon" height="200px" width="200px"></div>',
    ];

    $form['miniorange_more_features']['miniorange_no_feature_list2'] = [
      '#markup' => '<div>In case you do not find your desired feature or if you want any custom feature in the module, please mail us on <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>
                              and we will implement it for you.</div>',

    ];
    self::faq($form, $form_state);

    $form['miniorange_oauth_div_close'] = [
      '#markup' => '</div>',
    ];

  }

  /**
   * Displays FAQ's links.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public static function faq(&$form, &$form_state) {
    $form['miniorange_more_features']['miniorange_oauth_faq_button_css'] = [
      '#attached' => [
        'library' => 'miniorange_oauth_client/miniorange_oauth_client.style_settings',
      ],
    ];

    $form['miniorange_more_features']['miniorange_faq'] = [
      '#markup' => '<br><div class="mo_oauth_client_text_center"><b></b>
                          <a class="button button--primary" href="https://faq.miniorange.com/kb/drupal/drupal-oauth-oidc-sso/" target="_blank">FAQs</a>
                                    <b></b><a class="button button--primary" href="https://forum.miniorange.com/" target="_blank">Ask questions on forum</a></div>',
    ];
  }

  /**
   * Creates array of different config variables with fields in the respective tabs.
   *
   * @param string $class_name
   *   The tab name.
   *
   * @return array
   *   Returns config variables for respective tabs.
   */
  public static function getVariableArray($class_name) {

    if ($class_name == "mo_options_enum_client_configuration") {
      $class_object = [
        'App_selected'  => 'miniorange_oauth_client_app',
        'App_name'  => 'miniorange_auth_client_app_name',
        'Display_link' => 'miniorange_auth_client_display_name',
        'Client_ID' => 'miniorange_auth_client_client_id',
        'Client_secret' => 'miniorange_auth_client_client_secret',
        'Client_scope' => 'miniorange_auth_client_scope',
        'Authorized_endpoint' => 'miniorange_auth_client_authorize_endpoint',
        'Access_token_endpoint' => 'miniorange_auth_client_access_token_ep',
        'Userinfo_endpoint' => 'miniorange_auth_client_user_info_ep',
        'Callback_url' => 'miniorange_auth_client_callback_uri',
        'credentials_via_header' => 'miniorange_oauth_send_with_header_oauth',
        'credentials_via_body' => 'miniorange_oauth_send_with_body_oauth',
        'Enable_login_with_oauth' => 'miniorange_oauth_enable_login_with_oauth',
      ];
    }
    elseif ($class_name == "mo_options_enum_attribute_mapping") {
      $class_object = [
        'Email_attribute_value'    => 'miniorange_oauth_client_email_attr_val',
        'Username_attribute_value' => 'miniorange_oauth_client_name_attr_val',
      ];
    }
    elseif ($class_name == "mo_options_enum_signin_settings") {
      $class_object = [
        'Base_URL_value'    => 'miniorange_oauth_client_base_url',
      ];
    }
    return $class_object;
  }

  /**
   * Creates array OAuth Client Module features.
   *
   * @return array
   *   Returns array of features.
   */
  public static function getOAuthFeaturelist(){
    $feature_list = [
      "Auto Create user in Drupal" => "Auto Create user in Drupal",
      "OpenID Connect" => "OpenID Connect",
      "Multiple OAuth Providers" => "Multiple OAuth Providers",
      "Advanced Attribute Mapping" => "Advanced Attribute Mapping",
      "Advanced Role Mapping" => "Advanced Role Mapping",
      "Profile Mapping" => "Profile Mapping",
      "Role based Restriction" => "Role based Restriction",
      "Custom Redirect after Login and Logout" => "Custom Redirect after Login and Logout",
      "Page Restriction" => "Page Restriction",
      "Domain Restriction" => "Domain Restriction",
      "Replace Drupal Login Page with OAuth Server Login Page" => "Replace Drupal Login Page with OAuth Server Login Page",
      "Login Reports and Analytics" => "Login Reports and Analytics"
    ];
    return $feature_list;
  }

  /**
   * Adds logs if logging enabled.
   *
   * @param string $file
   *   The filename.
   * @param string $function
   *   The function name.
   * @param string $line
   *   The linenumber.
   * @param string $message
   *   The message of log.
   */
  public static function addLogger($file, $function, $line, $message) {
    if (\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_enable_logging')) {
      \Drupal::logger('miniorange_oauth_client')->debug($file . ', ' . $function . ' , ' . $line . ': ' . $message);
    }
  }

  /**
   * Sets value of variable.
   *
   * @param string $message
   *   The sso status.
   */
  public static function setSsoStatus($message) {
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_sso_status', Json::encode($message))->save();
  }

  /**
   * This function is used to get the timestamp value.
   *
   * @return string
   *   Returns timestamp.
   */
  public static function getOauthTimestamp() {
    $url = 'https://login.xecurify.com/moas/rest/mobile/get-timestamp';
    $content = Utilities::callService($url, [], []);
    if (empty($content)) {
      $currentTimeInMillis = round(microtime(TRUE) * 1000);
      $currentTimeInMillis = number_format($currentTimeInMillis, 0, '', '');
    }
    return empty($content) ? $currentTimeInMillis : $content;
  }

  /**
   * Show attribute list coming from server on Attribute Mapping tab.
   *
   * @return void
   *   Returns void.
   */
  public static function showAttrListFromIdp(&$form, $form_state) {
    global $base_url;
    $server_attrs = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_attr_list_from_server');
    if (isset($server_attrs)) {
      $server_attrs = JSON::decode($server_attrs);
    }
    if (empty($server_attrs)) {
      Utilities::nofeaturelisted($form, $form_state);
      return;
    }

    $form['miniorange_idp_guide_link'] = [
      '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_2" id="mo_oauth_guide_vt">',
    ];

    $form['miniorange_oauth_attr_header'] = [
      '#markup' => '<div class="mo_attr_table">Attributes received from the OAuth Server:</div><br>',
    ];

    $icnt = count($server_attrs);
    if ($icnt >= 8) {
      $scrollkit = 'scrollit';
    }
    else {
      $scrollkit = '';
    }

    $form['mo_oauth_attrs_list_idp'] = [
      '#markup' => '<div class="table-responsive mo_guide_text-center" style="font-family: sans-serif;font-size: 12px;"><div class=' . $scrollkit . '>
                <table class="mo_guide_table mo_guide_table-striped mo_guide_table-bordered" style="border: 1px solid #ddd;max-width: 100%;border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="mo_guide_text-center mo_td_values">ATTRIBUTE NAME</th>
                            <th class="mo_guide_text-center mo_td_values">ATTRIBUTE VALUE</th>
                        </tr>
                              </thead>',
    ];

    $someattrs = '';
    self::showAttr($server_attrs, $someattrs);

    $form['miniorange_oauth_guide_table_list'] = [
      '#markup' => '<tbody style="font-weight:bold;font-size: 12px;color:gray;">' . $someattrs . '</tbody></table></div>',
    ];

    $form['miniorange_break'] = [
      '#markup' => '<br>',
    ];

    $form['miniorange_oauth_clearAttrList'] = [
      '#type' => 'submit',
      '#value' => t('Clear Attribute List'),
      '#submit' => ['::clearAttrList'],
      '#limit_validation_errors' => [],
      '#attributes' => ['class'=> ['button','button--primary'] ]
    ];

    $form['miniorange_oauth_guide_clear_list_note'] = [
      '#markup' => '<br><div style="font-size: 13px;"><b>NOTE : </b>Please clear this list after configuring the module to hide your confidential attributes.<br>
                                      Click on <b>Test configuration</b> in <b>CONFIGURE OAUTH</b> tab to populate the list again.</div>',
    ];

    $form['miniorange_oauth_guide_table_end'] = [
      '#markup' => '</div>',
    ];
  }

  /**
   * Displays attrs received from OAuth server in table format.
   *
   * @return string
   *   Returns attrs in table format.
   */
  public static function showAttr($attrs, &$result, $tr = '<tr>', $td = '<td>') {
    if (!is_array($attrs) || sizeof($attrs) < 1) {
      return is_array($attrs) ? '' : $attrs . '</td></tr>';
    }
    foreach ($attrs as $key => $value) {
      $result .= $tr . $td . $key . '</td>' . $td . $value . '</td></tr>';
    }
  }

  /**
   * @param string $title
   *  Title of the tooltip
   * @param string $description
   *  Descritption of the tooltip
   * @param string $icon
   *  The icon of tooltip
   * @return string
   *    Return the Tooltip with ? icon and title and description
   */

  public static function getTooltipIcon($title, $description, $icon = '?', $class = ''){

    $helper_text = '<div class="mo-oauth--help--title">'.$title.'</div><div class="mo-oauth--help--content">'.$description. '</div>';
    $helper_text = htmlspecialchars($helper_text, ENT_QUOTES, 'UTF-8');

    return '<span role="tooltip" tabindex="0" aria-expanded="false" class="mo-oauth--help js-miniorange-oauth-help miniorange-oauth-help '.$class.'" data-miniorange-oauth-help="'.$helper_text.'"><span aria-hidden="true">'.$icon.'</span></span>';
  }

  /**
   * The countries => timezone array.
   *
   * @var array
   *   The array of different countries with their timezone.
   */
  public static $zones = [
    "Niue Time (GMT-11:00)" => "Pacific/Niue",
    "Samoa Standard Time (GMT-11:00) " => "Pacific/Pago_Pago",
    "Cook Islands Standard Time (GMT-10:00)" => "Pacific/Rarotonga",
    "Hawaii-Aleutian Standard Time (GMT-10:00) " => "Pacific/Honolulu",
    "Tahiti Time (GMT-10:00)" => "Pacific/Tahiti",
    "Marquesas Time (GMT-09:30)" => "Pacific/Marquesas",
    "Gambier Time (GMT-09:00)" => "Pacific/Gambier",
    "Hawaii-Aleutian Time (Adak) (GMT-09:00)" => "America/Adak",
    "Alaska Time - Anchorage(GMT-08:00)" => "America/Anchorage",
    "Alaska Time - Juneau (GMT-08:00)" => "America/Juneau",
    "Alaska Time - Metlakatla (GMT-08:00)" => "America/Metlakatla",
    "Alaska Time - Nome (GMT-08:00)" => "America/Nome",
    "Alaska Time - Sitka (GMT-08:00)" => "America/Sitka",
    "Alaska Time - Yakutat (GMT-08:00)" => "America/Yakutat",
    "Pitcairn Time (GMT-08:00)" => "Pacific/Pitcairn",
    "Mexican Pacific Standard Time (GMT-07:00)" => "America/Hermosillo",
    "Mountain Standard Time - Creston (GMT-07:00)" => "America/Creston",
    "Mountain Standard Time - Dawson (GMT-07:00)" => "America/Dawson",
    "Mountain Standard Time - Dawson Creek (GMT-07:00)" => "America/Dawson_Creek",
    "Mountain Standard Time - Fort Nelson (GMT-07:00)" => "America/Fort_Nelson",
    "Mountain Standard Time - Phoenix (GMT-07:00)" => "America/Phoenix",
    "Mountain Standard Time - Whitehorse (GMT-07:00)" => "America/Whitehorse",
    "Pacific Time - Los Angeles (GMT-07:00)" => "America/Los_Angeles",
    "Pacific Time - Tijuana (GMT-07:00)" => "America/Tijuana",
    "Pacific Time - Vancouver (GMT-07:00)" => "America/Vancouver",
    "Central Standard Time - Belize (GMT-06:00)" => "America/Belize",
    "Central Standard Time - Costa Rica (GMT-06:00)" => "America/Costa_Rica",
    "Central Standard Time - El Salvador (GMT-06:00)" => "America/El_Salvador",
    "entral Standard Time - Guatemala (GMT-06:00)" => "America/Guatemala",
    "Central Standard Time - Managua (GMT-06:00)" => "America/Managua",
    "Central Standard Time - Regina (GMT-06:00)" => "America/Regina",
    "Central Standard Time - Swift Current (GMT-06:00)" => "America/Swift_Current",
    "Central Standard Time - Tegucigalpa (GMT-06:00)" => "America/Tegucigalpa",
    "Easter Island Time (GMT-06:00)" => "Pacific/Easter",
    "Galapagos Time (GMT-06:00)" => "Pacific/Galapagos",
    "Mexican Pacific Time - Chihuahua (GMT-06:00)" => "America/Chihuahua",
    "Mexican Pacific Time - Mazatlan (GMT-06:00)" => "America/Mazatlan",
    "Mountain Time - Boise (GMT-06:00)" => "America/Boise",
    "Mountain Time - Cambridge Bay (GMT-06:00)" => "America/Cambridge_Bay",
    "Mountain Time - Denver (GMT-06:00)" => "America/Denver",
    "Mountain Time - Edmonton (GMT-06:00)" => "America/Edmonton",
    "Mountain Time - Inuvik (GMT-06:00)" => "America/Inuvik",
    "(Mountain Time - Ojinaga (GMT-06:00)" => "America/Ojinaga",
    "Mountain Time - Yellowknife (GMT-06:00)" => "America/Yellowknife",
    "Acre Standard Time - Eirunepe (GMT-05:00)" => "America/Eirunepe",
    "Acre Standard Time - Rio Branco (GMT-05:00)" => "America/Rio_Branco",
    "Central Time - Bahia Banderas (GMT-05:00)" => "America/Bahia_Banderas",
    "Central Time - Beulah, North Dakota (GMT-05:00)" => "America/North_Dakota/Beulah",
    "Central Time - Center, North Dakota (GMT-05:00)" => "America/North_Dakota/Center",
    "Central Time - Chicago (GMT-05:00)" => "America/Chicago",
    "Central Time - Knox, Indiana (GMT-05:00)" => "America/Indiana/Knox",
    "Central Time - Matamoros (GMT-05:00)" => "America/Matamoros",
    "Central Time - Menominee (GMT-05:00)" => "America/Menominee",
    "Central Time - Merida (GMT-05:00)" => "America/Merida",
    "Central Time - Mexico City (GMT-05:00)" => "America/Mexico_City",
    "Central Time - Monterrey (GMT-05:00)" => "America/Monterrey",
    "Central Time - New Salem, North Dakota (GMT-05:00)" => "America/North_Dakota/New_Salem",
    "Central Time - Rainy River (GMT-05:00)" => "America/Rainy_River",
    "Central Time - Rankin Inlet (GMT-05:00)" => "America/Rankin_Inlet",
    "Central Time - Resolute (GMT-05:00)" => "America/Resolute",
    "Central Time - Tell City, Indiana (GMT-05:00)" => "America/Indiana/Tell_City",
    "Central Time - Winnipeg (GMT-05:00)" => "America/Winnipeg",
    "Colombia Standard Time (GMT-05:00)" => "America/Bogota",
    "Eastern Standard Time - Atikokan (GMT-05:00)" => "America/Atikokan",
    "Eastern Standard Time - Cancun (GMT-05:00)" => "America/Cancun",
    "Eastern Standard Time - Jamaica (GMT-05:00)" => "America/Jamaica",
    "Eastern Standard Time - Panama (GMT-05:00)" => "America/Panama",
    "Ecuador Time (GMT-05:00)" => "America/Guayaquil",
    "Peru Standard Time (GMT-05:00)" => "America/Lima",
    "Amazon Standard Time - Boa Vista (GMT-04:00)" => "America/Boa_Vista",
    "Amazon Standard Time - Campo Grande (GMT-04:00)" => "America/Campo_Grande",
    "Amazon Standard Time - Cuiaba (GMT-04:00)" => "America/Cuiaba",
    "Amazon Standard Time - Manaus (GMT-04:00)" => "America/Manaus",
    "Amazon Standard Time - Porto Velho (GMT-04:00)" => "America/Porto_Velho",
    "Atlantic Standard Time - Barbados (GMT-04:00)" => "America/Barbados",
    "Atlantic Standard Time - Blanc-Sablon (GMT-04:00)" => "America/Blanc-Sablon",
    "Atlantic Standard Time - Curacao (GMT-04:00)" => "America/Curacao",
    "Atlantic Standard Time - Martinique (GMT-04:00)" => "America/Martinique",
    "Atlantic Standard Time - Port of Spain (GMT-04:00)" => "America/Port_of_Spain",
    "Atlantic Standard Time - Puerto Rico (GMT-04:00)" => "America/Puerto_Rico",
    "Atlantic Standard Time - Santo Domingo (GMT-04:00)" => "America/Santo_Domingo",
    "Bolivia Time (GMT-04:00)" => "America/La_Paz",
    "Chile Time (GMT-04:00)" => "America/Santiago",
    "Cuba Time (GMT-04:00)" => "America/Havana",
    "Eastern Time - Detroit (GMT-04:00)" => "America/Detroit",
    "Eastern Time - Grand Turk (GMT-04:00)" => "America/Grand_Turk",
    "Eastern Time - Indianapolis (GMT-04:00)" => "America/Indiana/Indianapolis",
    "Eastern Time - Iqaluit (GMT-04:00)" => "America/Iqaluit",
    "Eastern Time - Louisville (GMT-04:00)" => "America/Kentucky/Louisville",
    "Eastern Time - Marengo, Indiana (GMT-04:00)" => "America/Indiana/Marengo",
    "Eastern Time - Monticello, Kentucky (GMT-04:00)" => "America/Kentucky/Monticello",
    "Eastern Time - Nassau (GMT-04:00)" => "America/Nassau",
    "Eastern Time - New York (GMT-04:00)" => "America/New_York",
    "Eastern Time - Nipigon (GMT-04:00)" => "America/Nipigon",
    "Eastern Time - Pangnirtung (GMT-04:00)" => "America/Pangnirtung",
    "Eastern Time - Petersburg, Indiana (GMT-04:00)" => "America/Indiana/Petersburg",
    "Eastern Time - Port-au-Prince (GMT-04:00)" => "America/Port-au-Prince",
    "Eastern Time - Thunder Bay (GMT-04:00)" => "America/Thunder_Bay",
    "Eastern Time - Toronto (GMT-04:00)" => "America/Toronto",
    "Eastern Time - Vevay, Indiana (GMT-04:00)" => "America/Indiana/Vevay",
    "Eastern Time - Vincennes, Indiana (GMT-04:00)" => "America/Indiana/Vincennes",
    "Eastern Time - Winamac, Indiana (GMT-04:00)" => "America/Indiana/Winamac",
    "Guyana Time (GMT-04:00)" => "America/Guyana",
    "Paraguay Time (GMT-04:00)" => "America/Asuncion",
    "Venezuela Time (GMT-04:00)" => "America/Caracas",
    "Argentina Standard Time - Buenos Aires (GMT-03:00)" => "America/Argentina/Buenos_Aires",
    "Argentina Standard Time - Catamarca (GMT-03:00)" => "America/Argentina/Catamarca",
    "Argentina Standard Time - Cordoba (GMT-03:00)" => "America/Argentina/Cordoba",
    "Argentina Standard Time - Jujuy (GMT-03:00)" => "America/Argentina/Jujuy",
    "Argentina Standard Time - La Rioja (GMT-03:00)" => "America/Argentina/La_Rioja",
    "Argentina Standard Time - Mendoza (GMT-03:00)" => "America/Argentina/Mendoza",
    "Argentina Standard Time - Rio Gallegos (GMT-03:00)" => "America/Argentina/Rio_Gallegos",
    "Argentina Standard Time - Salta (GMT-03:00)" => "America/Argentina/Salta",
    "Argentina Standard Time - San Juan (GMT-03:00)" => "America/Argentina/San_Juan",
    "Argentina Standard Time - San Luis (GMT-03:00)" => "America/Argentina/San_Luis",
    "Argentina Standard Time - Tucuman (GMT-03:00)" => "America/Argentina/Tucuman",
    "Argentina Standard Time - Ushuaia (GMT-03:00)" => "America/Argentina/Ushuaia",
    "Atlantic Time - Bermuda (GMT-03:00)" => "Atlantic/Bermuda",
    "Atlantic Time - Glace Bay (GMT-03:00)" => "America/Glace_Bay",
    "Atlantic Time - Goose Bay (GMT-03:00)" => "America/Goose_Bay",
    "Atlantic Time - Halifax (GMT-03:00)" => "America/Halifax",
    "Atlantic Time - Moncton (GMT-03:00)" => "America/Moncton",
    "Atlantic Time - Thule (GMT-03:00)" => "America/Thule",
    "Brasilia Standard Time - Araguaina (GMT-03:00)" => "America/Araguaina",
    "Brasilia Standard Time - Bahia (GMT-03:00)" => "America/Bahia",
    "Brasilia Standard Time - Belem (GMT-03:00)" => "America/Belem",
    "Brasilia Standard Time - Fortaleza (GMT-03:00)" => "America/Fortaleza",
    "Brasilia Standard Time - Maceio (GMT-03:00)" => "America/Maceio",
    "Brasilia Standard Time - Recife (GMT-03:00)" => "America/Recife",
    "Brasilia Standard Time - Santarem (GMT-03:00)" => "America/Santarem",
    "Brasilia Standard Time - Sao Paulo (GMT-03:00)" => "America/Sao_Paulo",
    "Chile Time (GMT-03:00)" => "America/Santiago",
    "Falkland Islands Standard Time (GMT-03:00)" => "Atlantic/Stanley",
    "French Guiana Time (GMT-03:00)" => "America/Cayenne",
    "Palmer Time (GMT-03:00)" => "Antarctica/Palmer",
    "Punta Arenas Time (GMT-03:00)" => "America/Punta_Arenas",
    "Rothera Time (GMT-03:00)" => "Antarctica/Rothera",
    "Suriname Time (GMT-03:00)" => "America/Paramaribo",
    "Uruguay Standard Time (GMT-03:00)" => "America/Montevideo",
    "Newfoundland Time (GMT-02:30)" => "America/St_Johns",
    "Fernando de Noronha Standard Time (GMT-02:00)" => "America/Noronha",
    "South Georgia Time (GMT-02:00)" => "Atlantic/South_Georgia",
    "St. Pierre & Miquelon Time (GMT-02:00)" => "America/Miquelon",
    "West Greenland Time (GMT-02:00)" => "America/Nuuk",
    "Cape Verde Standard Time (GMT-01:00)" => "Atlantic/Cape_Verde",
    "Azores Time (GMT+00:00)" => "Atlantic/Azores",
    "Coordinated Universal Time (GMT+00:00)" => "UTC",
    "East Greenland Time (GMT+00:00)" => "America/Scoresbysund",
    "Greenwich Mean Time (GMT+00:00)" => "Etc/GMT",
    "Greenwich Mean Time - Abidjan (GMT+00:00)" => "Africa/Abidjan",
    "Greenwich Mean Time - Accra (GMT+00:00)" => "Africa/Accra",
    "Greenwich Mean Time - Bissau (GMT+00:00)" => "Africa/Bissau",
    "Greenwich Mean Time - Danmarkshavn (GMT+00:00)" => "America/Danmarkshavn",
    "Greenwich Mean Time - Monrovia (GMT+00:00)" => "Africa/Monrovia",
    "Greenwich Mean Time - Reykjavik (GMT+00:00)" => "Atlantic/Reykjavik",
    "Greenwich Mean Time - Sao Tome (GMT+00:00)" => "Africa/Sao_Tome",
    "Central European Standard Time - Algiers (GMT+01:00)" => "Africa/Algiers",
    "Central European Standard Time - Tunis (GMT+01:00)" => "Africa/Tunis",
    "Ireland Time (GMT+01:00)" => "Europe/Dublin",
    "Morocco Time (GMT+01:00)" => "Africa/Casablanca",
    "United Kingdom Time (GMT+01:00)" => "Europe/London",
    "West Africa Standard Time - Lagos (GMT+01:00)" => "Africa/Lagos",
    "West Africa Standard Time - Ndjamena (GMT+01:00)" => "Africa/Ndjamena",
    "Western European Time - Canary (GMT+01:00)" => "Atlantic/Canary",
    "Western European Time - Faroe (GMT+01:00)" => "Atlantic/Faroe",
    "Western European Time - Lisbon (GMT+01:00)" => "Europe/Lisbon",
    "Western European Time - Madeira (GMT+01:00)" => "Atlantic/Madeira",
    "Western Sahara Time (GMT+01:00)" => "Africa/El_Aaiun",
    "Central Africa Time - Khartoum (GMT+02:00)" => "Africa/Khartoum",
    "Central Africa Time - Maputo (GMT+02:00)" => "Africa/Maputo",
    "Central Africa Time - Windhoek (GMT+02:00)" => "Africa/Windhoek",
    "Central European Time - Amsterdam (GMT+02:00)" => "Europe/Amsterdam",
    "Central European Time - Andorra (GMT+02:00)" => "Europe/Andorra",
    "Central European Time - Belgrade (GMT+02:00)" => "Europe/Belgrade",
    "Central European Time - Berlin (GMT+02:00)" => "Europe/Berlin",
    "Central European Time - Brussels (GMT+02:00)" => "Europe/Brussels",
    "Central European Time - Budapest (GMT+02:00)" => "Europe/Budapest",
    "Central European Time - Ceuta (GMT+02:00)" => "Africa/Ceuta",
    "Central European Time - Copenhagen (GMT+02:00)" => "Europe/Copenhagen",
    "Central European Time - Gibraltar (GMT+02:00)" => "Europe/Gibraltar",
    "Central European Time - Luxembourg (GMT+02:00)" => "Europe/Luxembourg",
    "Central European Time - Madrid (GMT+02:00)" => "Europe/Madrid",
    "Central European Time - Malta (GMT+02:00)" => "Europe/Malta",
    "Central European Time - Monaco (GMT+02:00)" => "Europe/Monaco",
    "Central European Time - Oslo (GMT+02:00)" => "Europe/Oslo",
    "Central European Time - Paris (GMT+02:00)" => "Europe/Paris",
    "Central European Time - Prague (GMT+02:00)" => "Europe/Prague",
    "Central European Time - Rome (GMT+02:00)" => "Europe/Rome",
    "Central European Time - Stockholm (GMT+02:00)" => "Europe/Stockholm",
    "Central European Time - Tirane (GMT+02:00)" => "Europe/Tirane",
    "Central European Time - Vienna (GMT+02:00)" => "Europe/Vienna",
    "Central European Time - Warsaw (GMT+02:00)" => "Europe/Warsaw",
    "Central European Time - Zurich (GMT+02:00)" => "Europe/Zurich",
    "Eastern European Standard Time - Cairo (GMT+02:00)" => "Africa/Cairo",
    "Eastern European Standard Time - Kaliningrad (GMT+02:00)" => "Europe/Kaliningrad",
    "Eastern European Standard Time - Tripoli (GMT+02:00)" => "Africa/Tripoli",
    "South Africa Standard Time (GMT+02:00)" => "Africa/Johannesburg",
    "Troll Time (GMT+02:00)" => "Antarctica/Troll",
    "Arabian Standard Time - Baghdad (GMT+03:00)" => "Asia/Baghdad",
    "Arabian Standard Time - Qatar (GMT+03:00)" => "Asia/Qatar",
    "Arabian Standard Time - Riyadh (GMT+03:00)" => "Asia/Riyadh",
    "East Africa Time - Juba (GMT+03:00)" => "Africa/Juba",
    "East Africa Time - Nairobi (GMT+03:00)" => "Africa/Nairobi",
    "Eastern European Time - Amman (GMT+03:00)" => "Asia/Amman",
    "Eastern European Time - Athens (GMT+03:00)" => "Europe/Athens",
    "Eastern European Time - Beirut (GMT+03:00)" => "Asia/Beirut",
    "Eastern European Time - Bucharest (GMT+03:00)" => "Europe/Bucharest",
    "Eastern European Time - Chisinau (GMT+03:00)" => "Europe/Chisinau",
    "Eastern European Time - Damascus (GMT+03:00)" => "Asia/Damascus",
    "Eastern European Time - Gaza (GMT+03:00)" => "Asia/Gaza",
    "Eastern European Time - Hebron (GMT+03:00)" => "Asia/Hebron",
    "Eastern European Time - Helsinki (GMT+03:00)" => "Europe/Helsinki",
    "Eastern European Time - Kiev (GMT+03:00)" => "Europe/Kiev",
    "Eastern European Time - Nicosia (GMT+03:00)" => "Asia/Nicosia",
    "Eastern European Time - Riga (GMT+03:00)" => "Europe/Riga",
    "Eastern European Time - Sofia (GMT+03:00)" => "Europe/Sofia",
    "Eastern European Time - Tallinn (GMT+03:00)" => "Europe/Tallinn",
    "Eastern European Time - Uzhhorod (GMT+03:00)" => "Europe/Uzhgorod",
    "Eastern European Time - Vilnius (GMT+03:00)" => "Europe/Vilnius",
    "Eastern European Time - Zaporozhye (GMT+03:00)" => "Europe/Zaporozhye",
    "Famagusta Time (GMT+03:00)" => "Asia/Famagusta",
    "Israel Time" => "Asia/Jerusalem (GMT+03:00)",
    "Kirov Time" => "Europe/Kirov (GMT+03:00)",
    "Moscow Standard Time - Minsk (GMT+03:00)" => "Europe/Minsk",
    "Moscow Standard Time - Moscow (GMT+03:00)" => "Europe/Moscow",
    "Moscow Standard Time - Simferopol (GMT+03:00)" => "Europe/Simferopol",
    "Syowa Time (GMT+03:00)" => "Antarctica/Syowa",
    "Turkey Time (GMT+03:00)" => "Europe/Istanbul",
    "Armenia Standard Time (GMT+04:00)" => "Asia/Yerevan",
    "Astrakhan Time (GMT+04:00)" => "Europe/Astrakhan",
    "Azerbaijan Standard Time (GMT+04:00)" => "Asia/Baku",
    "Georgia Standard Time (GMT+04:00)" => "Asia/Tbilisi",
    "Gulf Standard Time (GMT+04:00)" => "Asia/Dubai",
    "Mauritius Standard Time (GMT+04:00)" => "Indian/Mauritius",
    "Reunion Time (GMT+04:00)" => "Indian/Reunion",
    "Samara Standard Time (GMT+04:00)" => "Europe/Samara",
    "Saratov Time (GMT+04:00)" => "Europe/Saratov",
    "Seychelles Time (GMT+04:00)" => "Indian/Mahe",
    "Ulyanovsk Time (GMT+04:00)" => "Europe/Ulyanovsk",
    "Volgograd Standard Time (GMT+04:00)" => "Europe/Volgograd",
    "Afghanistan Time (GMT+04:30)" => "Asia/Kabul",
    "Iran Time (GMT+04:30)" => "Asia/Tehran",
    "French Southern & Antarctic Time (GMT+05:00)" => "Indian/Kerguelen",
    "Maldives Time (GMT+05:00)" => "Indian/Maldives",
    "Mawson Time (GMT+05:00)" => "Antarctica/Mawson",
    "Pakistan Standard Time (GMT+05:00)" => "Asia/Karachi",
    "Tajikistan Time (GMT+05:00)" => "Asia/Dushanbe",
    "Turkmenistan Standard Time (GMT+05:00)" => "Asia/Ashgabat",
    "Uzbekistan Standard Time - Samarkand (GMT+05:00)" => "Asia/Samarkand",
    "Uzbekistan Standard Time - Tashkent (GMT+05:00)" => "Asia/Tashkent",
    "West Kazakhstan Time - Aqtau (GMT+05:00)" => "Asia/Aqtau",
    "West Kazakhstan Time - Aqtobe (GMT+05:00)" => "Asia/Aqtobe",
    "West Kazakhstan Time - Atyrau (GMT+05:00)" => "Asia/Atyrau",
    "West Kazakhstan Time - Oral (GMT+05:00)" => "Asia/Oral",
    "West Kazakhstan Time - Qyzylorda (GMT+05:00)" => "Asia/Qyzylorda",
    "Yekaterinburg Standard Time (GMT+05:00)" => "Asia/Yekaterinburg",
    "Indian Standard Time - Colombo (GMT+05:30)" => "Asia/Colombo",
    "Indian Standard Time - Kolkata (GMT+05:30)" => "Asia/Kolkata",
    "Nepal Time (GMT+05:45)" => "Asia/Kathmandu",
    "Bangladesh Standard Time (GMT+06:00)" => "Asia/Dhaka",
    "Bhutan Time (GMT+06:00)" => "Asia/Thimphu",
    "East Kazakhstan Time - Almaty (GMT+06:00)" => "Asia/Almaty",
    "East Kazakhstan Time - Kostanay (GMT+06:00)" => "Asia/Qostanay",
    "Indian Ocean Time (GMT+06:00)" => "Indian/Chagos",
    "Kyrgyzstan Time (GMT+06:00)" => "Asia/Bishkek",
    "Omsk Standard Time (GMT+06:00)" => "Asia/Omsk",
    "Urumqi Time (GMT+06:00)" => "Asia/Urumqi",
    "Vostok Time (GMT+06:00)" => "Antarctica/Vostok",
    "Cocos Islands Time (GMT+06:30)" => "Indian/Cocos",
    "Myanmar Time (GMT+06:30)" => "Asia/Yangon",
    "Barnaul Time (GMT+07:00)" => "Asia/Barnaul",
    "Christmas Island Time (GMT+07:00)" => "Indian/Christmas",
    "Davis Time (GMT+07:00)" => "Antarctica/Davis",
    "Hovd Standard Time (GMT+07:00)" => "Asia/Hovd",
    "Indochina Time - Bangkok (GMT+07:00)" => "Asia/Bangkok",
    "Indochina Time - Ho Chi Minh City (GMT+07:00)" => "Asia/Ho_Chi_Minh",
    "Krasnoyarsk Standard Time - Krasnoyarsk (GMT+07:00)" => "Asia/Krasnoyarsk",
    "Krasnoyarsk Standard Time - Novokuznetsk (GMT+07:00)" => "Asia/Novokuznetsk",
    "Novosibirsk Standard Time (GMT+07:00)" => "Asia/Novosibirsk",
    "Tomsk Time (GMT+07:00)" => "Asia/Tomsk",
    "Western Indonesia Time - Jakarta (GMT+07:00)" => "Asia/Jakarta",
    "Western Indonesia Time - Pontianak (GMT+07:00)" => "Asia/Pontianak",
    "Australian Western Standard Time - Casey (GMT+08:00)" => "Antarctica/Casey",
    "Australian Western Standard Time - Perth (GMT+08:00)" => "Australia/Perth",
    "Brunei Darussalam Time (GMT+08:00)" => "Asia/Brunei",
    "Central Indonesia Time (GMT+08:00)" => "Asia/Makassar",
    "China Standard Time - Macao (GMT+08:00)" => "Asia/Macau",
    "China Standard Time - Shanghai (GMT+08:00)" => "Asia/Shanghai",
    "Hong Kong Standard Time (GMT+08:00)" => "Asia/Hong_Kong",
    "Irkutsk Standard Time (GMT+08:00)" => "Asia/Irkutsk",
    "Malaysia Time - Kuala Lumpur (GMT+08:00)" => "Asia/Kuala_Lumpur",
    "Malaysia Time - Kuching (GMT+08:00)" => "Asia/Kuching",
    "Philippine Standard Time (GMT+08:00)" => "Asia/Manila",
    "Singapore Standard Time (GMT+08:00)" => "Asia/Singapore",
    "Taipei Standard Time (GMT+08:00)" => "Asia/Taipei",
    "Ulaanbaatar Standard Time - Choibalsan (GMT+08:00)" => "Asia/Choibalsan",
    "Ulaanbaatar Standard Time - Ulaanbaatar (GMT+08:00)" => "Asia/Ulaanbaatar",
    "Australian Central Western Standard Time (GMT+08:45)" => "Australia/Eucla",
    "East Timor Time (GMT+09:00)" => "Asia/Dili",
    "Eastern Indonesia Time (GMT+09:00)" => "Asia/Jayapura",
    "Japan Standard Time (GMT+09:00)" => "Asia/Tokyo",
    "Korean Standard Time - Pyongyang (GMT+09:00)" => "Asia/Pyongyang",
    "Korean Standard Time - Seoul (GMT+09:00)" => "Asia/Seoul",
    "Palau Time" => "Pacific/Palau (GMT+09:00)",
    "Yakutsk Standard Time - Chita (GMT+09:00)" => "Asia/Chita",
    "Yakutsk Standard Time - Khandyga (GMT+09:00)" => "Asia/Khandyga",
    "Yakutsk Standard Time - Yakutsk (GMT+09:00)" => "Asia/Yakutsk",
    "Australian Central Standard Time (GMT+09:30)" => "Australia/Darwin",
    "Central Australia Time - Adelaide (GMT+09:30)" => "Australia/Adelaide",
    "Central Australia Time - Broken Hill (GMT+09:30)" => "Australia/Broken_Hill",
    "Australian Eastern Standard Time - Brisbane (GMT+10:00)" => "Australia/Brisbane",
    "Australian Eastern Standard Time - Lindeman (GMT+10:00)" => "Australia/Lindeman",
    "Chamorro Standard Time (GMT+10:00)" => "Pacific/Guam",
    "Chuuk Time (GMT+10:00)" => "Pacific/Chuuk",
    "Dumont-dUrville Time (GMT+10:00)" => "Antarctica/DumontDUrville",
    "Eastern Australia Time - Currie (GMT+10:00)" => "Australia/Currie",
    "Eastern Australia Time - Hobart (GMT+10:00)" => "Australia/Hobart",
    "Eastern Australia Time - Melbourne (GMT+10:00)" => "Australia/Melbourne",
    "Eastern Australia Time - Sydney (GMT+10:00)" => "Australia/Sydney",
    "Papua New Guinea Time (GMT+10:00)" => "Pacific/Port_Moresby",
    "Vladivostok Standard Time - Ust-Nera (GMT+10:00)" => "Asia/Ust-Nera",
    "Vladivostok Standard Time - Vladivostok (GMT+10:00)" => "Asia/Vladivostok",
    "Lord Howe Time (GMT+10:30)" => "Australia/Lord_Howe",
    "Bougainville Time (GMT+11:00)" => "Pacific/Bougainville",
    "Kosrae Time (GMT+11:00)" => "Pacific/Kosrae",
    "Macquarie Island Time (GMT+11:00)" => "Antarctica/Macquarie",
    "Magadan Standard Time (GMT+11:00)" => "Asia/Magadan",
    "New Caledonia Standard Time (GMT+11:00)" => "Pacific/Noumea",
    "Norfolk Island Time (GMT+11:00)" => "Pacific/Norfolk",
    "Ponape Time (GMT+11:00)" => "Pacific/Pohnpei",
    "Sakhalin Standard Time (GMT+11:00)" => "Asia/Sakhalin",
    "Solomon Islands Time (GMT+11:00)" => "Pacific/Guadalcanal",
    "Srednekolymsk Time (GMT+11:00)" => "Asia/Srednekolymsk",
    "Vanuatu Standard Time (GMT+11:00)" => "Pacific/Efate",
    "Anadyr Standard Time (GMT+12:00)" => "Asia/Anadyr",
    "Fiji Time (GMT+12:00)" => "Pacific/Fiji",
    "Gilbert Islands Time (GMT+12:00)" => "Pacific/Tarawa",
    "Marshall Islands Time - Kwajalein (GMT+12:00)" => "Pacific/Kwajalein",
    "Marshall Islands Time - Majuro (GMT+12:00)" => "Pacific/Majuro",
    "Nauru Time (GMT+12:00)" => "Pacific/Nauru",
    "New Zealand Time (GMT+12:00)" => "Pacific/Auckland",
    "Petropavlovsk-Kamchatski Standard Time (GMT+12:00)" => "Asia/Kamchatka",
    "Tuvalu Time (GMT+12:00)" => "Pacific/Funafuti",
    "Wake Island Time (GMT+12:00)" => "Pacific/Wake",
    "Wallis & Futuna Time (GMT+12:00)" => "Pacific/Wallis",
    "Chatham Time (GMT+12:45)" => "Pacific/Chatham",
    "Apia Time (GMT+13:00)" => "Pacific/Apia",
    "Phoenix Islands Time (GMT+13:00)" => "Pacific/Enderbury",
    "Tokelau Time (GMT+13:00)" => "Pacific/Fakaofo",
    "Tonga Standard Time (GMT+13:00)" => "Pacific/Tongatapu",
    "Line Islands Time (GMT+14:00)" => "Pacific/Kiritimati",
  ];

}
