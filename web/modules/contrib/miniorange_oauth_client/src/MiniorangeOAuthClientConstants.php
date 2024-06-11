<?php

/**
 * @file
 * Contains constants class.
 */

namespace Drupal\miniorange_oauth_client;

/**
 * Class for handling constants used throughout the project.
 */
class MiniorangeOAuthClientConstants {

  const BASE_URL = 'https://login.xecurify.com';
  const SUPPORT_EMAIL = 'drupalsupport@xecurify.com';

  const AUTH_CODE_GRANT = "Authorization Code Grant";
  const AUTH_CODE_PKCE_GRANT = "Authorization Code with PKCE";
  const PASS_GRANT = "Password Grant";
  const IMPLICIT_GRANT = "Implicit Grant";

  const AUTH_CODE_GRANT_GUIDE = "https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-authorization-code-grant";
  const AUTH_CODE_PKCE_GRANT_GUIDE = "https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-authorization-code-grant";
  const PASS_GRANT_GUIDE = "https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-password-grant";
  const IMPLICIT_GRANT_GUIDE = "https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login/what-is-oauth-20-implicit-grant";

  /**
   * Module Info used for skipped feedback template
   */
  const MODULE_INFO = [
    'name' => 'OAuth Client',
    'oauth_features' => 'https://plugins.miniorange.com/drupal-sso-oauth-openid-single-sign-on',
    'setup_guides' => 'https://www.drupal.org/docs/contributed-modules/drupal-oauth-openid-connect-login-oauth2-client-sso-login',
    'video_links' => 'https://www.youtube.com/playlist?list=PL2vweZ-PcNpeW8s4xWt0tdev1oL7TCZ57',
    'case_studies' => 'https://www.drupal.org/node/3196471/case-studies',
    'landing_page' => 'https://plugins.miniorange.com/drupal',
    'customers' => 'https://plugins.miniorange.com/drupal-customers',
    'drupalsupport' => self::SUPPORT_EMAIL,
  ];

}