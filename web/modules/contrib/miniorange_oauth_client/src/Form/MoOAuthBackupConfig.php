<?php

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\miniorange_oauth_client\Utilities;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class for handling import export of module configurations.
 */
class MoOAuthBackupConfig extends FormBase {

  /**
   * The immutable config property.
   *
   * @var Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * The config property.
   *
   * @var Drupal\Core\Config\Config
   */
  protected $configFactory;

  /**
   * Constructs a new MoOAuthBackupConfig object.
   */
  public function __construct() {
    $this->config = \Drupal::config('miniorange_oauth_client.settings');
    $this->configFactory = \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings');
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'mo_oauth_client_backup_config';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#prefix'] = '<div id="modal_backup_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['button'] = [
      '#type' => 'submit',
      '#button_type' => 'danger',
      '#value' => t('&#11164; Back'),
      '#submit' => ['::backToConfigTable'],
    ];

    $form['markup_top_vt_start2'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('BACKUP/IMPORT CONFIGURATIONS'),
      '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],
    ];

    $module_path = \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client');

    $form['markup_top_vt_start2']['markup_1'] = [
      '#markup' => '<br><div class="mo_oauth_highlight_background_note"><p><b>NOTE: </b>This tab will help you to transfer your module configurations when you change your Drupal instance.
                          <br>Example: When you switch from test environment to production.<br>Follow these 3 simple steps to do that:<br>
                          <br><strong>1.</strong> Download module configuration file by clicking on the Download Configuration button given below.
                          <br><strong>2.</strong> Install the module on new Drupal instance.<br><strong>3.</strong> Upload the configuration file in Import module Configurations section.<br>
                          <br><b>And just like that, all your module configurations will be transferred!</b></p></div><br><div id="Exort_Configuration"><h3>Backup/ Export Configuration &nbsp;&nbsp;</h3><hr/><p>
                                    Click on the button below to download module configuration.</p>',
    ];

    $form['markup_top_vt_start2']['miniorange_saml_imo_option_exists_export'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Download'),
      '#submit' => ['::miniorangeImportExport'],
      '#suffix' => '<br/><br/></div>',
    ];

    $form['markup_top_vt_start2']['markup_prem_plan'] = [
      '#markup' => '<div id="Import_Configuration"><br/><h3>Import Configuration</h3><hr><br>
                          <div class="mo_oauth_highlight_background_note_1"><b>Note: </b>Available in
                                    <a href="' . $base_url . '/admin/config/people/miniorange_oauth_client/licensing">Standard, Premium and Enterprise</a> versions of the module</div>',
    ];

    $form['markup_top_vt_start2']['markup_import_note'] = [
      '#markup' => '<p>This tab will help you to<span style="font-weight: bold"> Import your module configurations</span> when you change your Drupal instance.</p>
                           <p>choose <b>"json"</b> Extened module configuration file and upload by clicking on the button given below. </p>',
    ];

    $form['markup_top_vt_start2']['import_Config_file'] = [
      '#type' => 'file',
      '#disabled' => TRUE,
    ];

    $form['markup_top_vt_start2']['miniorange_saml_idp_import'] = [
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#disabled' => TRUE,
    ];

    return $form;
  }

  /**
   * Exports module configurations.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function miniorangeImportExport(array &$form, FormStateInterface $form_state) {
    $tab_class_name = [
      'OAuth Client Configuration' => 'mo_options_enum_client_configuration',
      'Attribute Mapping' => 'mo_options_enum_attribute_mapping',
      'Sign In Settings' => 'mo_options_enum_signin_settings',
    ];

    $configuration_array = [];
    foreach ($tab_class_name as $key => $value) {
      $configuration_array[$key] = $this->moGetConfigurationArray($value);
    }

    $configuration_array["Version_dependencies"] = $this->moGetVersionInformations();
    $this->configFactory->set('miniorange_oauth_client_module_configuration', $configuration_array)->save();
    header("Content-Disposition: attachment; filename = miniorange_oauth_client_config.json");
    echo(json_encode($configuration_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    exit;
  }

  /**
   * Redirects to configuration tab.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Returns response object.
   */
  public function backToConfigTable() {
    $response = new RedirectResponse(Url::fromRoute('miniorange_oauth_client.config_clc')->toString());
    $response->send();
    return new Response();
  }

  /**
   * Creates array of fields with its config varaible value of specific tab.
   *
   * @param string $class_name
   *   The name of tab.
   */
  public function moGetConfigurationArray($class_name) {
    $class_object = Utilities::getVariableArray($class_name);
    $mo_array = [];
    foreach ($class_object as $key => $value) {
      $mo_option_exists = $this->config->get($value);
      if ($mo_option_exists) {
        $mo_array[$key] = $mo_option_exists;
      }
    }
    return $mo_array;
  }

  /**
   * Creates array php extension and module versions.
   *
   * @return array
   *   Return array version info.
   */
  public function moGetVersionInformations() {
    $array_version = [];
    $array_version["PHP_version"] = phpversion();
    $array_version["Drupal_version"] = \DRUPAL::VERSION;
    $array_version["OPEN_SSL"] = $this->moOauthIsOpensslInstalled();
    $array_version["CURL"] = $this->moOauthIsCurlInstalled();
    $array_version["ICONV"] = $this->moOauthIsIconvInstalled();
    $array_version["DOM"] = $this->moOauthIsDomInstalled();
    return $array_version;
  }

  /**
   * Checks if opessl is installed or not.
   *
   * @return int
   *   Return 1 if installed else 0.
   */
  public function moOauthIsOpensslInstalled() {
    if (in_array('openssl', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Checks if cURL is installed or not.
   *
   * @return int
   *   Return 1 if installed else 0.
   */
  public function moOauthIsCurlInstalled() {
    if (in_array('curl', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Checks if iconv is installed or not.
   *
   * @return int
   *   Return 1 if installed else 0.
   */
  public function moOauthIsIconvInstalled() {
    if (in_array('iconv', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Checks if dom is installed or not.
   *
   * @return int
   *   Return 1 if installed else 0.
   */
  public function moOauthIsDomInstalled() {
    if (in_array('dom', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
