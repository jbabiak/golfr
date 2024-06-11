<?php

/**
 * @file
 * Contains \Drupal\miniorange_oauth_client\Form\MiniorangeMapping.
 */

namespace Drupal\miniorange_oauth_client\Form;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_oauth_client\Utilities;
use Drupal\Core\Form\FormBase;

/**
 * Class for handling Mapping.
 */
class MiniorangeMapping extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_mapping';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "miniorange_oauth_client/miniorange_oauth_client.admin",
          "miniorange_oauth_client/miniorange_oauth_client.style_settings",
          "miniorange_oauth_client/miniorange_oauth_client.Vtour",
          "miniorange_oauth_client/miniorange_oauth_client.mo_tooltip",
          "core/drupal.dialog.ajax",
        ],
      ],
    ];

    $config = \Drupal::config('miniorange_oauth_client.settings');

    $form['header_top_style_1'] = ['#markup' => '<div class="mo_oauth_table_layout_1">'];

    $form['markup_top'] = [
      '#markup' => '<div class="mo_oauth_table_layout mo_oauth_container_63">',
    ];

    $form['markup_top_vt_start'] = [
      '#type' => 'container',
      '#attributes' => array( 'style' => 'padding:2% 2% 2%; margin-bottom:1%' ),
    ];

    $form['markup_top_vt_start']['mapping_title'] = [
      '#markup' => '<h3>Attribute Mapping</h3><hr><br>'
    ];

    $url_path = $base_url . '/' . \Drupal::service('extension.list.module')->getPath('miniorange_oauth_client') . '/includes/images';

    $email_attr = $config->get('miniorange_oauth_client_email_attr_val');
    $name_attr = $config->get('miniorange_oauth_client_name_attr_val');

      $attrs = $config->get('miniorange_oauth_client_attr_list_from_server');

      if(isset($attrs))
       $attrs = JSON::decode($attrs);
      $options = [];
      if (is_array($attrs)) {
        foreach ($attrs as $key => $value) {
          if (is_array($value)){
            foreach ($value as $key1 => $value1) {
              $options[$key1] = $key1;
            }
            continue;
          }
          $options[$key] = $key;
        }
      }

      $data = ['email_attr' => 'miniorange_oauth_client_email_attr_val', 'name_attr' => 'miniorange_oauth_client_name_attr_val'];

      $form['markup_top_vt_start']['basic_attribute_mapping_details'] = [
        '#type' => 'details',
        '#title' => t('Basic Attribute Mapping'),
        '#open' => true,
      ];

      $form['markup_top_vt_start']['basic_attribute_mapping_details']['mo_vt_id_start1'] = [
      '#markup' => '<div class="mo_oauth_highlight_background_note_1">Attributes are the user details that are stored by your OAuth server(s). Attribute Mapping helps you get user attributes/fields from your OAuth server and map them to your Drupal site user attributes.</div>
        <br><b>Note: </b>Please select the attribute name with email and Username from the dropdown of Received Attribute List for successful SSO.<br><br>',
      ];

      $form['markup_top_vt_start']['basic_attribute_mapping_details']['miniorange_oauth_login_mapping'] = [
          '#type' => 'table',
          '#responsive' => TRUE,
          '#header' => [
            'Attributes' => [
                'data' => 'Attributes',
                'width' => '35%'
            ],
             'Received Attribute List' => [
                 'data' => 'Received Attribute List',
                 'width' => '65%'
             ]
          ],
          '#attributes' => ['style' => 'border-collapse: separate;'],
          '#prefix' => '<div id="tour_attribute_table_id">',
          '#suffix' => '</div>'
      ];

      foreach ($data as $key => $value) {
          $row = self::miniorangeOauthClientTableDataMapping($key, $value, $options, $config);
          $form['markup_top_vt_start']['basic_attribute_mapping_details']['miniorange_oauth_login_mapping'][$key] = $row;
      }

      $form['markup_top_vt_start']['basic_attribute_mapping_details']['miniorange_oauth_client_attr_setup_button_2'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Save Configuration'),
        '#prefix' => '<br>',
        '#attributes' => ['style' => '	margin: auto; display:block; '],
        '#submit' => ['::miniorangeOauthClientAttrSetupSubmit'],
         '#id' => 'tour_save_mapping_id'
      ];

    $form['markup_top_vt_start']['markup_custom_attribute'] = [
        '#type' => 'details',
        '#open' => true,
        '#title' => t('Custom Attribute Mapping '. Utilities::getTooltipIcon('', 'Available in the Standard, Premium and Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip').'<a class="mo_oauth_client_how_to_setp" style="float: right;" href="https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/oauth-feature-handbook/user-entity-fields-mapping-oauth-oidc-login" target="_blank">[Know More]</a>'),
    ];


    $form['markup_top_vt_start']['markup_custom_attribute']['attribute_mapping_info'] = [
      '#markup' => t('<br><div class="mo_oauth_highlight_background_note_1"> In this section you can map any attribute of the OAuth Server to the Drupal user profile field.
      To add a new Drupal field go to Configuration->Account Settings -> <a href = "'.$base_url.'/admin/config/people/accounts/fields">Manage fields</a> and then click on Add field.
      <br><br><b>OAuth Server Attribute Name:</b> Select attribute name recieved from OAuth Server which you want to map with custom Drupal user profile field.
      <br><b>Drupal Machine Name:</b> Machine Name of the Drupal user profile field.</div>')
    ];

    $form['markup_top_vt_start']['markup_custom_attribute']['markup_custom_attr_mapping']['markup_idp_user_attr_header'] = array(
      '#markup' => '</br><h3><span>Add Custom Attributes  &nbsp;&nbsp;</span>',
    );

    $form['markup_top_vt_start']['markup_custom_attribute']['markup_custom_attr_mapping']['add_attr'] = array(
      '#type' => 'submit',
      '#disabled' => true,
      '#value' => t('+'),
      '#attributes' => ['class' => ['button button--small']],
      '#suffix' => '</h3>'
    );

    $form['markup_top_vt_start']['markup_custom_attribute']['markup_custom_attr_mapping']['miniorange_oauth_attr_map_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('OAuth Server Attribute Name'),
        $this->t('Drupal Machine Name'),
        '',
      ],
    ];

    $form['markup_top_vt_start']['markup_custom_attribute']['markup_custom_attr_mapping']['miniorange_oauth_attr_map_table']['attrmap_row'] = self::generateTableRow('attribute', $options);
  
    $form['markup_top_vt_start_role_mapping'] = [
      '#type' => 'container',
      '#attributes' => array('style' => 'padding:2% 2% 4%; margin-bottom:1%' ),
    ];

    $form['markup_top_vt_start_role_mapping']['role_mapping_title'] = [
      '#markup' => '<h3>Role Mapping</h3><hr><br>'
    ];

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Custom Role Mapping '. Utilities::getTooltipIcon('', 'Available in the Premium and Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip').'<a class="mo_oauth_client_how_to_setp" style="float: right;" href="https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/oauth-feature-handbook/user-role-mapping-oauth-oidc-login" target="_blank">[Know More]</a>'),
    ];

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['miniorange_enable_role_mapping'] = array(
      '#type' => 'checkbox',
      '#disabled' => true,
      '#title' => t('<b>Enable Role Mapping</b>'),
    );

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['miniorange_oauth_disable_role_update'] = array(
      '#type' => 'checkbox',
      '#disabled' => true,
      '#title' => t('Keep existing roles if roles are not mapped below'),
    );
    
    $mrole = user_role_names(TRUE);

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['miniorange_oauth_default_mapping'] = array(
      '#type' => 'select',
      '#title' => t('Select the default role for new users'),
      '#options' => $mrole,
      '#attributes' => array('style' => 'width:73%;'),
    );

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['miniorange_oauth_role_attr_name'] = array(
      '#type' => 'textfield',
      '#disabled' => true,
      '#title' => t('Role Attribute'),
      '#attributes' => array('placeholder' => 'Enter Role Attribute'),
      '#attributes' => array('style' => 'width:73%;'),
    );

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['markup_idp_user_role_header'] = array(
      '#markup' => '</br><h3><span>Role Attributes  &nbsp;&nbsp;</span>',
    );

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['add_role_attr'] = array(
      '#type' => 'submit',
      '#disabled' => true,
      '#value' => t('+'),
      '#attributes' => ['class' => ['button button--small']],
      '#suffix' => '</h3>'
    );

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['miniorange_oauth_role_map_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Drupal Role'),
        $this->t('OAuth Server/Provider Role'),
        '',
      ],
    ];

    $form['markup_top_vt_start_role_mapping']['markup_custom_role_mapping']['miniorange_oauth_role_map_table']['rolemap_row'] = self::generateTableRow('role', $mrole);

    $form['markup_top_vt_start_profile_mapping'] = [
      '#type' => 'container',
      '#attributes' => array('style' => 'padding:2% 2% 4%; margin-bottom:1%' ),
    ];

    $form['markup_top_vt_start_profile_mapping']['profile_mapping_title'] = [
      '#markup' => '<h3>Profile Mapping</h3><hr><br>'
    ];

    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Profile Module Mapping '. Utilities::getTooltipIcon('', 'Available in the Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip').'<a class="mo_oauth_client_how_to_setp" style="float: right;" href="https://developers.miniorange.com/docs/oauth-drupal/mapping#role-mapping" target="_blank">[Know More]</a>'),
    ];

    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping']['miniorange_oauth_client_enable_profile_mapping'] = array(
      '#type' => 'checkbox',
      '#title' => t('<b>Enable Profile Mapping.</b>'),
      '#disabled' => true,
    );

    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping']['miniorange_oauth_client_profile_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Profile Type'),
      '#attributes' => array('style' => 'width:66.5%;'),
      '#options' => ['Select' => 'Select Profile Type'],
      '#prefix' => '<div class ="container-inline">',
      '#disabled' => true,
    ];

    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping']['save_enitiy_type'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#attributes' => array('style' => 'margin-left: 2%;'),
      '#disabled' => true,
      '#prefix' => '<span>',
      '#suffix' => '</div></span>'
    ];

    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping']['miniorange_oauth_client_profile_mapping_add'] = [
      '#markup' => '</br><h3><span>Add Profile Mapping&nbsp;</span> ',
    ];
   
    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping']['add_profile_field'] = array(
      '#type' => 'submit',
      '#value' => $this->t('+'),
      '#disabled' => true,
      '#attributes' => ['class' => ['button button--small']],
      '#suffix' => '</h3>'  
    );

    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping']['miniorange_oauth_profile_map_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Drupal Profile field'),
        $this->t('OAuth Server Attribute Name'),
        '',
      ],
    ];

    $form['markup_top_vt_start_profile_mapping']['markup_custom_profile_mapping']['miniorange_oauth_profile_map_table']['profilemap_row'] = self::generateTableRow('profile', ['Select' => '- Select -']);

    $form['markup_top_vt_start_group_mapping'] = [
      '#type' => 'container',
      '#attributes' => array('style' => 'padding:2% 2% 4%; margin-bottom:1%' ),
    ];

    $form['markup_top_vt_start_group_mapping']['group_mapping_title'] = [
      '#markup' => '<h3>Group Mapping</h3><hr><br>'
    ];

    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Group Module Mapping '. Utilities::getTooltipIcon('', 'Available in the Enterprise version', '<a class= "licensing" href="licensing"><img class = "mo_oauth_pro_icon1" src="' . $url_path . '/pro.png" alt="Premium and Enterprise"></a>', 'mo_oauth_pro_icon_tooltip').'<a class="mo_oauth_client_how_to_setp" style="float: right;" href="https://developers.miniorange.com/docs/oauth-drupal/mapping#role-mapping" target="_blank">[Know More]</a>'),
    ];

    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping']['miniorange_oauth_client_enable_group_mapping'] = array(
      '#type' => 'checkbox',
      '#title' => t('<b>Enable Group Mapping.</b>'),
      '#disabled' => true,
    );

    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping']['mo_keep_existing_groups'] = [
      '#type' => 'checkbox',
      '#title' => t('Keep existing groups if groups not mapped below'),
      '#disabled' => true,
    ];

    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping']['mo_group_attribute'] = [
      '#type' => 'textfield',
      '#title' => t('Group Attribute'),
      '#attributes' => array('placeholder' => 'Enter Group Attribute'),
      '#disabled' => true,
    ];


    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping']['miniorange_oauth_client_group_mapping_add'] = [
      '#markup' => '</br><h3><span>Add Group Mapping&nbsp;</span> ',
    ];
   
    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping']['add_group_field'] = array(
      '#type' => 'submit',
      '#value' => $this->t('+'),
      '#disabled' => true,
      '#attributes' => ['class' => ['button button--small']],
      '#suffix' => '</h3>'  
    );

    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping']['miniorange_oauth_group_map_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Drupal Group Name'),
        $this->t('OAuth Server Group Name/ID'),
        '',
      ],
    ];

    $form['markup_top_vt_start_group_mapping']['markup_custom_group_mapping']['miniorange_oauth_group_map_table']['groupmap_row'] = self::generateTableRow('group', ['Select' => '- Select -']);

    $form['mo_header_style_end'] = ['#markup' => '</div>'];
    Utilities::showAttrListFromIdp($form, $form_state);

    $form['miniorange_idp_guide_link_end'] = [
        '#markup' => '</div>',
    ];

    Utilities::moOAuthShowCustomerSupportIcon($form, $form_state);

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Forms table row for mapping table
   */
  public static function generateTableRow($mapping, $dropdownMenu){
        
    $row['drupal_name'] = [
      '#type' => 'select',
      '#options' => $dropdownMenu,
    ];
      
    $row['oauth_server_attr_name'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#disabled' => true,
    ];

    if($mapping != 'attribute')
     $row['oauth_server_attr_name']['#attributes'] = array('placeholder' => 'semi-colon(;) separated');

    $row['delete_row'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('-'),
      '#disabled' => true,
    ];

    return $row;
  }

  /**
   * Submit handler for saving mapping configuration.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function miniorangeOauthClientAttrSetupSubmit($form, $form_state) {
    $form_values = $form_state->getValues();
    $email_attr = $form_values['miniorange_oauth_login_mapping']['email_attr']['miniorange_oauth_client_email_select'];
    $name_attr = $form_values['miniorange_oauth_login_mapping']['name_attr']['miniorange_oauth_client_username_select'];
    $app_name = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');

    $app_values = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_appval');

    $app_values['miniorange_oauth_client_name_attr'] = $name_attr;
    $app_values['miniorange_oauth_client_email_attr'] = $email_attr;


    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_email_attr_val', $email_attr)->save();
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_name_attr_val', $name_attr)->save();

    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_appval', $app_values)->save();
    \Drupal::messenger()->addMessage(t('Attribute Mapping saved successfully. Please logout and go to your Drupal site’s login page, you will automatically find a <b>Login with ' . $app_name . '</b> link there.'), 'status');
  }

  /**
   * Clears list of attrs received from server.
   *
   * @param array $form
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The formstate.
   */
  public function clearAttrList(&$form, $form_state) {
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_attr_list_from_server')->save();
    Utilities::showAttrListFromIdp($form, $form_state);
  }

  /**
   * Email and Username mapping configuration.
   *
   * @param string $key
   *   The mapping attr key.
   * @param string $value
   *   The config variable against a key.
   * @param array $options
   *   The received attrs array.
   * @param object $config
   *   The config property.
   * @return array
   *   Returns array of form elements.
   */
  public function miniorangeOauthClientTableDataMapping($key, $value, $options, $config ): array {

    if ($key == 'email_attr') {
      $row[$key] = [
        '#markup' => '<div class="mo-mapping-floating"><strong>Email Attribute: </strong></div>',
      ];

            $row['miniorange_oauth_client_email_select'] = [
                '#type' => 'select',
                '#id' => 'miniorange_oauth_client_email_select',
                '#default_value' => $config->get($value),
                '#required' => true,
                '#options' => $options,
                "#empty_option"=> $this->t('- Select -'),
            ];
        }
        else{
            $row[$key] = [
                '#markup' => '<div class="mo-mapping-floating"><strong>Username Attribute: </strong></div>',
            ];

            $row['miniorange_oauth_client_username_select'] = [
                '#type' => 'select',
                '#id' => 'miniorange_oauth_client_username_select',
                '#default_value' => $config->get($value),
                '#options' => $options,
                "#empty_option" => $this->t('- Select -'),
            ];
        }
        return $row;
    }

}
