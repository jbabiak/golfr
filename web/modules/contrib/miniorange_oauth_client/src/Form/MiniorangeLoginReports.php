<?php

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\Utilities;

/**
 * Class for handling login reports tab.
 */
class MiniorangeLoginReports extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_reports';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $url_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client') . '/includes/images';

    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "miniorange_oauth_client/miniorange_oauth_client.admin",
          "miniorange_oauth_client/miniorange_oauth_client.style_settings",
          "miniorange_oauth_client/miniorange_oauth_client.mo_tooltip",
          "core/drupal.dialog.ajax",
        ],
      ],
    ];

    $form['header_top_style_1'] = ['#markup' => '<div class="mo_oauth_table_layout_1"><div class="mo_oauth_table_layout">'];

    $form['markup_login_reports'] = [
      '#type' => 'fieldset',
      '#title' => t('Login Reports '. Utilities::getTooltipIcon('', 'Available in the Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip')),
      '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],
    ];

    $form['markup_login_reports']['miniorange_oauth_client_report'] = [
      '#type' => 'table',
      '#header' => ['Username', 'Status', 'Application', 'Date and Time', 'Email', 'IP Address', 'Navigation URL'],
      '#empty' => t('This feature is available in the <a href="' . $base_url . '/admin/config/people/miniorange_oauth_client/licensing">Enterprise </a>version.'),
      '#prefix' => '<br><hr><br>',
      '#suffix' => '</div>',
    ];

    Utilities::moOauthShowCustomerSupportIcon($form, $form_state);

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
