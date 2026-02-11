<?php

namespace Drupal\gc_upload\Service;

use DOMDocument;
use DOMXPath;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\grint_api\Grint_API_Service;

class GcUploadScorecardBuilder {

  protected Grint_API_Service $grintAPI;
  protected $logger;

  public function __construct(Grint_API_Service $grintAPI, LoggerChannelFactoryInterface $loggerFactory) {
    $this->grintAPI = $grintAPI;
    $this->logger = $loggerFactory->get('gc_upload');
  }

  protected function log(string $message, array $context = []): void {
    // Notice so it shows up in dblog more reliably.
    $this->logger->notice($message, $context);
  }

  /**
   * Extract course_id, tee_color, course_name from the Grint review_score page.
   */
  public function extractRoundMeta(int $roundId): array {
    $uri = '/score/review_score/' . $roundId;
    $html = $this->grintAPI->getRequest($uri);

    $this->log('Fetched review_score HTML length=@len for roundId=@rid', [
      '@len' => strlen((string) $html),
      '@rid' => (string) $roundId,
    ]);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $meta = [
      'course_id' => '',
      'tee_color' => '',
      'course_name' => '',
    ];

    $meta['course_id'] = $this->firstXPathValue($xpath, [
      "//input[@name='course_id']/@value",
      "//input[@id='course_id']/@value",
      "//input[contains(@name,'course') and contains(@name,'id')]/@value",
    ]);

    $meta['tee_color'] = $this->firstXPathValue($xpath, [
      "//input[@name='tee']/@value",
      "//input[@id='tee']/@value",
      "//select[@name='tee']/option[@selected]/@value",
      "//select[@id='tee']/option[@selected]/@value",
    ]);

    $meta['course_name'] = $this->firstXPathText($xpath, [
      "//*[contains(@class,'course-name')][1]",
      "//h1[1]",
      "//h2[1]",
      "//*[@id='course_name'][1]",
      "//*[@id='courseName'][1]",
    ]);

    // If course_name begins with "(12345) ..." we can parse course_id.
    if ($meta['course_id'] === '' && $meta['course_name'] !== '') {
      $cid = $this->grintAPI->getCourseIdFromString($meta['course_name']);
      if (!empty($cid)) {
        $meta['course_id'] = (string) $cid;
      }
    }

    $this->log('Round meta raw: course_id=@cid tee=@tee course_name=@name', [
      '@cid' => $meta['course_id'],
      '@tee' => $meta['tee_color'],
      '@name' => $meta['course_name'],
    ]);

    return $meta;
  }

  protected function firstXPathValue(DOMXPath $xpath, array $queries): string {
    foreach ($queries as $q) {
      $node = $xpath->query($q)->item(0);
      if ($node && trim($node->nodeValue) !== '') {
        return trim($node->nodeValue);
      }
    }
    return '';
  }

  protected function firstXPathText(DOMXPath $xpath, array $queries): string {
    foreach ($queries as $q) {
      $node = $xpath->query($q)->item(0);
      if ($node) {
        $text = trim($node->textContent);
        if ($text !== '') {
          return $text;
        }
      }
    }
    return '';
  }

  /**
   * Build an editable 18-hole scorecard form section.
   * - Scores come from $scores (blank if missing).
   * - Course info comes from course_id + tee_color via Grint course data (if available).
   */
  public function buildScorecard(array $scores, array $meta = [], string $grint_user_id = ''): array {
    $course_id = trim((string) ($meta['course_id'] ?? ''));
    $tee_color = trim((string) ($meta['tee_color'] ?? ''));
    $course_name = trim((string) ($meta['course_name'] ?? ''));

    // Pull course data when possible.
    $course_data_clean = NULL;
    if ($course_id !== '' && $tee_color !== '') {
      $raw = $this->grintAPI->getCourseData($course_id, $tee_color, 18);
      $course_data_clean = $this->grintAPI->processCourseData($raw);
      $this->log('Course data clean keys: @keys', [
        '@keys' => is_array($course_data_clean) ? implode(',', array_keys($course_data_clean)) : '',
      ]);
    }
    else {
      $this->log('Missing course_id or tee_color; cannot call getCourseData. course_id=@cid tee=@tee', [
        '@cid' => $course_id,
        '@tee' => $tee_color,
      ]);
    }

    // Optional: attempt course handicap (best-effort; not required for now).
    $course_handicap = NULL;
    try {
      if ($grint_user_id !== '' && $course_id !== '' && $tee_color !== '') {
        $hi = $this->grintAPI->getHandicapIndex($grint_user_id);
        $ch = $this->grintAPI->getCourseHandicap($grint_user_id, $hi, $course_id, $tee_color);
        $this->log('getCourseHandicap response: @resp', ['@resp' => print_r($ch, TRUE)]);

        if (is_object($ch)) {
          foreach (['course_handicap', 'courseHandicap', 'handicap', 'ch'] as $prop) {
            if (isset($ch->$prop) && is_numeric($ch->$prop)) {
              $course_handicap = (int) $ch->$prop;
              break;
            }
          }
        }
      }
    }
    catch (\Throwable $e) {
      $this->log('Unable to compute course handicap (non-fatal): @msg', ['@msg' => $e->getMessage()]);
    }

    // Course arrays (blank if not available).
    $pars = $course_data_clean['par']['hole_par'] ?? array_fill(0, 18, '');
    $yards = $course_data_clean['yardage']['hole_yardage'] ?? array_fill(0, 18, '');
    $hdcp = $course_data_clean['handicap']['hole_handicap'] ?? array_fill(0, 18, '');

    $par_out = $course_data_clean['par']['front_par'] ?? '';
    $par_in = $course_data_clean['par']['back_par'] ?? '';
    $par_total = $course_data_clean['par']['total_par'] ?? '';

    $yards_out = $course_data_clean['yardage']['front_yardage'] ?? '';
    $yards_in = $course_data_clean['yardage']['back_yardage'] ?? '';
    $yards_total = $course_data_clean['yardage']['total_yardage'] ?? '';

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['gc-upload-scorecard']],
      '#attached' => [
        'library' => [
          // Your existing helper (keeps your score table behaviors if it relies on ids/classes).
          'hacks_forms/scorecard-helper',
          // New module-specific styling/js.
          'gc_upload/scorecard',
        ],
      ],
    ];

    $build['scores_info'] = [
      '#type' => 'markup',
      '#markup' =>
        '<div class="gc-upload-scores-info">'
        . '<span>Course: <span id="user_course_name">' . htmlspecialchars($course_name ?: '(unknown)', ENT_QUOTES, 'UTF-8') . '</span></span>'
        . '<span style="margin-left:10px;">Tee: <span id="user_course_tee_color">' . htmlspecialchars($tee_color ?: '(unknown)', ENT_QUOTES, 'UTF-8') . '</span></span>'
        . '<span style="margin-left:10px;">Course Handicap: <span id="user_course_handicap">' . htmlspecialchars($course_handicap !== NULL ? (string) $course_handicap : '(unknown)', ENT_QUOTES, 'UTF-8') . '</span></span>'
        . '</div>',
    ];

    // FRONT TABLE.
    $build['scores_table']['front'] = [
      '#type' => 'table',
      '#header' => ['Hole', '1','2','3','4','5','6','7','8','9','Out'],
      '#attributes' => ['class' => ['scorecard-table-input']],
    ];
    $build['scores_table']['front']['yards'][0] = ['#markup' => 'Yards'];
    $build['scores_table']['front']['hdcp'][0] = ['#markup' => 'Hdcp'];
    $build['scores_table']['front']['par'][0] = ['#markup' => 'Par'];
    $build['scores_table']['front']['score'][0] = ['#markup' => 'Score'];

    for ($i = 1; $i <= 9; $i++) {
      $hole = $i;
      $hole_score = isset($scores[$hole]['score']) ? (string) $scores[$hole]['score'] : '';

      $build['scores_table']['front']['yards'][$i] = ['#markup' => $yards[$hole - 1] ?? ''];
      $build['scores_table']['front']['hdcp'][$i] = ['#markup' => $hdcp[$hole - 1] ?? ''];
      $build['scores_table']['front']['par'][$i] = ['#markup' => $pars[$hole - 1] ?? ''];
      $build['scores_table']['front']['score'][$i] = [
        '#type' => 'textfield',
        '#size' => 1,
        '#required' => FALSE,
        '#attributes' => [
          'pattern' => '[0-9]*',
          'min' => '0',
          'class' => ['gc-upload-score-input'],
          'data-hole' => (string) $hole,
        ],
        '#default_value' => $hole_score,
      ];
    }

    $build['scores_table']['front']['yards'][10] = ['#markup' => $yards_out];
    $build['scores_table']['front']['hdcp'][10] = ['#markup' => ''];
    $build['scores_table']['front']['par'][10] = ['#markup' => $par_out];
    $build['scores_table']['front']['score'][10] = ['#markup' => '<span id="scores_table_front_score">0</span>'];

    // BACK TABLE.
    $build['scores_table']['back'] = [
      '#type' => 'table',
      '#header' => ['Hole', '10','11','12','13','14','15','16','17','18','In'],
      '#attributes' => ['class' => ['scorecard-table-input']],
    ];
    $build['scores_table']['back']['yards'][0] = ['#markup' => 'Yards'];
    $build['scores_table']['back']['hdcp'][0] = ['#markup' => 'Hdcp'];
    $build['scores_table']['back']['par'][0] = ['#markup' => 'Par'];
    $build['scores_table']['back']['score'][0] = ['#markup' => 'Score'];

    for ($i = 1; $i <= 9; $i++) {
      $hole = $i + 9;
      $hole_score = isset($scores[$hole]['score']) ? (string) $scores[$hole]['score'] : '';

      $build['scores_table']['back']['yards'][$i] = ['#markup' => $yards[$hole - 1] ?? ''];
      $build['scores_table']['back']['hdcp'][$i] = ['#markup' => $hdcp[$hole - 1] ?? ''];
      $build['scores_table']['back']['par'][$i] = ['#markup' => $pars[$hole - 1] ?? ''];
      $build['scores_table']['back']['score'][$i] = [
        '#type' => 'textfield',
        '#size' => 1,
        '#required' => FALSE,
        '#attributes' => [
          'pattern' => '[0-9]*',
          'min' => '0',
          'class' => ['gc-upload-score-input'],
          'data-hole' => (string) $hole,
        ],
        '#default_value' => $hole_score,
      ];
    }

    $build['scores_table']['back']['yards'][10] = ['#markup' => $yards_in];
    $build['scores_table']['back']['hdcp'][10] = ['#markup' => ''];
    $build['scores_table']['back']['par'][10] = ['#markup' => $par_in];
    $build['scores_table']['back']['score'][10] = ['#markup' => '<span id="scores_table_back_score">0</span>'];

    // TOTALS TABLE (simple for now).
    $build['scores_table']['total'] = [
      '#type' => 'table',
      '#header' => ['Gross Score', 'Par', 'Yards'],
      '#attributes' => ['class' => ['scorecard-table-input']],
    ];

    $grossScore = 0;
    foreach ($scores as $hole => $data) {
      if (isset($data['score']) && is_numeric($data['score'])) {
        $grossScore += (int) $data['score'];
      }
    }

    $build['scores_table']['total']['score']['gross'] = [
      '#markup' => "<span id='scores_table_total_score'>{$grossScore}</span>",
    ];
    $build['scores_table']['total']['score']['par'] = [
      '#markup' => '<span id="scores_table_par_score">' . htmlspecialchars((string) $par_total, ENT_QUOTES, 'UTF-8') . '</span>',
    ];
    $build['scores_table']['total']['score']['yardage'] = [
      '#markup' => htmlspecialchars((string) $yards_total, ENT_QUOTES, 'UTF-8'),
    ];

    return $build;
  }

}
