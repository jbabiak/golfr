<?php

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\Utilities;

/**
 * Class for handling signin settings tab.
 */
class Settings extends FormBase {

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
    $url_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client') . '/includes/images';
    $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');

    $attachments['#attached']['library'][] = 'miniorange_oauth_client/miniorange_oauth_client.admin';

    $form['markup_library'] = [
         '#attached' => [
                'library' => [
                    "miniorange_oauth_client/miniorange_oauth_client.admin",
                    "miniorange_oauth_client/miniorange_oauth_client.style_settings",
                    "miniorange_oauth_client/miniorange_oauth_client.mo_tooltip",
                    "core/drupal.dialog.ajax"
                ]
            ],
    ];

    $form['header_top_style_1'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

    $form['markup_top'] = [
      '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_signinsettings">',
    ];

    $module_path = \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client');

    $form['markup_custom_troubleshoot'] = [
      '#type' => 'fieldset',
      '#title' => t('Debugging & Troubleshoot'),
    ];

    $form['markup_custom_troubleshoot']['miniorange_oauth_client_enable_logging'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Logging'),
      '#default_value' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_enable_logging'),
      '#description' => 'Enabling this checkbox will add loggers under the <a href="' . $base_url . '/admin/reports/dblog?type%5B%5D=miniorange_oauth_client" target="_blank">Reports</a> section',
      '#prefix' => '<hr>',
    ];

    $form['markup_custom_troubleshoot']['miniorange_oauth_client_siginin1'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Save Configuration'),
      '#submit' => ['::troubleshootsubmitForm'],

    ];

    $form['markup_custom_troubleshoot']['miniorange_oauth_client_enable_logging_download'] = [
      '#type' => 'submit',
      '#value' => t('Download Module Logs'),
      '#limit_validation_errors' => [],
      '#states' => [
        'disabled' => [
          ':input[name="miniorange_oauth_client_enable_logging"]' => ['checked' => FALSE],
        ],
      ],
      '#submit' => ['::miniorangeModuleLogs'],
    ];

    $form['markup_custom_auto_create_user'] = [
      '#type' => 'details',
      '#title' => t('Auto Create Users '. Utilities::getTooltipIcon('', 'Available in the Standard, Premium and Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip')),
      '#open' => true
    ];

    $form['markup_custom_auto_create_user']['markup_bottom_vt_start_auto_create_users'] = [
      '#markup' => '<p>This feature provides you with an option to automatically create a user if the user is not already present in Drupal</p>',
    ];

    $form['markup_custom_auto_create_user']['miniorange_oauth_disable_autocreate_users'] = [
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to enable <b>auto creation</b> of users if user does not exist.'),
      '#disabled' => TRUE,
    ];

    $form['markup_custom_signin'] = [
      '#type' => 'details',
      '#title' => t('Page Restriction '. Utilities::getTooltipIcon('', 'Available in the Premium and Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip'). '<a class="mo_oauth_client_how_to_setup" style="float:right;"href="https://developers.miniorange.com/docs/oauth-drupal/sign-in-settings#domain-restriction" target="_blank">[What is Page restriction and How to Set up]</a>'),
      '#open' => true,
    ];

    $form['markup_custom_signin']['miniorange_oauth_client_force_auth'] = [
      '#type' => 'checkbox',
      '#title' => t('Protect website against anonymous access '),
      '#disabled' => TRUE,
      '#default_value' => true,
      '#description' => t('<b>Note: </b>Users will be redirected to your OAuth server for login in case user is not logged in and tries to access website.<br><br>'),
    ];

    $form['markup_custom_signin']['miniorange_oauth_set_of_page_restriction'] = array(
      '#type' => 'fieldset',
      '#states' => array(
      // Only show this field when the checkbox is enabled.
      'visible' => array(
          ':input[name="miniorange_oauth_client_force_auth"]' => array('checked' => TRUE),),),
    );

    $types_of_page_restrictions = ['whitelist_pages' => 'Pages to exclude from restriction','restrict_pages' => 'Pages to be restricted'];

    $form['markup_custom_signin']['miniorange_oauth_set_of_page_restriction']['choose_type_of_page_restriction'] = array(
      '#type' => 'radios',
      '#options' => $types_of_page_restrictions,
      '#attributes' => array('class' => array('container-inline'),),
      '#default_value' => 'whitelist_pages'
    );

    $form['markup_custom_signin']['miniorange_oauth_set_of_page_restriction']['markup_desc_page_restriction'] = array(
      '#markup' => '<p>Enter the <b>line seperated relative URLs</b>. For instace, If the site url is <b>https://www.xyz.com/yyy</b> then the relative URL would be <b>/yyy</b>.
      <ul>
        <li>If you want to restrict/allow access to particular <b>\'/abc/pqr/xyz\'</b> route then use <b>\'/abc/pqr/xyz\'</b>. </li>
        <li>You also have the option to use the <b>\'*\'</b> wildcard in URLs to manage page access. For instance, to restrict/allow access to all routes under <b>\'/abc\'</b>, use the wildcard URL <b>\'/abc/*\'</b>.</li>
      </ul></p>',
    );


    $form['markup_custom_signin']['miniorange_oauth_set_of_page_restriction']['miniorange_oauth_page_whitelist_urls'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages to exclude from auto-redirect to OAuth Provider for anonymous users'),
      '#attributes' => array('style' => 'width:640px;height:80px; background-color: hsla(0,0%,0%,0.08) !important',
      'placeholder' => 'Enter the list of semicolon separated relative URLs of your pages in the textarea.'),
      '#description' => t('<b>Note:&nbsp;</b>Users can access these pages anonymously.<b>&nbsp;Keep this textarea empty if you want to restrict all pages of your site</b>'),
      '#disabled' => true,
      '#resizable' => FALSE,
      '#states' => array(
        // Only show this field when the checkbox is enabled.
        'visible' => array(
            ':input[name="choose_type_of_page_restriction"]' => array('value' => 'whitelist_pages'),),
       ),
      '#suffix' => '<br>',
    );

    $form['markup_custom_signin']['miniorange_oauth_set_of_page_restriction']['miniorange_oauth_page_restrict_urls'] = array(
      '#type' => 'textarea',
      '#title' => t('Pagess to be restricted'),
      '#attributes' => array('style' => 'width:640px;height:80px; background-color: hsla(0,0%,0%,0.08) !important',
      'placeholder' => 'Enter the list of semicolon separated relative URLs of your pages in the textarea.'),
      '#description' => t('<b>Note:&nbsp;</b>Users will be redirected to your OAuth Server for login when the restricted page is accessed.<b>&nbsp;Only these pages will be restricted and all other pages can be accessed anonymously</b>'),
      '#disabled' => true,
      '#resizable' => FALSE,
      '#states' => array(
        // Only show this field when the checkbox is enabled.
        'visible' => array(
            ':input[name="choose_type_of_page_restriction"]' => array('value' => 'restrict_pages'),),
       ),
      '#suffix' => '<br>',
    );

    $form['markup_custom_signin']['miniorange_oauth_auto_redirect'] = [
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to <b> Auto-redirect to OAuth Provider/Server </b>'),
      '#disabled' => TRUE,
      '#description' => t('<b>Note: </b>Users will be redirected to your OAuth server for login when the login page is accessed.<br><br>'),
    ];

    $form['markup_custom_signin']['miniorange_oauth_enable_backdoor'] = [
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to enable <b>backdoor login </b>'),
      '#disabled' => TRUE,
      '#description' => t('<b>Note:</b> Checking this option creates a backdoor to login to your Website using Drupal credentials incase you get locked out of your OAuth server.'),
    ];

    $form['markup_custom_signin1'] = [
      '#type' => 'details',
      '#title' => t('Domain Restriction '.Utilities::getTooltipIcon('', 'Available in the Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip').'<a class="mo_oauth_client_how_to_setup" style="float:right;"href="https://developers.miniorange.com/docs/oauth-drupal/sign-in-settings#domain-restriction" target="_blank">[What is Domain restriction and How to Set up]</a>'),
      '#open' => true,
    ];

    $form['markup_custom_signin1']['miniorange_oauth_domain_restriction_checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => t("Check this option if you want <b> Domain Restriction</b>"),
      '#default_value' => true,
      '#disabled' => TRUE,

    );

    $form['markup_custom_signin1']['miniorange_oauth_set_of_radiobuttons'] = array(
      '#type' => 'fieldset',
      '#states' => array(
        // Only show this field when the checkbox is enabled.
        'visible' => array(
          ':input[name="miniorange_oauth_domain_restriction_checkbox"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['markup_custom_signin1']['miniorange_oauth_set_of_radiobuttons']['miniorange_oauth_allow_or_block_domains']=array(
      '#type'=>'radios',
      '#maxlength' => 5,
      '#options' => array('allowed' => 'I want to allow only some of the domains','block' => 'I want to block some of the domains'),
      '#disabled' => true,
    );

    $form['markup_custom_signin1']['miniorange_oauth_set_of_radiobuttons']['miniorange_oauth_domains'] = array(
      '#type' => 'textarea',
      '#title' => t('Enter list of domains'),
      '#attributes' => array('style' => 'width:580px;height:80px; background-color: hsla(0,0%,0%,0.08) !important',
      'placeholder' => 'Enter semicolon(;) separated domains (Eg. xxxx.com; xxxx.com)'),
      '#disabled' => true,
      '#suffix' => '<br>',
    );


    $form['markup_custom_login_logout'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Custom Login/Logout '. Utilities::getTooltipIcon('', 'Available in the Standard, Premium and Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip')),
    ];

    $form['markup_custom_login_logout']['custom_redirect'] = [
      '#markup' => '<p>This feature provides you with an option to redirect the users to a default page after Login and Logout</p>',
    ];

    $form['markup_custom_login_logout']['miniorange_oauth_client_login_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => t('Default redirect URL after login'),
      '#attributes' => ['placeholder' => 'Enter Default Redirect URL after login']
    ];

    $form['markup_custom_login_logout']['miniorange_oauth_client_logout_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => t('Default redirect URL after logout'),
      '#attributes' => ['placeholder' => 'Enter Default Redirect URL after logout']
    ];

    $form['markup_custom_login_button'] = [
      '#type' => 'details',
      '#title' => t('Login Button Customization '. Utilities::getTooltipIcon('', 'Available in the Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip')),
      '#open' => true
    ];

    $form['markup_custom_login_button']['miniorange_oauth_icon_width'] = [
      '#type' => 'textfield',
      '#title' => t('Icon width'),
      '#disabled' => TRUE,
      '#description' => t('For eg. 200px or 10% <br>'),
    ];

    $form['markup_custom_login_button']['miniorange_oauth_icon_height'] = [
      '#type' => 'textfield',
      '#title' => t('Icon height'),
      '#disabled' => TRUE,
      '#description' => t('For eg. 60px or auto <br>'),
    ];

    $form['markup_custom_login_button']['miniorange_oauth_icon_margins'] = [
      '#type' => 'textfield',
      '#title' => t('Icon Margins'),
      '#disabled' => TRUE,
      '#description' => t('For eg. 2px 3px or auto <br>'),
    ];

    $form['markup_custom_login_button']['miniorange_oauth_custom_css'] = [
      '#type' => 'textarea',
      '#title' => t('Custom CSS'),
      '#disabled' => TRUE,
      '#attributes' => ['style' => 'width:80%', 'placeholder' => 'For eg.  .oauthloginbutton{ background: #7272dc; height:40px; padding:8px; text-align:center; color:#fff; }'],
    ];

    $form['markup_custom_login_button']['miniorange_oauth_btn_txt'] = [
      '#type' => 'textfield',
      '#title' => t('Custom Button Text'),
      '#disabled' => TRUE,
      '#attributes' => ['placeholder' => 'Login Using appname'],
    ];
    $form['miniorange_oauth_client_siginin'] = [

      '#type' => 'button',
      '#value' => t('Save Configuration'),
      '#button_type' => 'primary',
      '#disabled' => TRUE,
      '#attributes' => ['style' => '	margin: auto; display:block; '],
    ];

    $form['mo_header_style_end'] = ['#markup' => '</div>'];
    Utilities::moOauthShowCustomerSupportIcon($form, $form_state);
    return $form;
  }

  /**
   * Submit handler for updating base url of site.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;

    $baseUrlvalue = trim($form['markup_custom_role_mapping']['miniorange_oauth_client_base_url']['#value']);
    if (!empty($baseUrlvalue) && filter_var($baseUrlvalue, FILTER_VALIDATE_URL) == FALSE) {
      \Drupal::messenger()->adderror(t('Please enter a valid URL'));
      return;
    }
    $callbackUrl = empty($baseUrlvalue) ? $base_url . "/mo_login" : $baseUrlvalue . "/mo_login";

    $configFactory = \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings');
    $configFactory->set('miniorange_oauth_client_base_url', $baseUrlvalue)
      ->set('miniorange_auth_client_callback_uri', $callbackUrl)
      ->save();
    \Drupal::messenger()->addMessage(t('Configurations saved successfully.'));
  }

  /**
   * Enables logging.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function troubleshootsubmitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $enable_logs = $form_values['miniorange_oauth_client_enable_logging'];
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_enable_logging', $enable_logs)->save();
    \Drupal::messenger()->addMessage(t('Configurations saved successfully.'));
  }

  /**
   * Filters string provided.
   *
   * @param string $str
   *   The string to be filtered.
   */
  public static function mofilterData(&$str) {
    $str = preg_replace("/\t/", "\\t", $str);
    $str = preg_replace("/\r?\n/", "\\n", $str);
    if (strstr($str, '"')) {
      $str = '"' . str_replace('"', '""', $str) . '"';
    }
  }

  /**
   * Exports module logs.
   */
  public static function miniorangeModuleLogs() {

    $connection = \Drupal::database();

    // Excel file name for download.
    $fileName = "drupal_oauth_client_loggers_" . date('Y-m-d') . ".xls";

    // Column names.
    $fields = ['WID', 'UID', 'TYPE', 'MESSAGE', 'VARIABLES', 'SEVERITY', 'LINK', 'LOCATION', 'REFERER', 'HOSTNAME', 'TIMESTAMP'];

    // Display column names as first row.
    $excelData = implode("\t", array_values($fields)) . "\n\n";

    // Fetch records from database.
    $query = $connection->query("SELECT * from {watchdog} WHERE type = 'miniorange_oauth_client' OR severity = 3")->fetchAll();

    foreach ($query as $row) {
      $lineData = [$row->wid, $row->uid, $row->type, $row->message, $row->variables, $row->severity, $row->link, $row->location, $row->referer, $row->hostname, $row->timestamp];
      array_walk($lineData, static function(&$value) {
        self::mofilterData($value);
      });

      $excelData .= implode("\t", array_values($lineData)) . "\n";
    }

    // Headers for download.
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$fileName\"");

    // Render excel data.
    echo $excelData;
    exit;
  }

}
