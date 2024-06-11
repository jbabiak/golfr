<?php

namespace Drupal\miniorange_oauth_client\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\miniorange_oauth_client\Utilities;
use Symfony\Component\HttpFoundation\Response;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\formBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for this project.
 */
class miniorange_oauth_clientController extends ControllerBase {

  /**
   * The formbuilder property.
   *
   * @var Drupal\Core\Form\formBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a new miniorange_oauth_clientController object.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get("form_builder")
      );
  }

  /**
   * Implements OAuth2.0 SSO flow.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Returns response object.
   */
  public function miniorange_oauth_client_mo_login() {

    if (!isset($_GET["code"])) {
      Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Code is not set in the URL. Get parameters: <pre><code>' . print_r($_GET, TRUE) . '</code></pre>');
      Utilities::showErrorMessage($_GET);
    }

    $code  = Html::escape($_GET['code']);
    $state = isset($_GET['state']) ? $_GET['state'] : '';
    $state = Html::escape($state);

    if (session_id() == '' || !isset($_SESSION)) {
      session_start();
    }

    if (empty($code)) {
      if (isset($_GET['error_description'])) {
        exit($_GET['error_description']);
      }
      elseif (isset($_GET['error'])) {
        exit($_GET['error']);
      }
      exit('Invalid response');
    }

    // Getting Access Token.
    $app = [];
    $app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_appval');
    $name_attr = "";
    $email_attr = "";
    $name = "";
    $email = "";
  
    if (isset($app['miniorange_oauth_client_email_attr'])) {
      $email_attr = trim($app['miniorange_oauth_client_email_attr']);
    }
    if (isset($app['miniorange_oauth_client_name_attr'])) {
      $name_attr = trim($app['miniorange_oauth_client_name_attr']);
    }

    $parse_from_header = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_send_with_header_oauth');
    $parse_from_body = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_send_with_body_oauth');

    if (!$parse_from_header) {
      $parse_from_header = FALSE;
    }
    if (!$parse_from_body) {
      $parse_from_body = FALSE;
    }

    $accessToken = self::getAccessToken($app['access_token_ep'], 'authorization_code', $app['client_id'], $app['client_secret'], $code, $app['callback_uri'], $parse_from_header, $parse_from_body);
    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Access Token received: ' . $accessToken);
    if (!$accessToken) {
      print_r('Invalid token received.');
      exit;
    }
    $resourceownerdetailsurl = $app['user_info_ep'];
    if (substr($resourceownerdetailsurl, -1) == "=") {
      $resourceownerdetailsurl .= $accessToken;
    }

    $resourceOwner = self::getResourceOwner($resourceownerdetailsurl, $accessToken);
    $flattenResourceOwner = is_array($resourceOwner) ? self::flattenArray($resourceOwner) : [];
    /*
     *   Test Configuration
     */
    if (isset($_COOKIE['Drupal_visitor_mo_oauth_test']) && ($_COOKIE['Drupal_visitor_mo_oauth_test'] == TRUE)) {
      setrawcookie('Drupal.visitor.' . 'mo_oauth_test', '' , \Drupal::time()->getRequestTime() - 1, '/');
      setrawcookie('Drupal.visitor.' . 'mo.oauth.redirect.url', '' , \Drupal::time()->getRequestTime() - 1, '/');
      $module_path = \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client');
      $username = $resourceOwner['email'] ?? ($resourceOwner['mail'] ?? 'User');
      $someattrs = '';
      Utilities::showAttr($flattenResourceOwner, $someattrs, '<tr style="text-align:center;">', "<td style='font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;'>");
      $resourceOwner_encoded = json_encode($flattenResourceOwner);
      $configFactory = \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings');
      $configFactory->set('miniorange_oauth_client_attr_list_from_server', $resourceOwner_encoded)->save();
      echo '<div style="font-family:Calibri;padding:0 3%;">';
      if(!empty($resourceOwner)){
        echo '<div style="display:block;text-align:center;margin-bottom:4%;">
                        <img style="width:15%;"src="' . $module_path . '/includes/images/green_check.png">
                      </div>';

        echo '<span style="font-size:13pt;"><b>Hello</b>, ' . $username . '</span><br><br><div style="background-color:#dff0d8;padding:1%;">Your Test Connection is successful. Now, follow the below steps to complete the last step of your configuration:</div><span style="font-size:13pt;"><br><b></b>Please select the <b>Attribute Name</b> in which you are getting <b>Email ID.</b><br><br></span><div style="background-color: #dddddd; margin-left: 2%; margin-right: 3%">';
      }else{
        Utilities::showErrorMessage(['error' => 'No Attributes received from OAuth Server']);
      }

      self::miniorangeOauthClientUpdateEmailUsernameAttribute($flattenResourceOwner);
      $configFactory->set('miniorange_auth_client_test_configuration_status', 'Successful')->save();
      echo '<br>&emsp;<i style="font-size: small">You can also map the Username attribute from the Attribute and Role Mapping tab in the module.</i><br><br></div>
                    <br><i>Click on the <b>Done</b> button to save your changes.</i><br>';

      echo '<div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;
                            border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;
                            box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="save_and_done();"></div>
                    <script>
                        function close_and_redirect(){
                            window.opener.redirect_to_attribute_mapping();
                            self.close();
                        }
                        function redirect_to_attribute_mapping(){
                            var baseurl = window.location.href.replace("config_clc","mapping");
                            window.location.href= baseurl;
                          }

                        function save_and_done(){
                          var email_attr = document.getElementById("mo_oauth_email_attribute").value;
                          var index = window.location.href.indexOf("?");
                          var url = window.location.href.slice(0,index).replace("mo_login","mo_post_testconfig/?field_selected="+email_attr);
                          window.opener.location.href= url;
                          self.close();
                        }
                    </script>';

      echo '<p><b> ATTRIBUTES RECEIVED:</b></p><table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:13pt;background-color:#EDEDED;">
                          <tr style="text-align:center;">
                              <td style="font-weight:bold;border:2px solid #949090;padding:2%;width: fit-content;">ATTRIBUTE NAME</td>
                              <td style="font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;">ATTRIBUTE VALUE</td>
                          </tr>';
      echo $someattrs;
      echo '</table></div>';

      return new Response();
      exit();
    }

    if (!empty($email_attr)) {
      $email = $flattenResourceOwner[$email_attr];
    }
    if (!empty($name_attr)) {
      $name = $flattenResourceOwner[$name_attr];
    }
    
    global $base_url;
    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Email Attribute: ' . $email);
    /*************==============Attributes not mapped check===============************/
    if (empty($email)) {
      Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Email is empty.');
      echo '<div style="font-family:Calibri;padding:0 3%;">';
      echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div><div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>Email address does not received.</p><p>Check your <b>Attribute Mapping</b> configuration.</p><p><strong>Possible Cause: </strong>Email Attribute field is not configured.</p></div><div style="margin:3%;display:block;text-align:center;"></div><div style="margin:3%;display:block;text-align:center;"><form action="' . $base_url . '" method ="post"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="submit" value="Done"></form></div>';
      exit;
      return new Response();
    }
    // Validates the email format.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      echo '<div style="font-family:Calibri;padding:0 3%;">';
      echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div><div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>Invalid email format of the received value.</p><p>Check your <b>Attribute Mapping</b> configuration.</p><p><strong>Possible Cause: </strong>Email Attribute field is incorrectly configured.</p></div><div style="margin:3%;display:block;text-align:center;"></div><div style="margin:3%;display:block;text-align:center;"><form action="' . $base_url . '" method ="post"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="submit" value="Done"></form></div>';
      exit;
      return new Response();
    }

    $account = '';
    if (!empty($email)) {
      $account = user_load_by_mail($email);
    }
    if ($account == NULL) {
      if (!empty($name) && isset($name)) {
        $account = user_load_by_name($name);
      }
    }
    
    global $user;

    if (!isset($account->uid)) {
      Utilities::setSsoStatus('Successful - User does not exists.');

      Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'User does not exists.');
      echo '<div style="font-family:Calibri;padding:0 3%;">';
      echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div><div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>User Not Found in Drupal.</p><p>You can only log in the existing Drupal users in this version of the module.<br><br>Please upgrade to either the <a href="https://plugins.miniorange.com/drupal-sso-oauth-openid-single-sign-on#features" target="_blank">Standard, Premium or the Enterprise </a> version of the module in order to create unlimited new users.</p></div><div style="margin:3%;display:block;text-align:center;"></div><div style="margin:3%;display:block;text-align:center;"><form action="' . $base_url . '" method ="post"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="submit" value="Done"></form></div>';
      exit;
      return new Response();
    }
    $user = User::load($account->id());

    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'SSO user ID: ' . $account->id());
    user_login_finalize($account);

    Utilities::setSsoStatus('Successful - User logged in');

    $finalRedirectUrl = self::finalRedirectUrlAfterLogin();
    setrawcookie('Drupal.visitor.' . 'mo.oauth.redirect.url', '' , \Drupal::time()->getRequestTime() - 1, '/');

    $response = new RedirectResponse($finalRedirectUrl);
    return $response;
  }

  /**
   * Saves email attr selected after test config.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Returns response object
   */
  public function mo_post_testconfig() {
    $email_attr = $_GET['field_selected'];
    $config = \Drupal::config('miniorange_oauth_client.settings');
    $app_name = $config->get('miniorange_auth_client_app_name');
    $app_values = $config->get('miniorange_oauth_client_appval');
    $app_values['miniorange_oauth_client_email_attr'] = $email_attr;
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_appval', $app_values)->save();
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_email_attr_val', $email_attr)->save();

    \Drupal::messenger()->addMessage(t('Configurations saved successfully. Please go to your Drupal site’s login page where you will automatically find a <b> Login with ' . $app_name . ' </b> link.'));

    global $base_url;
    $response = new RedirectResponse($base_url . "/admin/config/people/miniorange_oauth_client/mapping");
    return $response;
  }

  /**
   * Checks final redirect url after sso
   *
   * @return string
   *   Return reirect url.
   */
  public static function finalRedirectUrlAfterLogin(){
    global $base_url;
    if (isset($_COOKIE['Drupal_visitor_mo_oauth_redirect_url']) && !empty($_COOKIE['Drupal_visitor_mo_destination_parameter_redirect'])){
      $final_redirection_UrlValue = $_COOKIE['Drupal_visitor_mo_oauth_redirect_url'];
    }

    if (!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url'))) {
      $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
    }
    else {
      $baseUrlValue = $base_url;
    }

    return $final_redirection_UrlValue ?? $baseUrlValue;
  }

  /**
   * Makes API request to get an access token from OAuth server.
   *
   * @param string $tokenendpoint
   *   The tokenendpoint.
   * @param string $grant_type
   *   The client_id.
   * @param string $clientid
   *   The client secret.
   * @param string $clientsecret
   *   The client_secret.
   * @param string $code
   *   The authorization_code received from authorization endpoint.
   * @param string $redirect_url
   *   The callback/redirect url.
   * @param bool $send_headers
   *   Option to send client credentials in header.
   * @param bool $send_body
   *   Option to send client credentials in body.
   *
   * @return string
   *   Returns access token received from OAuth server.
   */
  public function getAccessToken($tokenendpoint, $grant_type, $clientid, $clientsecret, $code, $redirect_url, $send_headers, $send_body) {

    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Access Token flow initiated.');
    if ($send_headers && !$send_body) {
      $response = Utilities::callService($tokenendpoint,
            'redirect_uri=' . urlencode($redirect_url) . '&grant_type=' . $grant_type . '&code=' . $code,
            [
              'Authorization' => 'Basic ' . base64_encode($clientid . ":" . $clientsecret),
              'Accept' => 'application/json',
              'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        );
    }
    elseif (!$send_headers && $send_body) {
      $response = Utilities::callService($tokenendpoint,
            'redirect_uri=' . urlencode($redirect_url) . '&grant_type=' . $grant_type . '&client_id=' . urlencode($clientid) . '&client_secret=' . urlencode($clientsecret) . '&code=' . $code,
            [
              'Accept' => 'application/json',
              'Content-Type' => 'application/x-www-form-urlencoded',
            ]
            );
    }
    else {
      $response = Utilities::callService($tokenendpoint,
            'redirect_uri=' . urlencode($redirect_url) . '&grant_type=' . $grant_type . '&client_id=' . urlencode($clientid) . '&client_secret=' . urlencode($clientsecret) . '&code=' . $code,
            [
              'Authorization' => 'Basic ' . base64_encode($clientid . ":" . $clientsecret),
              'Accept' => 'application/json',
              'Content-Type' => 'application/x-www-form-urlencoded',
            ]
            );
    }

    $content = JSON::decode($response);
    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Access Token Content: <pre><code>' . print_r($content, TRUE) . '</code></pre>');

    if (isset($content["error"]) || isset($content["error_description"])) {

      if (!isset($_COOKIE['Drupal_visitor_mo_oauth_test'])) {
        Utilities::setSsoStatus('Tried and failed - Token endpoint - <pre>' . $content . '</pre>');
      }

      if (isset($content["error"]) && is_array($content["error"])) {
        $content["error"] = $content["error"]["message"];
      }
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_access_token_status', Json::encode($content))->save();
      Utilities::showErrorMessage($content);
    }
    elseif (isset($content["access_token"])) {
      if (!isset($_COOKIE['Drupal_visitor_mo_oauth_test'])) {
        Utilities::setSsoStatus('Successfully received Access token');
      }
      $access_token = $content["access_token"];
    }
    else {
      exit('Invalid response received from OAuth Provider. Contact your administrator for more details.');
    }

    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_access_token_status', Json::encode($content))->save();

    return $access_token;
  }

  /**
   * Makes API request to get resourceowner details from userinfo endpoint.
   *
   * @param string $resourceownerdetailsurl
   *   The userinfo endpoint.
   * @param string $access_token
   *   The access token received from token endpoint.
   *
   * @return array
   *   Returns userinfo array.
   */
  public function getResourceOwner($resourceownerdetailsurl, $access_token) {

    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Userinfo flow initiated.');
    $response = Utilities::callService($resourceownerdetailsurl,
          NULL,
          ['Authorization' => 'Bearer ' . $access_token],
          'GET'
      );

    $content = JSON::decode($response);

    if (isset($content["error"]) || isset($content["error_description"])) {
      if (!isset($_COOKIE['Drupal_visitor_mo_oauth_test'])) {
        Utilities::setSsoStatus('Tried and failed - Userinfo endpoint - <pre>' . $content . '</pre>');
      }

      Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Userinfo Content: <pre><code>' . print_r($content, TRUE) . '</code></pre>');
      if (isset($content["error"]) && is_array($content["error"])) {
        $content["error"] = $content["error"]["message"];
      }
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_userinfo_status', Json::encode($content))->save();
      Utilities::showErrorMessage($content);
    }
    if (!isset($_COOKIE['Drupal_visitor_mo_oauth_test'])) {
      Utilities::setSsoStatus('User info received successfully.');
    }
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_userinfo_status', 'Userinfo received successfully.')->save();
    return $content;
  }

  /**
   * Initiates login and redirects to authorization endpoint.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Returns response object.
   */
  public static function mo_oauth_client_initiateLogin() {
    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Login using SSO Initiated.');
    global $base_url;

    isset($_SERVER['HTTP_REFERER']) ? $redirect_url = $_SERVER['HTTP_REFERER'] : $redirect_url = $base_url;
    setrawcookie('Drupal.visitor.' . 'mo.oauth.redirect.url', $redirect_url , \Drupal::time()->getRequestTime() + 3900, '/');

    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Redirect URL set to: ' . $redirect_url);
    $app_name = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
    $client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
    $client_secret = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_secret');
    $scope = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_scope');
    $authorizationUrl = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_authorize_endpoint');
    $access_token_ep = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_access_token_ep');
    $user_info_ep = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_user_info_ep');

    if ($app_name == NULL||$client_secret == NULL||$client_id == NULL||$scope == NULL||$authorizationUrl == NULL||$access_token_ep == NULL||$user_info_ep == NULL) {
      Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Configurations could not be found.');
      echo '<div style="font-family:Calibri;padding:0 3%;">';
      echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div><div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>OAuth Server configurations could not be found.</p><p>Check your <b>OAuth Server</b> configuration.</p><p><strong>Possible Cause: </strong>OAuth Server configurations are not completed.</p></div><div style="margin:3%;display:block;text-align:center;"></div><div style="margin:3%;display:block;text-align:center;"></div>';
      exit;
      return new Response();
    }

    $callback_uri = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_callback_uri');
    $state = base64_encode($app_name);
    if (strpos($authorizationUrl, '?') !== FALSE) {
      $authorizationUrl = $authorizationUrl . "&client_id=" . $client_id . "&scope=" . $scope . "&redirect_uri=" . $callback_uri . "&response_type=code&state=" . $state;
    }
    else {
      $authorizationUrl = $authorizationUrl . "?client_id=" . $client_id . "&scope=" . $scope . "&redirect_uri=" . $callback_uri . "&response_type=code&state=" . $state;
    }
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
    Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Authorization URL: ' . $authorizationUrl);
    $_SESSION['oauth2state'] = $state;
    $_SESSION['appname'] = $app_name;
    $response = new RedirectResponse($authorizationUrl);
    $response->send();
    return new Response();
  }

  /**
   * Performs test configuration.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Returns response object.
   */
  public function test_mo_config() {
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_test_configuration_status', 'Tried and failed')->save();
    user_cookie_save(["mo_oauth_test" => TRUE]);
    self::mo_oauth_client_initiateLogin();
    return new Response();
  }

  /**
   * Displays ajax form of authorization code grant description.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function showauthorizationcodegrantdescription() {
    $response = new AjaxResponse();
    $grant['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('<b>Authorization Code Grant is used by web and mobile applications. It requires the client to exchange authorization code with OAuth server for access token.</b>'),
    ];
    $grant['know_more'] = [
      '#markup' => '<b><a href="https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-authorization-code-grant" target="_blank">Know more</a> about this grant type.</b> ',
    ];
    $ajax_form = new OpenModalDialogCommand('Authorization Code Grant', $grant, ['width' => '40%']);
    $response->addCommand($ajax_form);
    return $response;
  }

  /**
   * Displays ajax form of authorization code grant with PKCE description.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function showauthcodewithpkceflowdescription() {
    $response = new AjaxResponse();
    $grant['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('<b>Authorization Code Grant with PKCE is an extension of the standard Authorization Code Grant flow. It is considered best for Single Page Apps (SPA) or Mobile Apps. Client Secret is not required while using PKCE flow.</b>'),
    ];
    $grant['know_more'] = [
      '#markup' => '<b><a href="https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-authorization-code-grant" target="_blank">Know more</a> about this grant type.</b> ',
    ];
    $ajax_form = new OpenModalDialogCommand('Authorization Code with PKCE', $grant, ['width' => '40%']);
    $response->addCommand($ajax_form);
    return $response;
  }

  /**
   * Displays ajax form of Password grant description.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function showpasswordgrantdescription() {
    $response = new AjaxResponse();
    $grant['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('<b>Password Grant is used by applications to exchange user' . 's credentials for access token. This, generally, should be used by internal applications.</b>'),
    ];
    $grant['know_more'] = [
      '#markup' => '<b><a href="https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-password-grant" target="_blank">Know more</a> about this grant type.</b> ',
    ];

    $ajax_form = new OpenModalDialogCommand('Password Grant', $grant, ['width' => '40%']);
    $response->addCommand($ajax_form);
    return $response;
  }

  /**
   * Displays ajax form of Implicit grant description.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function showimplicitgrantdescription() {
    $response = new AjaxResponse();
    $grant['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('<b>The Implicit Grant is a simplified version of Authorization Code Grant flow. OAuth providers directly offer access token after authenticating user when using this grant type.</b>'),
    ];
    $grant['know_more'] = [
      '#markup' => '<b><a href="https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-implicit-grant" target="_blank">Know more</a> about this grant type.</b> ',
    ];
    $ajax_form = new OpenModalDialogCommand('Implicit Grant', $grant, ['width' => '40%']);
    $response->addCommand($ajax_form);
    return $response;
  }

  /**
   * Displays Trial Request form.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function openDemoRequestForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_oauth_client\Form\MoOAuthRequestDemo');
    $response->addCommand(new OpenModalDialogCommand('Request 7-Days Full Feature Trial License', $modal_form, ['width' => '60%']));
    return $response;
  }

  /**
   * Displays Remove Account confirmation form.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function openRemoveAccountForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_oauth_client\Form\MiniorangeOAuthClientRemoveAccount');
    $response->addCommand(new OpenModalDialogCommand('Remove Account', $modal_form, ['width' => '800']));
    return $response;
  }

  /**
   * Displays Customer Support form.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function openCustomerRequestForm() {
    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm('\Drupal\miniorange_oauth_client\Form\MoOAuthCustomerRequest');
    $response->addCommand(new OpenModalDialogCommand('Contact miniOrange Support', $modal_form, ['width' => '45%']));
    return $response;
  }

  /**
   * Displays add new provider advertise.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Returns ajaxresponse object.
   */
  public function openaddnewproviderform() {
    $response = new AjaxResponse();
    $provider_info['add_new_provider_info'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>You can configure only 1 application in free version of the module. Multiple OAuth/OpenID Providers are supported in <a href="licensing">ENTERPRISE</a> version of module</p>'),
    ];
    $ajax_form = new OpenModalDialogCommand('Add New OAuth/OpenID Provider', $provider_info, ['width' => '40%']);
    $response->addCommand($ajax_form);
    return $response;
  }

  /**
   * Starts SSO.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse|Symfony\Component\HttpFoundation\Response
   *   Return redirectresponse or response object.
   */
  public static function miniorange_oauth_client_mologin() {
    global $base_url;
    user_cookie_save(["mo_oauth_test" => FALSE]);
    $enable_login = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_enable_login_with_oauth');
    if ($enable_login) {
      Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Login using SSO Enabled.');
      self::mo_oauth_client_initiateLogin();
      return new Response();
    }
    else {
      Utilities::addLogger(basename(__FILE__), __FUNCTION__, __LINE__, 'Login using SSO Disabled.');
      \Drupal::messenger()->addMessage(t('Please enable <b>Login with OAuth</b> to initiate the SSO.'), 'error');
      return new RedirectResponse($base_url);
    }
  }

  /**
   * Updates email and username attribute.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Returns response object.
   */
  public function miniorangeOauthClientUpdateEmailUsernameAttribute($data) {
    $options = '';
    $selected_flag = 0;
    foreach ($data as $key => $value) {
      if ($selected_flag == 0 && $key == 'email') {
        $options = $options . '<option value="email" selected> email </option>';
        $selected_flag = 1;
      }
      elseif ($selected_flag == 0 && $key == 'mail') {
        $options = $options . '<option value="mail" selected> mail </option>';
        $selected_flag = 1;
      }
      elseif ($selected_flag == 0 && $key == 'email > 0') {
        $options = $options . '<option value="email > 0" selected> email > 0 </option>';
        $selected_flag = 1;
      }
      elseif ($selected_flag == 0 && $key == 'emails') {
        $options = $options . '<option value="emails" selected> emails </option>';
        $selected_flag = 1;
      }
      elseif ($selected_flag == 0 && $key == 'emails > 0') {
        $options = $options . '<option value="emails > 0" selected> emails > 0 </option>';
        $selected_flag = 1;
      }
      else {
        $options = $options . '<option value="' . $key . '"> ' . $key . ' </option>';
      }
    }

    $html_string = '<p style="display: inline-block;">&emsp;<b> Email Attribute </b></p> &nbsp;&nbsp;&nbsp; <select id="mo_oauth_email_attribute" style="height: 32px;">' . $options . '</select>
                        &nbsp;&nbsp;&nbsp; <input style="display: none;" id="miniorange_oauth_client_other_field_for_email" placeholder="Enter Email Attribute">';

    echo $html_string . '';
    return new Response();
  }

  /**
   * Flattens nested attrs in userinfo array received from OAuth Server.
   */
  public static function flattenArray($array, $prefix = '') {
    $result = [];
    foreach ($array as $key => $value) {
      $newKey = $prefix . $key;
      if (is_array($value)) {
        $result = array_merge($result, self::flattenArray($value, $newKey . ' > '));
      }
      else {
        $result[$newKey] = $value;
      }
    }
    return $result;
  }

}
