<?php

namespace Drupal\miniorange_oauth_client;

/**
 * Class for handling customer support query.
 */
class MiniorangeOAuthClientSupport {

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
   * The query of user.
   *
   * @var string
   */
  public $query;

  /**
   * The type of query.
   *
   * @var string
   */
  public $queryType;

  /**
   * The timezone of user.
   *
   * @var string
   */
  public $moTimezone;

  /**
   * The date of query.
   *
   * @var string
   */
  public $moDate;

  /**
   * The time of query.
   *
   * @var string
   */
  public $moTime;

  /**
   * The description of query.
   *
   * @var string
   */
  public $supportFor;

  /**
   * Constructs a new MiniorangeOAuthClientSupport object.
   *
   * @param string $email
   *   The email of user.
   * @param string $phone
   *   The phone of user.
   * @param string $query
   *   The query of user.
   * @param string $queryType
   *   The query type of user.
   * @param string $moTimezone
   *   The timzone.
   * @param string $moDate
   *   The date.
   * @param string $moTime
   *   The time.
   * @param string $supportFor
   *   Support requested for.
   */
  public function __construct($email, $phone, $query, $queryType = '', $moTimezone = '', $moDate = '', $moTime = '', $supportFor = '') {
    $this->email = $email;
    $this->phone = $phone;
    $this->query = $query;
    $this->queryType = $queryType;
    $this->moTimezone = $moTimezone;
    $this->moDate = $moDate;
    $this->moTime = $moTime;
    $this->supportFor = $supportFor;
  }

  /**
   * Send support query.
   *
   * @return bool
   *   Return true if query sent successfully else false.
   */
  public function sendSupportQuery() {
    $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('miniorange_oauth_client');
    $modules_version = $modules_info['version'];

    if ($this->queryType == 'Trial Request' || $this->queryType == 'Call Request' || $this->queryType == 'Contact Support') {

      $url = MiniorangeOAuthClientConstants::BASE_URL . '/moas/api/notify/send';
      $request_for = $this->queryType == 'Trial Request' ? 'Trial' : ($this->queryType == 'Contact Support' ? 'Support' : 'Setup Meeting/Call');

      $subject = $request_for . ' request for Drupal-' . \DRUPAL::VERSION . ' OAuth Client Module | ' . $modules_version . ' | '. phpversion() . ' - ' .$this->email;
      $this->query = $this->queryType == 'Trial Request' ? $this->query : $request_for . ' requested for - ' . $this->query;
      $customerKey = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_id');
      $apikey = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_api_key');
      if ($customerKey == '') {
        $customerKey = "16555";
        $apikey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
      }

      $currentTimeInMillis = Utilities::getOauthTimestamp();
      $stringToHash = $customerKey . $currentTimeInMillis . $apikey;
      $hashValue = hash("sha512", $stringToHash);

      if ($this->queryType == 'Call Request') {
        $content = '<div >Hello, <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>Phone Number:' . $this->phone . '<br><br>Email:<a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br><br> Timezone: <b>' . $this->moTimezone . '</b><br><br> Date: <b>' . $this->moDate . '</b>&nbsp;&nbsp; Time: <b>' . $this->moTime . '</b><br><br>Query:[DRUPAL ' . Utilities::moGetDrupalCoreVersion() . ' OAuth Client Free | ' . $modules_version . ' | PHP ' . phpversion() . ' ] ' . $this->query . '</div>';
      }
      elseif ($this->queryType == 'Contact Support') {
        $content = '<div >Hello, <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br><strong>Support needed for: </strong>' . $this->supportFor . '<br><br>Email:<a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br><br>Query:[DRUPAL ' . Utilities::moGetDrupalCoreVersion() . ' OAuth Client Free | ' . $modules_version . ' | PHP ' . phpversion() . ' ] ' . $this->query . '</div>';
      }
      else {
        $content = '<div >Hello, <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>Phone Number:' . $this->phone . '<br><br>Email:<a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br><br>Trial request for:[DRUPAL ' . Utilities::moGetDrupalCoreVersion() . ' OAuth Client Free | ' . $modules_version . ' | PHP ' . phpversion() . ' ] ' . $this->query . '</div>';
      }

      $fields = [
        'customerKey' => $customerKey,
        'sendEmail' => TRUE,
        'email' => [
          'customerKey' => $customerKey,
          'fromEmail' => $this->email,
          'fromName' => 'miniOrange',
          'toEmail' => MiniorangeOAuthClientConstants::SUPPORT_EMAIL,
          'toName' => MiniorangeOAuthClientConstants::SUPPORT_EMAIL,
          'subject' => $subject,
          'content' => $content,
        ],
      ];

      $header = [
        'Content-Type' => 'application/json',
        'Customer-Key' => $customerKey,
        'Timestamp' => $currentTimeInMillis,
        'Authorization' => $hashValue,
      ];

    }
    else {

      $this->query = '[Drupal ' . \DRUPAL::VERSION . ' OAuth Client Free Module | ' . $modules_version . ' | PHP ' . phpversion() . '] ' . $this->query;
      $fields = [
        'company' => $_SERVER['SERVER_NAME'],
        'email' => $this->email,
        'phone' => $this->phone,
        'ccEmail' => MiniorangeOAuthClientConstants::SUPPORT_EMAIL,
        'query' => $this->query,
      ];

      $url = MiniorangeOAuthClientConstants::BASE_URL . '/moas/rest/customer/contact-us';

      $header = [
        'Content-Type' => 'application/json',
        'charset' => 'UTF-8',
        'Authorization' => 'Basic',
      ];
    }

    $field_string = json_encode($fields);
    $response = Utilities::callService($url, $field_string, $header);
    $content = json_decode($response, TRUE);
    if (isset($content['status']) && $content['status'] == 'SUCCESS') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
