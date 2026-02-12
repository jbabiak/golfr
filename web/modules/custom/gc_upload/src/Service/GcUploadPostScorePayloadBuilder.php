<?php

namespace Drupal\gc_upload\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

class GcUploadPostScorePayloadBuilder {

  protected ?LoggerChannelInterface $logger = NULL;

  /**
   * IMPORTANT:
   * - Must be optional because the service definition currently passes 0 args.
   * - This prevents "Too few arguments" fatals and is backward compatible.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerFactory = NULL) {
    if ($loggerFactory) {
      $this->logger = $loggerFactory->get('gc_upload');
    }
  }

  protected function log(string $message, array $context = []): void {
    if ($this->logger) {
      $this->logger->notice($message, $context);
    }
    else {
      \Drupal::logger('gc_upload')->notice($message, $context);
    }
  }

  /**
   * Build Golf Canada score payload array to match the exact network JSON structure.
   *
   * Restored behavior:
   * - gross = Grint scorecard hole scores
   * - putts = scorecard hole putts (new)
   * - yards/par/handicap = GC tee hole data (facilityId + memberId + courseId + teeId)
   * - esc = sum of gross
   */
  public function build(FormStateInterface $form_state): array {
    $values = $form_state->getValues();

    $individualId = isset($values['gc_id']) ? (int) $values['gc_id'] : 0;
    $facilityId = isset($values['gc_facility_id']) ? (int) $values['gc_facility_id'] : 0;
    $courseId = isset($values['gc_course_id']) ? (int) $values['gc_course_id'] : 0;
    $teeId = isset($values['gc_tee_id']) ? (int) $values['gc_tee_id'] : 0;

    // Backward compatible: scorecard_date might still exist in some flows.
    $played_date = (string) ($values['played_date'] ?? $values['scorecard_date'] ?? '');
    $date_iso = $this->dateToIsoMs($played_date);

    $holes_mode = (string) ($values['holes_mode'] ?? '18');
    $holesPlayed = $this->holesModeToGcEnum($holes_mode);

    $isTournament = !empty($values['tournament_score']);
    $isPlayedAlone = ((string) ($values['played_alone'] ?? 'no')) === 'yes';

    $attestor = trim((string) ($values['attestor'] ?? ''));
    $attestor = ($attestor === '') ? NULL : $attestor;

    $isHoleByHole = TRUE;
    $isHoleByHoleRequired = FALSE;
    $isPenalty = FALSE;

    // 1) Get gross per hole from Grint scorecard inputs.
    $gross_by_hole = $this->extractGrossByHoleFromFormValues($values);

    // NEW: 1b) Get putts per hole from scorecard inputs.
    $putts_by_hole = $this->extractPuttsByHoleFromFormValues($values);

    // Helpful debug (won't break anything).
    $this->log('Extracted gross holes=@holes', [
      '@holes' => implode(',', array_keys($gross_by_hole)),
    ]);
    $this->log('Extracted putts holes=@holes', [
      '@holes' => implode(',', array_keys($putts_by_hole)),
    ]);

    // 2) Get yards/par/handicap per hole from GC tee data.
    $gc_hole_map = [];
    if ($facilityId > 0 && $individualId > 0 && $courseId > 0 && $teeId > 0) {
      try {
        /** @var \Drupal\gc_api\Service\GolfCanadaApiService $gc */
        $gc = \Drupal::service('gc_api.golf_canada_api_service');
        $gc_hole_map = $gc->getTeeHoles($facilityId, $individualId, $courseId, $teeId);
      }
      catch (\Throwable $e) {
        $this->log('GC getTeeHoles failed: @msg', ['@msg' => $e->getMessage()]);
        $gc_hole_map = [];
      }
    }

    // 3) Build holeScores[] with merged data.
    $holeScores = [];
    for ($h = 1; $h <= 18; $h++) {
      $holeScores[] = [
        'number' => $h,
        'yards' => $gc_hole_map[$h]['yards'] ?? NULL,
        'par' => $gc_hole_map[$h]['par'] ?? NULL,
        'handicap' => $gc_hole_map[$h]['handicap'] ?? NULL,
        'gross' => $gross_by_hole[$h] ?? NULL,

        // NEW:
        'putts' => $putts_by_hole[$h] ?? NULL,

        // Leaving these alone.
        'puttLength' => NULL,
        'club' => NULL,
        'drive' => NULL,
        'fir' => NULL,
        'upDown' => NULL,
        'sandSave' => NULL,
        'penalty' => NULL,
      ];
    }

    // 4) esc = sum of all numeric gross.
    $esc = $this->sumGross($gross_by_hole);

    return [
      'id' => NULL,
      'individualId' => $individualId ?: NULL,
      'date' => $date_iso,
      'courseId' => $courseId ?: NULL,
      'teeId' => $teeId ?: NULL,
      'holesPlayed' => $holesPlayed,
      'esc' => $esc,
      'holeScores' => $holeScores,
      'isHoleByHole' => $isHoleByHole,
      'isHoleByHoleRequired' => $isHoleByHoleRequired,
      'isTournament' => (bool) $isTournament,
      'isPenalty' => $isPenalty,
      'attestor' => $attestor,
      'isPlayedAlone' => (bool) $isPlayedAlone,
      'facilityId' => $facilityId ?: NULL,
    ];
  }

  /**
   * Pull the per-hole gross scores from the Grint scorecard table inputs.
   */
  protected function extractGrossByHoleFromFormValues(array $values): array {
    $input = [];
    try {
      $input = \Drupal::request()->request->all();
    }
    catch (\Throwable $e) {
      $input = [];
    }

    $scores_table = $this->findScoresTable($values);
    if (!is_array($scores_table)) {
      $scores_table = $this->findScoresTable($input);
    }

    $gross_by_hole = [];

    if (is_array($scores_table)) {
      // Front 1..9
      if (!empty($scores_table['front']['score']) && is_array($scores_table['front']['score'])) {
        foreach ($scores_table['front']['score'] as $k => $v) {
          if (!is_numeric($k)) {
            continue;
          }
          $hole = (int) $k;
          if ($hole < 1 || $hole > 9) {
            continue;
          }
          $gross_by_hole[$hole] = $this->toNullableInt($v);
        }
      }

      // Back 10..18 (keys 1..9 mapped to +9)
      if (!empty($scores_table['back']['score']) && is_array($scores_table['back']['score'])) {
        foreach ($scores_table['back']['score'] as $k => $v) {
          if (!is_numeric($k)) {
            continue;
          }
          $idx = (int) $k;
          if ($idx < 1 || $idx > 9) {
            continue;
          }
          $hole = $idx + 9;
          $gross_by_hole[$hole] = $this->toNullableInt($v);
        }
      }
    }

    return $gross_by_hole;
  }

  /**
   * NEW: Pull per-hole putts from the same scorecard inputs.
   */
  protected function extractPuttsByHoleFromFormValues(array $values): array {
    $input = [];
    try {
      $input = \Drupal::request()->request->all();
    }
    catch (\Throwable $e) {
      $input = [];
    }

    $scores_table = $this->findScoresTable($values);
    if (!is_array($scores_table)) {
      $scores_table = $this->findScoresTable($input);
    }

    $putts_by_hole = [];

    if (is_array($scores_table)) {
      // Front 1..9
      if (!empty($scores_table['front']['putts']) && is_array($scores_table['front']['putts'])) {
        foreach ($scores_table['front']['putts'] as $k => $v) {
          if (!is_numeric($k)) {
            continue;
          }
          $hole = (int) $k;
          if ($hole < 1 || $hole > 9) {
            continue;
          }
          $putts_by_hole[$hole] = $this->toNullableInt($v);
        }
      }

      // Back 10..18 (keys 1..9 mapped to +9)
      if (!empty($scores_table['back']['putts']) && is_array($scores_table['back']['putts'])) {
        foreach ($scores_table['back']['putts'] as $k => $v) {
          if (!is_numeric($k)) {
            continue;
          }
          $idx = (int) $k;
          if ($idx < 1 || $idx > 9) {
            continue;
          }
          $hole = $idx + 9;
          $putts_by_hole[$hole] = $this->toNullableInt($v);
        }
      }
    }

    return $putts_by_hole;
  }

  protected function findScoresTable($data): ?array {
    if (!is_array($data)) {
      return NULL;
    }

    if (isset($data['scores_table']) && is_array($data['scores_table'])) {
      if ($this->looksLikeScoresTable($data['scores_table'])) {
        return $data['scores_table'];
      }
    }

    if ($this->looksLikeScoresTable($data)) {
      return $data;
    }

    foreach ($data as $v) {
      if (is_array($v)) {
        $found = $this->findScoresTable($v);
        if (is_array($found)) {
          return $found;
        }
      }
    }

    return NULL;
  }

  protected function looksLikeScoresTable(array $t): bool {
    if (!isset($t['front']) || !isset($t['back'])) {
      return FALSE;
    }
    if (!is_array($t['front']) || !is_array($t['back'])) {
      return FALSE;
    }

    // Existing score table structure
    if (isset($t['front']['score']) && is_array($t['front']['score'])) {
      return TRUE;
    }
    if (isset($t['back']['score']) && is_array($t['back']['score'])) {
      return TRUE;
    }

    // NEW: allow putts-only structure to still be recognized as the scorecard payload table.
    if (isset($t['front']['putts']) && is_array($t['front']['putts'])) {
      return TRUE;
    }
    if (isset($t['back']['putts']) && is_array($t['back']['putts'])) {
      return TRUE;
    }

    return FALSE;
  }

  protected function sumGross(array $gross_by_hole): ?int {
    $sum = 0;
    $count = 0;

    foreach ($gross_by_hole as $v) {
      if (is_numeric($v)) {
        $sum += (int) $v;
        $count++;
      }
    }

    return $count > 0 ? $sum : NULL;
  }

  protected function toNullableInt($value): ?int {
    if ($value === NULL) {
      return NULL;
    }
    $s = trim((string) $value);
    if ($s === '') {
      return NULL;
    }
    if (!is_numeric($s)) {
      return NULL;
    }
    return (int) $s;
  }

  protected function holesModeToGcEnum(string $holes_mode): string {
    if ($holes_mode === 'front9') {
      return 'FrontNine';
    }
    if ($holes_mode === 'back9') {
      return 'BackNine';
    }
    return 'EighteenHoles';
  }

  protected function dateToIsoMs(string $date): string {
    $date = trim($date);
    if ($date === '') {
      return '';
    }
    return $date . 'T00:00:00.000';
  }

}
