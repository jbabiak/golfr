<?php

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\Markup;
use Drupal\miniorange_oauth_client\Utilities;

/**
 * Class for handling upgrade plans tab.
 */
class MiniorangeLicensing extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'miniorange_oauth_client_licensing';
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
          "miniorange_oauth_client/miniorange_oauth_client.module",
          "miniorange_oauth_client/miniorange_oauth_client.Vtour",
          "miniorange_oauth_client/miniorange_oauth_client.main",
          "core/drupal.dialog.ajax"
        ],
      ],
    ];
    $module_path = \Drupal::service('extension.list.module')->getPath("miniorange_oauth_client");
    if (!Utilities::isCustomerRegistered()) {
      $username = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email');
      $URL_Redirect_std = "https://login.xecurify.com/moas/login?username=" . $username . "&redirectUrl=https://login.xecurify.com/moas/initializepayment&requestOrigin=drupal8_oauth_client_standard_plan";
      $URL_Redirect_pre = "https://login.xecurify.com/moas/login?username=" . $username . "&redirectUrl=https://login.xecurify.com/moas/initializepayment&requestOrigin=drupal8_oauth_client_premium_plan";
      $URL_Redirect_ent = "https://login.xecurify.com/moas/login?username=" . $username . "&redirectUrl=https://login.xecurify.com/moas/initializepayment&requestOrigin=drupal8_oauth_client_enterprise_plan";
      $targetBlank = 'target="_blank"';
    }
    else {
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_redi_upgrade', '1')->save();
      $URL_Redirect_std = $base_url . '/admin/config/people/miniorange_oauth_client/customer_setup';
      $URL_Redirect_pre = $base_url . '/admin/config/people/miniorange_oauth_client/customer_setup';
      $URL_Redirect_ent = $base_url . '/admin/config/people/miniorange_oauth_client/customer_setup';
      $targetBlank = '';
    }
    $linkText = 'Upgrade Now';
    $form['header_top_style_2'] = [
      '#markup' => '<div class="mo_oauth_table_layout_1"><div class="mo_oauth_table_layout">',
    ];

    $form['markup_1'] = [
      '#markup' => '<br><h3>UPGRADE PLANS</h3><br><hr>',
    ];
    $rows = [[
      Markup::create(t('<a href="#edit-miniorange-oauth-login-feature-list">Feature Comparison</a>')),
      Markup::create(t('<a href="#what_is_instance">What is an Instance?</a>')),
      Markup::create(t('<a href="#edit-miniorange-oauth-how-to-upgrade-table">Upgrade Steps</a>')),
      Markup::create(t('<a href="#faq">Frequently Asked Questions</a>')),
      Markup::create(t('<a href="#video">Premium Videos</a>')),
      Markup::create(t('<a href="#payment_method">Payment Methods</a>')),
    ],
    ];

    $form['miniorange_oauth_client_topnav'] = [
      '#type' => 'table',
      '#responsive' => TRUE,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#attributes' => ['class' => ['mo_topnav_bar']],
    ];

    $features = [
          [Markup::create(t('<h3>FEATURES / PLANS</h3>')),
            Markup::create(t('<br><h2>FREE</h2><p class="mo_oauth_pricing-rate"></p><br><br><br><br><br><a class="button" disabled>Current Plan</a>')),
            Markup::create(t('<br><h2>STANDARD</h2><p class="mo_oauth_pricing-rate" id="standard_price"><sup>$</sup>249</p><p id="standard_discount"></p>
                    <div class="container-inline"><label for="instances_standard">Instances*</label>&nbsp;&nbsp;
                    <select id="instances_standard" name="instances" onchange="Instance_Pricing(this.value, instances_premium, instances_enterprise)">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="10+">10+</option>
                </select></div><br>
                <a id="sta-upgrade-btn" class="button button--primary" target="' . $targetBlank . '" href="' . $URL_Redirect_std . '">' . $linkText . '</a>'
              )),
            Markup::create(t('<br><h2>PREMIUM</h2><p class="mo_oauth_pricing-rate" id="premium_price"><sup>$</sup>399</p><p id="premium_discount"></p>
                    <div class="container-inline"><label for="instances_premium">Instances*</label>&nbsp;&nbsp;
                    <select id="instances_premium" name="instances" onchange="Instance_Pricing(this.value,instances_standard,instances_enterprise)">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="10+">10+</option>
                </select></div><br>
                <a id="pre-upgrade-btn" class="button button--primary" target="' . $targetBlank . '" href="' . $URL_Redirect_pre . '">' . $linkText . '</a>'
            )),
            Markup::create(t('<br><h2>ENTERPRISE</h2><p class="mo_oauth_pricing-rate" id="enterprise_price"><sup>$</sup>449</p><p id="enterprise_discount"></p>
                    <div class="container-inline"><label for="instances_enterprise">Instances*</label>&nbsp;&nbsp;
                    <select id="instances_enterprise" name="instances" onchange="Instance_Pricing(this.value,instances_standard,instances_premium)">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="10+">10+</option>
                </select></div><br>
                <a id="ent-upgrade-btn" class="button button--primary" target="' . $targetBlank . '" href="' . $URL_Redirect_ent . '">' . $linkText . '</a>'
            )),
          ],

          [Markup::create(t('Multiple OAuth Provider Support **')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Autofill OAuth servers configuration')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Basic Attribute Mapping')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],

          [Markup::create(t('Export Configuration')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Support Authorization Code Grant')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],

          [Markup::create(t('Support for Authorization Code Grant with PKCE, Password Grant & Implicit Grant')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Auto Create Users')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],

          [Markup::create(t('Import Configuration')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Advanced Attribute Mapping (Username, Email, First Name, Custom Attributes, etc.)')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Custom Redirect URL after login and logout')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Basic Role Mapping (Support for default role for new users)')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Advanced Role Mapping')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Force authentication / Protect complete site')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('OpenID Connect Support')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Customized Login Button')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Support for Headless integration')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Domain specific registration')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Dynamic Callback URL')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Support for Group Info Endpoint')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
          [Markup::create(t('Login Reports / Analytics')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('')), Markup::create(t('&#x2714;'))],
    ];

    $form['miniorange_oauth_login_feature_list'] = [
      '#type' => 'table',
      '#responsive' => TRUE,
      '#rows' => $features,
      '#size' => 5,
      '#attributes' => ['class' => 'mo_upgrade_plans_features'],
    ];

    $form['miniorage_oauth_client_instance_based'] = [
      '#markup' => t('<br><br><div class="mo_oauth_client_highlight_background_note_3" id="what_is_instance"><h5>* What is an Instance ?</h5>
                 <p>A Drupal instance refers to a single installation of a Drupal site. It refers to each individual website where the module is active. In the case of multisite/subsite Drupal setup, each site with a separate database will be counted as a single instance. For eg. If you have the dev-staging-prod type of environment then you will require 3 licenses of the module (with additional discounts applicable on pre-production environments). Contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> for bulk discounts.</div><br><br>'),
    ];

    $rows = [
          [Markup::create(t('<b>1.</b> Click on Upgrade Now button for required licensed plan and you will be redirected to miniOrange login console.</li>')), Markup::create(t('<b>5.</b> Uninstall and then delete the free version of the module from your Drupal site.'))],
          [Markup::create(t('<b>2.</b> Enter your username and password with which you have created an account with us. After that you will be redirected to payment page.')), Markup::create(t('<b>6.</b> Now install the downloaded licensed version of the module.'))],
          [Markup::create(t('<b>3.</b> Enter your card details and proceed for payment. On successful payment completion, the Licensed version module(s) will be available to download.')), Markup::create(t('<b>7.</b> Clear Drupal Cache from <a href="' . $base_url . '/admin/config/development/performance" >here</a>.'))],
          [Markup::create(t('<b>4.</b> Download the licensed module(s) from Module Releases and Downloads section.')), Markup::create(t('<b>8.</b> After enabling the licensed version of the module, login using the account you have registered with us.'))],
    ];

    $form['miniorange_oauth_how_to_upgrade_table'] = [
      '#type' => 'table',
      '#responsive' => TRUE,
      '#header' => [
        'how_to_upgrade' => [
          'data' => 'HOW TO UPGRADE TO LICENSED VERSION MODULE',
          'colspan' => 2,
        ],
      ],
      '#rows' => $rows,
      '#attributes' => ['style' => 'border:groove', 'class' => ['mo_how_to_upgrade']],
    ];

    $form['miniorange_oauth_client_faq'] = [
      '#markup' => t('<html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <!-- Main Style -->
        </head>
        <body>

<!--FAQ-->
<div class="container mo-container" id="faq">
  <h3 id="faq-heading" >Frequently Asked Questions</h3><br><hr><br>
  <div class= "row">
    <div class="col-md-6">

    <button type="button" class="collapsible">Are the Licenses Perpetual?</button>
    <div class="content">
      <p>The modules licenses are perpetual and includes 12 months of free maintenance (version updates). You can renew maintenance after 12 months at the current license cost.</p>
    </div>

    <button type="button" class="collapsible">Does miniOrange Offer Technical Support?</button>
    <div class="content">
      <p>Yes, we provide 24*7 support for all and any issues you might face while using the module, which includes technical support from our developers. You can get prioritized support based on the Support Plan you have opted.</p>
    </div>

    </div>
    <div class="col-md-6">

    <button type="button" class="collapsible">What is the Refund Policy?</button>
    <div class="content">
      <p>
      At miniOrange, we want to ensure you are 100% happy with your purchase. If the module that you purchased is not working as advertised and you\'ve attempted to resolve any issues with our support team, which couldn\'t get resolved, we will refund the whole amount given that you raised a refund request within the first 10 days of the purchase. Please email us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> for any queries regarding the return policy or contact us <a href="https://www.miniorange.com/contact" target="_blank">here</a>.</p>
    </div>

    <button type="button" class="collapsible">Does miniOrange store any User data ?</button>
    <div class="content">
      <p>MiniOrange does not store or transfer any data which is coming from the OAuth / OIDC provider to the Drupal. All the data remains within your premises / server.</p>
    </div>

    </div>

  </div>

</div>
<!-- FAQ End-->

<!--Watch Premium Version Features -->

<div class="container mo-container" id="video">

<h3>WATCH PREMIUM VERSION FEATURES</h3><br><hr>

<div class="row">
<div class="col-md-4 " id="premium_video">
   <p>
    <h4>Attribute Mapping</h4>
    <a href="https://youtu.be/FnrtWxzbNjk" target="_blank"><img alt=" Drupal Oauth Client Attribute Mapping" class="center" height="295" src="' . $base_url . '/' . $module_path . '/includes/images/attribute_mapping.png" width="400" /></a>
   </p>
</div>
<div class="col-md-4">
  <p>
    <h4>Role Mapping</h4>
    <a href="https://youtu.be/1E_uk1RDoMw" target="_blank"><img alt=" Drupal OAuth Client Role Mapping" class="center" height="295" src="' . $base_url . '/' . $module_path . '/includes/images/role_mapping.png" width="400" /></a>
  </p>
</div>
</div>

</div>

<!--End of Watch Premium Version Features-->

<!--Supported Payment methods-->

<div class="container mo-container payment_method_main_divs" id="payment_method">
    <h3 style="text-align: center; margin:3%;">PAYMENT METHODS</h3><hr><br><br>
    <div class="row">
    <div class="col-md-3 payment_method_inner_divs">
        <div><img src="' . $base_url . '/' . $module_path . '/includes/images/card_payment.png" width="120" ></div><hr>
        <p>If the payment is made through Credit Card/International Debit Card, the license will be created automatically once the payment is completed.</p>
    </div>
    <div class="col-md-3 payment_method_inner_divs">
        <div><img src="' . $base_url . '/' . $module_path . '/includes/images/bank_transfer.png" width="150" ></div><hr>
        <p>If you want to use bank transfer for the payment then contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> so that we can provide you the bank details.</p>
    </div>
   </div>
</div>

<!-- End of supported payment methods-->

    </body>
    </html>'),
    ];
    $form['markup_6'] = [
      '#markup' => '<p>
            <br><b>**</b> There is an additional cost for the OAuth Providers if the number of OAuth Provider is more than 1.</p><br>
        ',
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
