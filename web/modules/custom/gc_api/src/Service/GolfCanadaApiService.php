<?php
namespace Drupal\gc_api\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\State\StateInterface;

class GolfCanadaApiService {

  protected $httpClient;
  protected $state;
  protected $settings;

  public function __construct(ClientInterface $http_client, StateInterface $state, $settings) {
    $this->httpClient = $http_client;
    $this->state = $state;
    $this->settings = $settings;
  }

  /**
   * Get a valid token, refreshing if expired.
   */
  public function getValidToken() {
    if ($this->isTokenExpired()) {
      return $this->authenticate();
    }

    return $this->state->get('gc_api.token');
  }

  /**
   * Check if the token is expired (default 1 hour).
   */
  protected function isTokenExpired() {
    $timestamp = $this->state->get('gc_api.token_timestamp');
    $expires_in = $this->state->get('gc_api.token_expires_in') ?? 3600;

    return !$timestamp || (time() - $timestamp) >= $expires_in;
  }

  /**
   * Authenticate with the Golf Canada API and store the token.
   */
  public function authenticate() {
    $credentials = $this->settings->get('gc_api.credentials');


    if (!$credentials || empty($credentials['username']) || empty($credentials['password'])) {
      \Drupal::logger('gc_api')->error('GC API credentials not set.');
      return NULL;
    }

    try {
      $response = $this->httpClient->post('https://scg.golfcanada.ca/connect/token', [
        'form_params' => [
          'grant_type' => 'password',
          'username' => $credentials['username'],
          'password' => $credentials['password'],
          'scope' => 'address email offline_access openid phone profile roles',
        ],
        'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'verify' => FALSE, // <-- disables SSL cert check
      ]);

      $data = json_decode($response->getBody(), TRUE);

      if (!empty($data['access_token'])) {
        $this->state->set('gc_api.token', $data['access_token']);
        $this->state->set('gc_api.token_timestamp', time());
        $this->state->set('gc_api.token_expires_in', $data['expires_in'] ?? 3600);

        return $data['access_token'];
      }

    } catch (\Exception $e) {
      \Drupal::logger('gc_api')->error('Auth error: @message', ['@message' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * Call the profile endpoint and get the handicap index.
   */
  public function getHandicapIndex($gc_user_id) {
    if (empty($gc_user_id)) {
      \Drupal::logger('gc_api')->warning('No Golf Canada user ID provided.');
      return NULL;
    }

    $token = $this->getValidToken();

    if (!$token) {
      \Drupal::logger('gc_api')->error('No valid token for fetching handicap index.');
      return NULL;
    }

    $url = 'https://scg.golfcanada.ca/api/scores/getProfile?individualId=' . urlencode($gc_user_id);

    try {
      $response = $this->httpClient->get($url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
        'verify' => FALSE, // 👈 Add this line
      ]);

      $data = json_decode($response->getBody(), TRUE);

      if (!empty($data['handicap']) && is_numeric($data['handicap'])) {
        return (float) $data['handicap'];
      }

      return NULL;


    } catch (\Exception $e) {
      \Drupal::logger('gc_api')->error('Handicap fetch error: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  public function getHistory($gc_user_id, $skip = 0, $top = 20) {
    if (empty($gc_user_id) || !is_numeric($gc_user_id)) {
      \Drupal::logger('gc_api')->warning('Invalid GC user ID provided to getHistory(). = ' . $gc_user_id);
      return NULL;
    }

    $token = $this->getValidToken();

    if (!$token) {
      \Drupal::logger('gc_api')->error('No valid token for fetching score history.');
      return NULL;
    }

    $query = http_build_query([
      '$skip' => $skip,
      '$top' => $top,
      'individualId' => $gc_user_id,
    ]);

    $url = "https://scg.golfcanada.ca/api/scores/getHistory?$query";

    try {
      $response = $this->httpClient->get($url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
        'verify' => FALSE, // ⚠️ Dev only! Remove or conditionally control in prod
      ]);

      $data = json_decode($response->getBody(), TRUE);

      // Return just the history array
      return $data['data'] ?? [];

    } catch (\Exception $e) {
      \Drupal::logger('gc_api')->error('Score history fetch error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  public function getRoundScore($scoreId = 0) {
    if ($scoreId <= 0) {
      \Drupal::logger('gc_api')->warning('Invalid score ID passed to getRoundScore().');
      return [];
    }

    // Get the GC API service
    $gcApi = \Drupal::service('gc_api.golf_canada_api_service');

    $token = $gcApi->getValidToken();
    if (!$token) {
      \Drupal::logger('gc_api')->error('Unable to fetch valid token for getRoundScore().');
      return [];
    }

    $url = 'https://scg.golfcanada.ca/api/scores/getScoreDetails?scoreId=' . $scoreId;

    try {
      $client = \Drupal::httpClient();
      $response = $client->get($url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
        'verify' => FALSE, // ⚠️ Disable in dev only
      ]);

      $data = json_decode($response->getBody(), TRUE);

      \Drupal::logger('gc_api')->debug('GC API Response: @data', [
        '@data' => json_encode($data),
      ]);

      if (!isset($data['holeScores']) || !is_array($data['holeScores'])) {
        \Drupal::logger('gc_api')->warning('No hole scores found in GC response.');
        return [];
      }

      // Build scores array
      $scores = [];
      foreach ($data['holeScores'] as $holeData) {
        $holeRaw = $holeData['holeNumber'];

        // Only include true integers between 1 and 18
        if (is_numeric($holeRaw) && floor($holeRaw) == $holeRaw && $holeRaw >= 1 && $holeRaw <= 18) {
          $hole = (int) $holeRaw;

          $scores[$hole] = [
            'hole' => $hole,
            'score' => $holeData['gross'] ?? '',
            'putts' => $holeData['putts'] ?? '', // nulls are okay
          ];
        }
      }


      return $scores;

    } catch (\Exception $e) {
      \Drupal::logger('gc_api')->error('Error fetching score details: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
