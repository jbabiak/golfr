<?php

namespace Drupal\grint_api;

use DOMDocument;
use DOMXPath;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\RequestException;


class Grint_API_Service {
  protected $client;
  protected ConfigFactoryInterface $configFactory;
  protected \Drupal\Core\Logger\LoggerChannelInterface $logger;
  protected ClientInterface $httpClient;
  protected StateInterface $state;
  protected $login_url = 'https://www.thegrint.com';

  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory, ClientInterface $httpClient, StateInterface $state) {
    $this->configFactory = $configFactory;
    $this->logger = $loggerFactory->get('Grint_API');
    $this->httpClient = $httpClient;
    $this->state = $state;
    $this->client = new Client([
      'base_uri' => 'https://www.thegrint.com',
      'cookies' => true, // Enable cookie jar
      'headers' => [
        'Content-Type' => 'application/x-www-form-urlencoded'
      ]
    ]);
    $this->login();
  }

  public function login() {
    $response = $this->client->post('/login', [
      'form_params' => [
        'username' => Settings::get('grint_api.settings')['username'],
        'password' => Settings::get('grint_api.settings')['password'],
      ]
    ]);

    return $response;
  }

  public function getHandicapIndex($grint_user_id) {
    $uri = '/user/get_handicap_info/';
    $payload = ['user_id' => $grint_user_id];
    $index = $this->postRequest($uri, $payload);

    $clean = trim($index->index_ghap, "~");

    // Match the first number: integer or float
    if (preg_match('/\d+(\.\d+)?/', $clean, $matches)) {
      $index_ghap = (float) $matches[0];
    }

    if (strpos($index_ghap, '+') === 0) {
      // Remove the '+' and convert to decimal
      $hdcp = -floatval(substr($index_ghap, 1));
    } else {
      // Convert to decimal and make it negative
      $hdcp = floatval($index_ghap);
    }
    return $hdcp;
  }

  public function getRequest($uri) {
    $response = $this->client->get($uri);
    return $response->getBody()->getContents();
  }

  public function postRequest($uri, $payload = null) {
    $response = $this->client->post($uri, [
      'form_params' => $payload
    ]);
    return json_decode($response->getBody()->getContents());
  }

  public function postRequestHTML($uri, $payload) {
    $response = $this->client->post($uri, [
      'form_params' => $payload
    ]);
    return ($response->getBody()->getContents());
  }

  public function searchCourse($string) {
    $uri = '/ajax/courseAutoComplete';
    $payload = [
      'search' => $string,
      'wave' => 0,
      'limit' => 10,
    ];
    $courses = $this->postRequest($uri, $payload);
    return $courses;
  }

  public function getCourseIdFromString($string) {
    if (preg_match('/^\((\d+)\)/', $string, $matches)) {
      $number = $matches[1];
      return $number;
    }
    return 0;
  }

  public function searchCourseTeeColors($course_id) {
    $uri = '/score/ajax_tees/';
    $payload = [
      'user_id' => 1597150,
      'course_id' => $course_id,
      'tee' => 'magenta',
    ];
    $teeHTML = $this->postRequestHTML($uri, $payload);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Disable warnings for invalid HTML
    $dom->loadHTML($teeHTML);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $options = $xpath->query('//option[@class="option-tee"]');

    $results = [];
    foreach ($options as $option) {
      $value = $option->getAttribute('value');
      $lr = $option->getAttribute('lr');
      $ls = $option->getAttribute('ls');
      $mr = $option->getAttribute('mr');
      $ms = $option->getAttribute('ms');

      $results[$value] = [
        'value' => $value,
        'lr' => $lr,
        'ls' => $ls,
        'mr' => $mr,
        'ms' => $ms
      ];
    }

    return $results;
  }

  public function getCourseData($course_id, $tee_color, $round = 18, $handicap_company_id = 7){
    $uri = '/ajax/get_course_data/0/0/0';
    $payload = [
      'course_id' => $course_id,
      'tee' => $tee_color,
      'round' => $round,
    ];
    return $this->postRequest($uri, $payload);
  }

  public function processCourseData($course_data){
    $clean_data = [];
    $clean_data['handicap'] = $this->processHandicap($course_data->handicap);
    $clean_data['yardage'] = $this->processYardages($course_data->yardage);
    $clean_data['par'] = $this->processPar($course_data->par);
    return $clean_data;
  }

  public function getGrintProfileImg($ghap_id){
    $uri = '/user/ajax_search_users_json';
    $payload = [
      'search' => $ghap_id,
    ];
    return $this->postRequest($uri, $payload)[0]->image;
  }

  public function getCourseHandicap($user_id, $user_hdcp, $course_id, $tee_color) {
    $uri = '/user/ajax_course_hdcp_lookup/';
    $payload = [
      'user_id' => $user_id,
      'user_hdcp' => $user_hdcp,
      'course_id' => $course_id,
      'tee' => $tee_color,
      'provider' => 7, //7 = ghap, 3 = whs
    ];
    return $this->postRequest($uri, $payload);
  }

  public function getCourseHandicapManual($handicapIndex, $slopeRating) {
    $courseHandicap = $handicapIndex * ($slopeRating / 113);
    return round($courseHandicap);
  }

  public function getCourseHandicapWHS($handicap_index,$slope_rating,$course_rating, $par): int {
    $course_handicap = ($handicap_index * ($slope_rating / 113)) + ($course_rating - $par);
    return (int) round($course_handicap);
  }

  public function getRoundScore($roundId = 0) {
    if ($roundId > 0) {
      $uri = '/score/review_score/' . $roundId;
      $htmlContent = $this->getRequest($uri);

      $dom = new DOMDocument();
      @$dom->loadHTML($htmlContent);

      $xpath = new DOMXPath($dom);

      $scoreQuery = "//table[contains(@class, 'user-input score')]//input[contains(@class, 'input-score-field')]";
      $scoreInputs = $xpath->query($scoreQuery);

      $scores = [];

      foreach ($scoreInputs as $input) {
        $holeNumber = $input->getAttribute('data-hole');
        $scoreValue = $input->getAttribute('data-value');

        // Initialize the array for this hole
        $scores[$holeNumber] = [
          'hole' => $holeNumber,
          'score' => $scoreValue
        ];
      }

      // Now, find all input elements for putts
      $puttsQuery = "//table[contains(@class, 'user-input optional')]//input[contains(@class, 'input-score-field')]";
      $puttsInputs = $xpath->query($puttsQuery);

      foreach ($puttsInputs as $input) {
        $holeNumber = $input->getAttribute('data-hole');
        $puttsValue = $input->getAttribute('value');

        if (isset($scores[$holeNumber])) {
          $scores[$holeNumber]['putts'] = $puttsValue;
        }
      }
      return $scores;
    }
  }

  /**
   * UPDATED: Supports Grint feed paging.
   * - No wave => latest rounds (backward compatible)
   * - wave 1/2/3... => older pages
   */
  public function getRoundFeed($user_id = 1597150, $wave = NULL) {
    $uri = '/newsfeed_util/loadActivityFriend';
    $payload = [
      'friendId' => $user_id,
    ];

    if ($wave !== NULL && $wave !== '' && is_numeric($wave)) {
      $payload['wave'] = (int) $wave;
    }

    return $this->postRequestHTML($uri, $payload);
  }

  public function processHandicap($handicap_html){
    $dom = new DOMDocument;
    libxml_use_internal_errors(true); // Disable warnings for invalid HTML
    $dom->loadHTML($handicap_html);
    libxml_clear_errors();
    // Create a new DOMXPath instance for the DOMDocument
    $xpath = new DOMXPath($dom);
    // XPath query to select all 'td' elements with class 'data-entry section-in'
    $query = "//td[contains(@class, 'data-entry') and contains(@class, 'section-in')]";
    $querySectionOut = "//td[contains(@class, 'data-entry') and contains(@class, 'section-out')]";
    $querySectionIn = "//td[contains(@class, 'data-entry') and contains(@class, 'section-in')]";

    $sectionOutValues = $this->extractValues($xpath, $querySectionOut);
    $sectionInValues = $this->extractValues($xpath, $querySectionIn);

    $combinedValues['hole_handicap'] = array_merge($sectionOutValues, $sectionInValues);

    return $combinedValues;
  }

  public function processYardages($yardage_html) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($yardage_html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $querySectionOut = "//td[contains(@class, 'data-entry') and contains(@class, 'section-out')]";
    $querySectionIn = "//td[contains(@class, 'data-entry') and contains(@class, 'section-in')]";
    $querySubtotalOut = "//td[contains(@class, 'subtotal') and contains(@class, 'section-out')]";
    $querySubtotalIn = "//td[contains(@class, 'subtotal') and contains(@class, 'section-in')]";
    $queryTotalYardage = "//td[contains(@class, 'total') and contains(@class, 'yardage')]";

    $sectionOutValues = $this->extractValues($xpath, $querySectionOut);
    $sectionInValues = $this->extractValues($xpath, $querySectionIn);
    $subtotalOut = $this->extractSingleValue($xpath, $querySubtotalOut);
    $subtotalIn = $this->extractSingleValue($xpath, $querySubtotalIn);
    $totalYardage = $this->extractSingleValue($xpath, $queryTotalYardage);

    $combinedValues = array_merge($sectionOutValues, $sectionInValues);

    $yardages['hole_yardage'] = $combinedValues;
    $yardages['front_yardage'] = $subtotalOut;
    $yardages['back_yardage'] = $subtotalIn;
    $yardages['total_yardage'] = $totalYardage;

    return $yardages;
  }

  public function processPar($par_html){
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($par_html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $querySectionOut = "//td[contains(@class, 'data-entry') and contains(@class, 'section-out')]";
    $querySectionIn = "//td[contains(@class, 'data-entry') and contains(@class, 'section-in')]";
    $querySubtotalOut = "//td[contains(@class, 'subtotal') and contains(@class, 'section-out')]";
    $querySubtotalIn = "//td[contains(@class, 'subtotal') and contains(@class, 'section-in')]";
    $queryTotalPar = "//td[contains(@class, 'total') and contains(@class, 'course-par')]";

    $sectionOutValues = $this->extractValues($xpath, $querySectionOut);
    $sectionInValues = $this->extractValues($xpath, $querySectionIn);
    $subtotalOut = $this->extractSingleValue($xpath, $querySubtotalOut);
    $subtotalIn = $this->extractSingleValue($xpath, $querySubtotalIn);
    $totalPar = $this->extractSingleValue($xpath, $queryTotalPar);

    $combinedValues = array_merge($sectionOutValues, $sectionInValues);

    $pars['hole_par'] = $combinedValues;
    $pars['front_par'] = $subtotalOut;
    $pars['back_par'] = $subtotalIn;
    $pars['total_par'] = $totalPar;

    return $pars;
  }

  public function extractValues(DOMXPath $xpath, string $query): array {
    $entries = $xpath->query($query);
    $values = [];
    foreach ($entries as $entry) {
      $values[] = trim($entry->textContent);
    }
    return $values;
  }
  // Function to extract a single value
  function extractSingleValue($xpath, $query) {
    $entry = $xpath->query($query)->item(0);
    return $entry ? trim($entry->textContent) : null;
  }
}
