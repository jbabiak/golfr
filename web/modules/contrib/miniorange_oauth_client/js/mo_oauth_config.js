/**
 * @file JS file to perform authentication and registration for miniOrange
 *       Authentication service.
 */


(function($) {

                        jQuery(document).ready(function() {
                            var v=document.getElementById('miniorange_oauth_client_app');
                            var i;
                            for (i = 19; i < 25; i++) {
                                v.options[i].disabled=true;
                            }

                        jQuery('#miniorange_oauth_client_app').parent().show();
                        jQuery('#miniorange_oauth_client_app').change(function()
                        {
                            var base_url = window.location.origin;
                            var baseUrl = base_url;
                            var appname = document.getElementById('miniorange_oauth_client_app').value;
                            var callbackUrl = document.getElementById('callbackurl').value;

                            document.getElementById('callbackurl').value=callbackUrl;

                    let myAppsData = [];
                    myAppsData['Azure AD']    = {'miniorange_auth_client_scope':'openid','miniorange_auth_client_authorize_endpoint':'https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/authorize','miniorange_auth_client_access_token_ep':'https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/token','miniorange_auth_client_user_info_ep':'https://graph.microsoft.com/oidc/userinfo'};
                    myAppsData['Box']         = {'miniorange_auth_client_scope':'root_readwrite','miniorange_auth_client_authorize_endpoint':'https://account.box.com/api/oauth2/authorize','miniorange_auth_client_access_token_ep':'https://api.box.com/oauth2/token','miniorange_auth_client_user_info_ep':'https://api.box.com/2.0/users/me'};
                    myAppsData['Discord']     = {'miniorange_auth_client_scope':'identify email','miniorange_auth_client_authorize_endpoint':'https://discordapp.com/api/oauth2/authorize','miniorange_auth_client_access_token_ep':'https://discordapp.com/api/oauth2/token','miniorange_auth_client_user_info_ep':'https://discordapp.com/api/users/@me'};
                    myAppsData['Facebook']    = {'miniorange_auth_client_scope':'email','miniorange_auth_client_authorize_endpoint':'https://www.facebook.com/dialog/oauth','miniorange_auth_client_access_token_ep':'https://graph.facebook.com/v2.8/oauth/access_token','miniorange_auth_client_user_info_ep':'https://graph.facebook.com/me/?fields=id,name,email,age_range,first_name,gender,last_name,link&access_token='};
                    myAppsData['FitBit']      = {'miniorange_auth_client_scope':'profile','miniorange_auth_client_authorize_endpoint':'https://www.fitbit.com/oauth2/authorize','miniorange_auth_client_access_token_ep':'https://api.fitbit.com/oauth2/token','miniorange_auth_client_user_info_ep':'https://api.fitbit.com/1/user/-/profile.json'};
                    myAppsData['GitHub']      = {'miniorange_auth_client_scope':'user repo','miniorange_auth_client_authorize_endpoint':'https://github.com/login/oauth/authorize','miniorange_auth_client_access_token_ep':'https://github.com/login/oauth/access_token','miniorange_auth_client_user_info_ep':'https://api.github.com/user'};
                    myAppsData['Google']      = {'miniorange_auth_client_scope':'email+profile','miniorange_auth_client_authorize_endpoint':'https://accounts.google.com/o/oauth2/auth','miniorange_auth_client_access_token_ep':'https://www.googleapis.com/oauth2/v4/token','miniorange_auth_client_user_info_ep':'https://www.googleapis.com/oauth2/v1/userinfo'};
                    myAppsData['Keycloak']    = {'miniorange_auth_client_scope':'email profile','miniorange_auth_client_authorize_endpoint':'{Keycloak_base_URL}/realms/{realm-name}/protocol/openid-connect/auth','miniorange_auth_client_access_token_ep':'{Keycloak_base_URL}/realms/{realm-name}/protocol/openid-connect/token','miniorange_auth_client_user_info_ep':'{Keycloak_base_URL}/realms/{realm-name}/protocol/openid-connect/userinfo'};
                    myAppsData['Line']        = {'miniorange_auth_client_scope':'Profile openid email','miniorange_auth_client_authorize_endpoint':'https://access.line.me/oauth2/v2.1/authorize','miniorange_auth_client_access_token_ep':'https://api.line.me/oauth2/v2.1/token','miniorange_auth_client_user_info_ep':'https://api.line.me/v2/profile'};
                    myAppsData['LinkedIn']    = {'miniorange_auth_client_scope':'r_basicprofile','miniorange_auth_client_authorize_endpoint':'https://www.linkedin.com/oauth/v2/authorization','miniorange_auth_client_access_token_ep':'https://www.linkedin.com/oauth/v2/accessToken','miniorange_auth_client_user_info_ep':'https://api.linkedin.com/v2/me'};
                    myAppsData['Okta']        = {'miniorange_auth_client_scope':'openid email profile','miniorange_auth_client_authorize_endpoint':'https://{yourOktaDomain}.com/oauth2/default/v1/authorize','miniorange_auth_client_access_token_ep':'https://{yourOktaDomain}.com/oauth2/default/v1/token','miniorange_auth_client_user_info_ep':'https://{yourOktaDomain}.com/oauth2/default/v1/userinfo'};
                    myAppsData['Paypal']      = {'miniorange_auth_client_scope':'openid email profile','miniorange_auth_client_authorize_endpoint':'https://www.paypal.com/signin/authorize','miniorange_auth_client_access_token_ep':'https://api.paypal.com/v1/oauth2/token','miniorange_auth_client_user_info_ep':'https://api.paypal.com/v1/identity/oauth2/userinfo'};
                    myAppsData['Salesforce']  = {'miniorange_auth_client_scope':'id','miniorange_auth_client_authorize_endpoint':'https://login.salesforce.com/services/oauth2/authorize','miniorange_auth_client_access_token_ep':'https://login.salesforce.com/services/oauth2/token','miniorange_auth_client_user_info_ep':'https://login.salesforce.com/services/oauth2/userinfo'};
                    myAppsData['Slack']       = {'miniorange_auth_client_scope':'users.profile:read','miniorange_auth_client_authorize_endpoint':'https://slack.com/oauth/authorize','miniorange_auth_client_access_token_ep':'https://slack.com/api/oauth.access','miniorange_auth_client_user_info_ep':'https://slack.com/api/users.profile.get'};
                    myAppsData['Strava']      = {'miniorange_auth_client_scope':'public','miniorange_auth_client_authorize_endpoint':'https://www.strava.com/oauth/authorize','miniorange_auth_client_access_token_ep':'https://www.strava.com/oauth/token','miniorange_auth_client_user_info_ep':'https://www.strava.com/api/v3/athlete'};
                    myAppsData['Wild Apricot']= {'miniorange_auth_client_scope':'auto','miniorange_auth_client_authorize_endpoint':'https://{your_account_url}/sys/login/OAuthLogin','miniorange_auth_client_access_token_ep':'https://oauth.wildapricot.org/auth/token','miniorange_auth_client_user_info_ep':'https://api.wildapricot.org/v2.1/accounts/{account_id}/contacts/me'};
                    myAppsData['Zendesk']     = {'miniorange_auth_client_scope':'read write','miniorange_auth_client_authorize_endpoint':'https://{subdomain}.zendesk.com/oauth/authorizations/new','miniorange_auth_client_access_token_ep':'https://{subdomain}.zendesk.com/oauth/tokens','miniorange_auth_client_user_info_ep':'https://{subdomain}.zendesk.com/api/v2/users'};
                    myAppsData['Custom']      = {'miniorange_auth_client_scope':'email profile','miniorange_auth_client_authorize_endpoint':'','miniorange_auth_client_access_token_ep':'','miniorange_auth_client_user_info_ep':''};

                    if(myAppsData[appname] != null){
                                jQuery('#miniorange_auth_client_app_name').parent().show();
                                jQuery('#miniorange_auth_client_display_name').parent().show();
                                jQuery('#miniorange_auth_client_client_id').parent().show();
                                jQuery('#miniorange_auth_client_client_secret').parent().show();
                                jQuery('#miniorange_auth_client_scope').parent().show();
                                jQuery('#miniorange_oauth_login_link').parent().show();
                                jQuery('#test_config_button').show();

                                jQuery('#callbackurl').parent().show();
                                jQuery('#mo_oauth_authorizeurl').attr('required','true');
                                jQuery('#mo_oauth_accesstokenurl').attr('required','true');
                                jQuery('#mo_oauth_resourceownerdetailsurl').attr('required','true');
                                jQuery('#miniorange_auth_client_authorize_endpoint').parent().show();
                                jQuery('#miniorange_auth_client_access_token_ep').parent().show();
                                jQuery('#miniorange_auth_client_user_info_ep').parent().show();

                                document.getElementById('miniorange_auth_client_scope').value = myAppsData[appname]['miniorange_auth_client_scope'];
                                document.getElementById('miniorange_auth_client_authorize_endpoint').value = myAppsData[appname]['miniorange_auth_client_authorize_endpoint'];
                                document.getElementById('miniorange_auth_client_access_token_ep').value = myAppsData[appname]['miniorange_auth_client_access_token_ep'];
                                document.getElementById('miniorange_auth_client_user_info_ep').value = myAppsData[appname]['miniorange_auth_client_user_info_ep'];

                                pointerNumber = 2;
                                jQuery('.mo-card').remove();
                                createCard(pointerNumber);
                            }
                        })
                    }
                    );
}(jQuery));


//Showing button for guide
var guides = {
  'Azure AD' : 'https://plugins.miniorange.com/setup-guide-to-configure-azure-ad-with-drupal-oauth-client',
  'Box' : 'https://plugins.miniorange.com/guide-configure-box-drupal',
  'Discord' : 'https://plugins.miniorange.com/setup-guide-to-configure-discord-with-drupal-oauth-client',
  'Facebook' : 'https://plugins.miniorange.com/configure-facebook-oauth-server-for-drupal-8',
  'FitBit' : 'https://plugins.miniorange.com/configure-fitbit-oauth-server-for-drupal-8',
  'GitHub' : 'https://plugins.miniorange.com/configure-github-oauthopenid-connect-server-drupal-8',
  'Google' : 'https://plugins.miniorange.com/configure-google-oauth-server-drupal-8',
  'Keycloak' : 'https://plugins.miniorange.com/guide-to-configure-keycloak-for-drupal-oauth-client-module',
  'Line' : 'https://plugins.miniorange.com/setup-guide-to-configure-line-with-drupal-oauth-client',
  'LinkedIn' : 'https://plugins.miniorange.com/configure-linkedin-as-an-oauth-openid-connect-server-for-drupal-8-client',
  'Okta' : 'https://plugins.miniorange.com/guide-to-configure-okta-with-drupal',
  'Paypal' : 'https://plugins.miniorange.com/configure-paypal-with-drupal-oauth-client',
  'Salesforce' : 'https://plugins.miniorange.com/guide-salesforce-configuration-drupal-oauth-client-module',
  'Slack' : 'https://plugins.miniorange.com/configure-slack-as-as-oauth-openid-connect-server-in-drupal',
  // 'Strava' : '',
  'Wild Apricot' : 'https://plugins.miniorange.com/guide-to-configure-wildapricot-with-drupal',
  'Zendesk' : 'https://plugins.miniorange.com/guide-configure-zendesk-drupal',
  'Custom': 'https://plugins.miniorange.com/how-to-setup-drupal-oauth-client-sso'
};

jQuery(document).ready(function () {

  jQuery('#callbackurl').prop('readonly', true);
  jQuery('#copy_button').click(function () {
    var callbackUrl = document.getElementById('callbackurl').innerText.trim();
    navigator.clipboard.writeText(callbackUrl);
    var tooltip = document.getElementById("myTooltip");
    tooltip.innerHTML = "Copied";
   setTimeout(function() {
    tooltip.innerHTML = "Copy to Clipboard";
  }, 1000);
  });

  var oauth_server = jQuery('#miniorange_oauth_client_app').val();
  if( guides[oauth_server] !== undefined) {
    jQuery(' <a id="mo_oauth_setup_guide_link" class=\'button button--primary\' style="float: right; margin-top: -55px;" href=" ' + guides[oauth_server] + ' " target="_blank">' + oauth_server + ' setup guide</a>').appendTo(jQuery('#setupguide'));
  }
  });

jQuery('#miniorange_oauth_client_app').change( function () {

  var oauth_server = jQuery('#miniorange_oauth_client_app').val();
  jQuery('#mo_oauth_setup_guide_link').remove();
  if( guides[oauth_server] !== undefined) {
    jQuery(' <a id="mo_oauth_setup_guide_link" class=\'button button--primary\' style="float: right;margin-top: -55px;" href=" ' + guides[oauth_server] + ' " target="_blank">' + oauth_server + ' setup guide</a>').appendTo(jQuery('#setupguide'));
  }
} )
