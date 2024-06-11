<?php

namespace Drupal\miniorange_oauth_client;

use GuzzleHttp\Exception\RequestException;

/**
 * Class for handling registration of new customers.
 */
class MiniorangeOAuthClientCustomer {

  /**
   * The email of user.
   *
   * @var string
   */
  public $email;

  /**
   * The phone of user.
   *
   * @var string
   */
  public $phone;

  /**
   * The customer-key.
   *
   * @var string
   */
  public $customerKey;

  /**
   * The transaction-id.
   *
   * @var string
   */
  public $transactionId;

  /**
   * The password.
   *
   * @var string
   */
  public $password;

  /**
   * The otp token.
   *
   * @var string
   */
  public $otpToken;

  /**
   * The default customer-id.
   *
   * @var string
   */
  private $defaultCustomerId;

  /**
   * The default customer api-key.
   *
   * @var string
   */
  private $defaultCustomerApiKey;

  /**
   * Constructs a new MiniorangeOAuthClientCustomer object.
   * 
   * @param string $email
   *   The email of user.
   * @param string $phone
   *   The phone of user.
   * @param string $password
   *   The password of user.
   * @param string $otp_token
   *   The otp token of user.
   */
  public function __construct($email, $phone, $password, $otp_token) {
    $this->email = $email;
    $this->phone = $phone;
    $this->password = $password;
    $this->otpToken = $otp_token;
    $this->defaultCustomerId = "16555";
    $this->defaultCustomerApiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
  }

  /**
   * Check if customer exists.
   *
   * @return string|void
   *   Returns api call response.
   */
  public function checkCustomer() {
    if (!Utilities::isCurlInstalled()) {
      return json_encode([
        "status" => 'CURL_ERROR',
        "statusMessage" => '<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.',
      ]);
    }

    $url = MiniorangeOAuthClientConstants::BASE_URL . '/moas/rest/customer/check-if-exists';

    $email = $this->email;

    $header = [
      'Content-Type' => 'application/json',
      'charset' => 'UTF - 8',
      'Authorization' => 'Basic',
    ];

    $body = [
      'email' => $email,
    ];

    $fields = [
      'headers' => $header,
      'body' => json_encode($body),
      'verify' => FALSE,
    ];

    try {

      $api_response = \Drupal::httpClient()->request('POST', $url, $fields);

      $response = $api_response->getBody()->getContents();

      return $response;
    }
    catch (RequestException $exception) {
      $error = [
        '%method' => 'checkCustomer',
        '%file' => 'customer_setup.php',
        '%error' => $exception->getMessage(),
      ];
      \Drupal::logger('miniorange_oauth_client')->notice('%error', $error);
    }

  }

  /**
   * Creates Customer.
   *
   * @return string|void
   *   Returns api call response.
   */
  public function createCustomer() {
    if (!Utilities::isCurlInstalled()) {
      return json_encode([
        "statusCode" => 'ERROR',
        "statusMessage" => '. Please check your configuration.',
      ]);
    }
    $url = MiniorangeOAuthClientConstants::BASE_URL . '/moas/rest/customer/add';

    $header = [
      'Content-Type' => 'application/json',
      'charset' => 'UTF - 8',
      'Authorization' => 'Basic',
    ];

    $body = [
      'companyName' => $_SERVER['SERVER_NAME'],
      'areaOfInterest' => 'DRUPAL ' . Utilities::moGetDrupalCoreVersion() . ' OAuth Client',
      'email' => $this->email,
      'phone' => $this->phone,
      'password' => $this->password,
    ];

    $fields = [
      'headers' => $header,
      'body' => json_encode($body),
      'verify' => FALSE,
    ];

    try {
      $api_response = \Drupal::httpClient()->request('POST', $url, $fields);

      $response = $api_response->getBody()->getContents();

      return $response;
    }
    catch (RequestException $exception) {
      $error = [
        '%method' => 'createCustomer',
        '%file' => 'customer_setup.php',
        '%error' => $exception->getMessage(),
      ];
      \Drupal::logger('miniorange_oauth_client')->notice('%error', $error);
    }

  }

  /**
   * Get Customer Keys.
   *
   * @return string|void
   *   Returns api call response.
   */
  public function getCustomerKeys() {
    if (!Utilities::isCurlInstalled()) {
      return json_encode([
        "apiKey" => 'CURL_ERROR',
        "token" => '<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.',
      ]);
    }

    $url = MiniorangeOAuthClientConstants::BASE_URL . '/moas/rest/customer/key';

    $email = $this->email;
    $password = $this->password;

    $header = [
      'Content-Type' => 'application/json',
      'charset' => 'UTF - 8',
      'Authorization' => 'Basic',
    ];

    $body = [
      'email' => $email,
      'password' => $password,
    ];

    $fields = [
      'headers' => $header,
      'body' => json_encode($body),
      'verify' => FALSE,
    ];

    try {

      $api_response = \Drupal::httpClient()->request('POST', $url, $fields);
      $response = $api_response->getBody()->getContents();

      return $response;
    }
    catch (RequestException $exception) {
      $error = [
        '%method' => 'getCustomerKeys',
        '%file' => 'customer_setup.php',
        '%error' => $exception->getMessage(),
      ];
      \Drupal::logger('miniorange_oauth_client')->notice('%error', $error);
    }

  }

  /**
   * Send OTP to customer.
   *
   * @return string|void
   *   Returns api call response.
   */
  public function sendOtp() {
    if (!Utilities::isCurlInstalled()) {
      return json_encode([
        "status" => 'CURL_ERROR',
        "statusMessage" => '<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.',
      ]);
    }
    $url = MiniorangeOAuthClientConstants::BASE_URL . '/moas/api/auth/challenge';
    $customer_key = $this->defaultCustomerId;
    $api_key = $this->defaultCustomerApiKey;

    $username = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email');

    /* Current time in milliseconds since midnight, January 1, 1970 UTC. */
    $currentTimeInMillis = round(microtime(TRUE) * 1000);

    /* Creating the Hash using SHA-512 algorithm */
    $string_to_hash = $customer_key . number_format($currentTimeInMillis, 0, '', '') . $api_key;
    $hash_value = hash("sha512", $string_to_hash);

    $customer_key_header = "Customer-Key: " . $customer_key;
    $timestamp_header = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
    $authorization_header = "Authorization: " . $hash_value;

    $header = [
      "Content-Type" => "application/json",
      "Customer-Key" => $customer_key ,
      "Timestamp" => $currentTimeInMillis,
      "Authorization" => $hash_value,
    ];

    $body = [
      'customerKey' => $customer_key,
      'email' => $username,
      'authType' => 'EMAIL',
      'transactionName' => 'Drupal ' . Utilities::moGetDrupalCoreVersion() . ' OAuth Client Module',
    ];

    $fields = [
      'headers' => $header,
      'body' => json_encode($body),
      'verify' => FALSE,
    ];

    try {

      $api_response = \Drupal::httpClient()->request('POST', $url, $fields);

      $response = $api_response->getBody()->getContents();

      return $response;
    }
    catch (RequestException $exception) {
      $error = [
        '%method' => 'sendotp',
        '%file' => 'customer_setup.php',
        '%error' => $exception->getMessage(),
      ];
      \Drupal::logger('miniorange_oauth_client')->notice('%error', $error);
    }
  }

  /**
   * Validate OTP.
   *
   * @return string|void
   *   Returns api call response.
   */
  public function validateOtp($transaction_id) {
    if (!Utilities::isCurlInstalled()) {
      return json_encode([
        "status" => 'CURL_ERROR',
        "statusMessage" => '<a href="http://php.net/manual/en/curl.installation.php">PHP cURL extension</a> is not installed or disabled.',
      ]);
    }

    $url = MiniorangeOAuthClientConstants::BASE_URL . '/moas/api/auth/validate';

    $customer_key = $this->defaultCustomerId;
    $api_key = $this->defaultCustomerApiKey;

    $currentTimeInMillis = round(microtime(TRUE) * 1000);

    $string_to_hash = $customer_key . number_format($currentTimeInMillis, 0, '', '') . $api_key;
    $hash_value = hash("sha512", $string_to_hash);

    $customer_key_header = "Customer-Key: " . $customer_key;
    $timestamp_header = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
    $authorization_header = "Authorization: " . $hash_value;

    $header = [
      "Content-Type" => "application/json",
      "Customer-Key" => $customer_key ,
      "Timestamp" => $currentTimeInMillis,
      "Authorization" => $hash_value,
    ];

    $body = [
      'txId' => $transaction_id,
      'token' => $this->otpToken,
    ];

    $fields = [
      'headers' => $header,
      'body' => json_encode($body),
      'verify' => FALSE,
    ];

    try {

      $api_response = \Drupal::httpClient()->request('POST', $url, $fields);

      $response = $api_response->getBody()->getContents();

      return $response;
    }
    catch (RequestException $exception) {
      $error = [
        '%method' => 'validateotp',
        '%file' => 'customer_setup.php',
        '%error' => $exception->getMessage(),
      ];
      \Drupal::logger('miniorange_oauth_client')->notice('%error', $error);
    }

  }

}
