<?php

namespace Drupal\gc_upload\Form;

use DOMDocument;
use DOMXPath;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class GcUploadStartForm extends FormBase {

  protected $grintAPI;

  public function getFormId() {
    return 'gc_upload_start_form';
  }

  protected function logDebug(string $message, array $context = []): void {
    \Drupal::logger('gc_upload')->notice($message, $context);
  }

  protected function effectiveValue(FormStateInterface $form_state, array $form, string $key): string {
    $v = $form_state->getValue($key);
    if ($v !== NULL && trim((string) $v) !== '') {
      return trim((string) $v);
    }
    if (isset($form[$key]['#default_value']) && trim((string) $form[$key]['#default_value']) !== '') {
      return trim((string) $form[$key]['#default_value']);
    }
    return '';
  }

  protected function parseGrintRoundLabel(string $label): array {
    $out = [
      'course_name' => '',
      'facility_name' => '',
      'tee_name' => '',
    ];

    $label = trim($label);
    if ($label === '') {
      return $out;
    }

    if (preg_match('/\bat\s+(.+?)\s*\|\s*(.+?)\s*\[([^\]]+)\]\s*$/i', $label, $m)) {
      $out['course_name'] = trim($m[1]);
      $out['facility_name'] = trim($m[2]);
      $out['tee_name'] = trim($m[3]);
      return $out;
    }

    if (preg_match('/\[(.*?)\]/', $label, $m)) {
      $out['tee_name'] = trim($m[1]);
    }

    $parts = array_map('trim', explode('|', $label));
    if (count($parts) >= 2) {
      if (preg_match('/\bat\s+(.+)$/i', $parts[0], $m2)) {
        $out['course_name'] = trim($m2[1]);
      }
      $out['facility_name'] = trim(preg_replace('/\[[^\]]+\]/', '', $parts[1]));
    }

    return $out;
  }

  /**
   * Convert a Grint date string like "Nov 2, 2025" into Y-m-d.
   * Returns '' if it can't be parsed.
   */
  protected function parseGrintDateTextToYmd(string $dateText): string {
    $dateText = trim($dateText);
    if ($dateText === '') {
      return '';
    }

    $ts = strtotime($dateText);
    if ($ts !== FALSE) {
      return date('Y-m-d', $ts);
    }

    $dt = \DateTime::createFromFormat('M j, Y', $dateText);
    if ($dt instanceof \DateTime) {
      return $dt->format('Y-m-d');
    }

    return '';
  }

  protected function extractTrailingId(string $value): string {
    $value = trim($value);
    if ($value === '') {
      return '';
    }

    // Leading "(12345) ..."
    if (preg_match('/^\(\s*(\d+)\s*\)\s*/', $value, $m)) {
      return (string) $m[1];
    }

    // Trailing "... (12345)"
    if (preg_match('/\((\d+)\)\s*$/', $value, $m)) {
      return (string) $m[1];
    }

    return '';
  }

  // --- KEEPING YOUR WORKING LOGIC EXACTLY AS-IS ---
  protected function resolveGcSelections(FormStateInterface $form_state, string $memberId, array $grint_meta): void {
    $facilityName = trim((string) ($grint_meta['facility_name'] ?? ''));
    $courseName = trim((string) ($grint_meta['course_name'] ?? ''));
    $teeName = trim((string) ($grint_meta['tee_name'] ?? ''));

    $form_state->setValue('gc_facility_id', '');
    $form_state->setValue('gc_course_id', '');
    $form_state->setValue('gc_tee_id', '');

    if ($facilityName === '' || $memberId === '') {
      return;
    }

    try {
      /** @var \Drupal\gc_api\Service\GolfCanadaApiService $gc */
      $gc = \Drupal::service('gc_api.golf_canada_api_service');

      $facilities = $gc->searchFacilities($facilityName, 10);

      $this->logDebug('GC facility search "@q" returned count=@c', [
        '@q' => $facilityName,
        '@c' => is_array($facilities) ? count($facilities) : 0,
      ]);

      $facilityId = '';

      if (is_array($facilities)) {
        foreach ($facilities as $f) {
          if (!isset($f['id'], $f['name'])) {
            continue;
          }
          if (strcasecmp(trim((string) $f['name']), $facilityName) === 0) {
            $facilityId = (string) $f['id'];
            break;
          }
        }
      }

      if ($facilityId === '' && is_array($facilities) && !empty($facilities[0]['id'])) {
        $facilityId = (string) $facilities[0]['id'];
      }

      if ($facilityId === '') {
        return;
      }

      $form_state->setValue('gc_facility_id', $facilityId);

      $courses = $gc->getCourses((int) $facilityId, (int) $memberId);

      $courseId = '';
      $teeId = '';

      if (is_array($courses)) {
        foreach ($courses as $c) {
          if (!isset($c['id'], $c['name'])) {
            continue;
          }
          if ($courseName !== '' && strcasecmp(trim((string) $c['name']), $courseName) === 0) {
            $courseId = (string) $c['id'];

            if (!empty($c['tees']) && is_array($c['tees'])) {
              foreach ($c['tees'] as $t) {
                if (!isset($t['id'], $t['name'])) {
                  continue;
                }
                if ($teeName !== '' && strcasecmp(trim((string) $t['name']), $teeName) === 0) {
                  $teeId = (string) $t['id'];
                  break;
                }
              }
            }
            break;
          }
        }

        if ($courseId === '' && !empty($courses[0]['id'])) {
          $courseId = (string) $courses[0]['id'];
        }

        if ($courseId !== '' && $teeId === '') {
          foreach ($courses as $c) {
            if ((string) ($c['id'] ?? '') === $courseId && !empty($c['tees'][0]['id'])) {
              $teeId = (string) $c['tees'][0]['id'];
              break;
            }
          }
        }
      }

      $form_state->setValue('gc_course_id', $courseId);
      $form_state->setValue('gc_tee_id', $teeId);

      $this->logDebug('GC resolved IDs facility=@f course=@c tee=@t from Grint meta facility="@fn" course="@cn" tee="@tn"', [
        '@f' => $facilityId,
        '@c' => $courseId,
        '@t' => $teeId,
        '@fn' => $facilityName,
        '@cn' => $courseName,
        '@tn' => $teeName,
      ]);
    }
    catch (\Throwable $e) {
      $this->logDebug('GC resolve error: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  protected function applyPostFieldsStateChanges(array &$form, FormStateInterface $form_state): void {
    $mode = (string) $form_state->get('mode');
    if ($mode !== 'loaded') {
      return;
    }

    $trigger = $form_state->getTriggeringElement();
    $trigger_name = (string) ($trigger['#name'] ?? '');

    if ($trigger_name === 'club') {
      $club_value = trim((string) $form_state->getValue('club'));
      $facility_id = $this->extractTrailingId($club_value);

      $this->logDebug('Club changed: club="@club" resolved facility_id="@fid" -> clearing course/tee', [
        '@club' => $club_value,
        '@fid' => $facility_id,
      ]);

      $form_state->setValue('gc_facility_id', $facility_id);

      $form_state->setValue('gc_course_id', '');
      $form_state->setValue('gc_tee_id', '');

      $form_state->setValue('course', '');
      $form_state->setValue('tee', '');

      $input = $form_state->getUserInput();
      $input['course'] = '';
      $input['tee'] = '';
      $form_state->setUserInput($input);

      return;
    }

    if ($trigger_name === 'course') {
      $course_id = (string) $form_state->getValue('course');
      $this->logDebug('Course changed: course_id="@cid"', ['@cid' => $course_id]);

      $form_state->setValue('gc_course_id', $course_id);
      $form_state->setValue('gc_tee_id', '');
      return;
    }

    if ($trigger_name === 'tee') {
      $tee_id = (string) $form_state->getValue('tee');
      $this->logDebug('Tee changed: tee_id="@tid"', ['@tid' => $tee_id]);
      $form_state->setValue('gc_tee_id', $tee_id);
      return;
    }
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->grintAPI = \Drupal::service('grint_api.grint_api_service');

    if (!$form_state->has('mode')) {
      $form_state->set('mode', 'choose');
    }
    if (!$form_state->has('round_wave')) {
      $form_state->set('round_wave', 0);
    }

    $mode = (string) $form_state->get('mode');

    if ($form_state->isRebuilding() && $mode === 'loaded') {
      $this->applyPostFieldsStateChanges($form, $form_state);
    }

    $account = $this->currentUser();
    $defaults = [
      'grint_user_id' => '',
      'gc_id' => '',
    ];

    if ($account->isAuthenticated()) {
      $user = User::load($account->id());
      if ($user) {
        if ($user->hasField('field_grint_userid') && !$user->get('field_grint_userid')->isEmpty()) {
          $defaults['grint_user_id'] = (string) $user->get('field_grint_userid')->value;
        }
        if ($user->hasField('field_gc_id') && !$user->get('field_gc_id')->isEmpty()) {
          $defaults['gc_id'] = (string) $user->get('field_gc_id')->value;
        }
      }
    }

    $form['#prefix'] = '<div id="gc-upload-start-form">';
    $form['#suffix'] = '</div>';

    $header_type = ($mode === 'loaded') ? 'hidden' : 'textfield';

    $form['grint_user_id'] = [
      '#type' => $header_type,
      '#title' => $this->t('Grint User ID'),
      '#default_value' => $form_state->getValue('grint_user_id') ?? $defaults['grint_user_id'],
      '#ajax' => [
        'callback' => '::ajaxRefreshForm',
        'wrapper' => 'gc-upload-start-form',
        'event' => 'change',
        'progress' => ['type' => 'throbber'],
      ],
    ];

    $form['gc_id'] = [
      '#type' => $header_type,
      '#title' => $this->t('Golf Canada ID'),
      '#default_value' => $form_state->getValue('gc_id') ?? $defaults['gc_id'],
      '#ajax' => [
        'callback' => '::ajaxRefreshForm',
        'wrapper' => 'gc-upload-start-form',
        'event' => 'change',
        'progress' => ['type' => 'throbber'],
      ],
    ];

    // Wave (paging) state for Grint feed.
    $form['round_wave'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->get('round_wave') ?? 0),
    ];

    $form['loaded_round_id'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->getValue('loaded_round_id') ?? ''),
    ];

    $form['grint_facility_name'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->getValue('grint_facility_name') ?? ''),
    ];
    $form['grint_course_name'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->getValue('grint_course_name') ?? ''),
    ];
    $form['grint_tee_name'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->getValue('grint_tee_name') ?? ''),
    ];

    $form['gc_facility_id'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->getValue('gc_facility_id') ?? ''),
    ];
    $form['gc_course_id'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->getValue('gc_course_id') ?? ''),
    ];
    $form['gc_tee_id'] = [
      '#type' => 'hidden',
      '#value' => (string) ($form_state->getValue('gc_tee_id') ?? ''),
    ];

    $form['rounds_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'gc-upload-rounds-wrapper'],
    ];

    $form['rounds_wrapper'] += $this->buildRoundsSection($form, $form_state);

    return $form;
  }

  protected function buildRoundsSection(array &$form, FormStateInterface $form_state): array {
    $section = [];

    $mode = (string) $form_state->get('mode');

    $grint_uid = $this->effectiveValue($form_state, $form, 'grint_user_id');
    $gc_id = $this->effectiveValue($form_state, $form, 'gc_id');

    $this->logDebug('Header effective values: grint_uid=@grint_uid gc_id=@gc_id mode=@mode wave=@wave', [
      '@grint_uid' => $grint_uid,
      '@gc_id' => $gc_id,
      '@mode' => $mode,
      '@wave' => (int) ($form_state->get('round_wave') ?? 0),
    ]);

    if ($grint_uid === '' || $gc_id === '') {
      $section['help'] = [
        '#type' => 'markup',
        '#markup' => '<div class="gc-upload-help">Fill in Grint User ID and Golf Canada ID to load rounds.</div>',
      ];
      return $section;
    }

    if ($mode === 'loaded') {
      $loaded_round_id = (int) $form_state->getValue('loaded_round_id');

      $section['actions_top'] = ['#type' => 'actions'];
      $section['actions_top']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back (Choose Different Round)'),
        '#submit' => ['::backToRoundChooser'],
        '#ajax' => [
          'callback' => '::ajaxRefreshForm',
          'wrapper' => 'gc-upload-start-form',
          'progress' => ['type' => 'throbber'],
        ],
        '#limit_validation_errors' => [],
      ];

      $section['loaded_header'] = [
        '#type' => 'markup',
        '#markup' => '<div class="gc-upload-loaded"><strong>Loaded round:</strong> ' . (int) $loaded_round_id . '</div>',
      ];

      $section['loaded_wrap'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['gc-upload-scorecard']],
      ];

      try {
        $scores = $this->grintAPI->getRoundScore($loaded_round_id);

        $this->logDebug('getRoundScore returned type=@type keys=@keys', [
          '@type' => gettype($scores),
          '@keys' => is_array($scores) ? implode(',', array_slice(array_keys($scores), 0, 25)) : '',
        ]);

        if (!is_array($scores)) {
          throw new \RuntimeException('getRoundScore did not return an array.');
        }

        $builder = \Drupal::service('gc_upload.scorecard_builder');

        $meta = [
          'course_id' => '',
          'tee_color' => (string) ($form_state->getValue('grint_tee_name') ?? ''),
          'course_name' => (string) ($form_state->getValue('grint_course_name') ?? ''),
          'facility_name' => (string) ($form_state->getValue('grint_facility_name') ?? ''),
        ];

        $section['loaded_wrap']['scorecard'] = $builder->buildScorecard($scores, $meta, $grint_uid);

        $section['loaded_wrap']['post_fields_wrapper'] = [
          '#type' => 'container',
          '#attributes' => ['id' => 'gc-upload-post-fields-wrapper'],
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields'] = [
          '#type' => 'details',
          '#title' => $this->t('Post Score Details'),
          '#open' => TRUE,
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['club'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Club'),
          '#default_value' => (string) ($form_state->getValue('club') ?? $form_state->getValue('grint_facility_name') ?? ''),
          '#autocomplete_route_name' => 'gc_api.facility_autocomplete',
          '#description' => $this->t('Start typing the club name.'),
          '#ajax' => [
            'callback' => '::ajaxRefreshPostFields',
            'wrapper' => 'gc-upload-post-fields-wrapper',
            'event' => 'autocompleteclose',
            'progress' => ['type' => 'throbber'],
          ],
        ];

        $course_options = ['' => $this->t('- Select -')];
        $tee_options = ['' => $this->t('- Select -')];

        $facility_id = (string) ($form_state->getValue('gc_facility_id') ?? '');
        $selected_course_id = (string) ($form_state->getValue('course') ?? $form_state->getValue('gc_course_id') ?? '');
        $selected_tee_id = (string) ($form_state->getValue('tee') ?? $form_state->getValue('gc_tee_id') ?? '');

        $courses_payload = [];
        if ($facility_id !== '') {
          try {
            /** @var \Drupal\gc_api\Service\GolfCanadaApiService $gc */
            $gc = \Drupal::service('gc_api.golf_canada_api_service');
            $courses_payload = $gc->getCourses((int) $facility_id, (int) $gc_id);
          }
          catch (\Throwable $e) {
            $this->logDebug('Error fetching GC courses: @msg', ['@msg' => $e->getMessage()]);
          }
        }

        if (is_array($courses_payload)) {
          foreach ($courses_payload as $c) {
            if (isset($c['id'], $c['name'])) {
              $course_options[(string) $c['id']] = (string) $c['name'];
            }
          }

          if ($selected_course_id !== '') {
            foreach ($courses_payload as $c) {
              if ((string) ($c['id'] ?? '') === $selected_course_id && !empty($c['tees']) && is_array($c['tees'])) {
                foreach ($c['tees'] as $t) {
                  if (isset($t['id'], $t['name'])) {
                    $tee_options[(string) $t['id']] = (string) $t['name'];
                  }
                }
                break;
              }
            }
          }
        }

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['course'] = [
          '#type' => 'select',
          '#title' => $this->t('Course'),
          '#options' => $course_options,
          '#default_value' => $selected_course_id,
          '#ajax' => [
            'callback' => '::ajaxRefreshPostFields',
            'wrapper' => 'gc-upload-post-fields-wrapper',
            'event' => 'change',
            'progress' => ['type' => 'throbber'],
          ],
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['tee'] = [
          '#type' => 'select',
          '#title' => $this->t('Tee'),
          '#options' => $tee_options,
          '#default_value' => $selected_tee_id,
          '#ajax' => [
            'callback' => '::ajaxRefreshPostFields',
            'wrapper' => 'gc-upload-post-fields-wrapper',
            'event' => 'change',
            'progress' => ['type' => 'throbber'],
          ],
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['holes_mode'] = [
          '#type' => 'radios',
          '#title' => $this->t('Holes'),
          '#options' => [
            '18' => $this->t('18 Holes'),
            'front9' => $this->t('Front 9'),
            'back9' => $this->t('Back 9'),
          ],
          '#default_value' => (string) ($form_state->getValue('holes_mode') ?? '18'),
        ];

        // Date defaults to the selected round date (if available), otherwise today.
        $today = new DrupalDateTime('now');
        $selected_round_date = (string) ($form_state->get('selected_round_date_ymd') ?? '');
        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['played_date'] = [
          '#type' => 'date',
          '#title' => $this->t('Date'),
          '#default_value' => (string) ($form_state->getValue('played_date') ?? ($selected_round_date !== '' ? $selected_round_date : $today->format('Y-m-d'))),
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['format'] = [
          '#type' => 'radios',
          '#title' => $this->t('Format'),
          '#options' => [
            'stroke' => $this->t('Stroke Play'),
            'match' => $this->t('Match Play'),
          ],
          '#default_value' => (string) ($form_state->getValue('format') ?? 'stroke'),
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['tournament_score'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Tournament Score'),
          '#default_value' => (int) ($form_state->getValue('tournament_score') ?? 0),
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['attestor'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Attestor'),
          '#default_value' => (string) ($form_state->getValue('attestor') ?? ''),
        ];

        $section['loaded_wrap']['post_fields_wrapper']['post_fields']['played_alone'] = [
          '#type' => 'radios',
          '#title' => $this->t('Played Alone'),
          '#options' => [
            'no' => $this->t('No'),
            'yes' => $this->t('Yes'),
          ],
          '#default_value' => (string) ($form_state->getValue('played_alone') ?? 'no'),
        ];

        $section['loaded_wrap']['actions_bottom'] = ['#type' => 'actions'];
        $section['loaded_wrap']['actions_bottom']['post_score'] = [
          '#type' => 'submit',
          '#value' => $this->t('Post Score'),
          '#button_type' => 'primary',
          '#submit' => ['::postScoreSubmit'],
        ];
      }
      catch (\Throwable $e) {
        $this->logDebug('Error building loaded scorecard: @msg', ['@msg' => $e->getMessage()]);
        $section['error'] = [
          '#type' => 'markup',
          '#markup' => '<div class="gc-upload-error">Could not load scorecard. Check logs.</div>',
        ];
      }

      return $section;
    }

    // CHOOSE MODE (with paging via wave).
    try {
      $wave = (int) ($form_state->get('round_wave') ?? 0);

      // Backward compatible call: wave only included if > 0.
      $html = $this->grintAPI->getRoundFeed($grint_uid, $wave > 0 ? $wave : NULL);

      $rounds = $this->extractRounds($html);
      $rounds = array_slice($rounds, 0, 30);

      if (empty($rounds)) {
        $section['none'] = [
          '#type' => 'markup',
          '#markup' => '<div class="gc-upload-none">No rounds found for that user.</div>',
        ];
        return $section;
      }

      // Pager buttons.
      $section['round_pager'] = [
        '#type' => 'actions',
        '#attributes' => ['class' => ['gc-upload-round-pager']],
      ];



      $section['round_pager']['older'] = [
        '#type' => 'submit',
        '#value' => $this->t('Older rounds'),
        '#submit' => ['::roundPagerOlderSubmit'],
        '#ajax' => [
          'callback' => '::ajaxRefreshForm',
          'wrapper' => 'gc-upload-start-form',
          'progress' => ['type' => 'throbber'],
        ],
        '#limit_validation_errors' => [
          ['grint_user_id'],
          ['gc_id'],
        ],
      ];
      $section['round_pager']['newer'] = [
        '#type' => 'submit',
        '#value' => $this->t('Newer rounds'),
        '#submit' => ['::roundPagerNewerSubmit'],
        '#ajax' => [
          'callback' => '::ajaxRefreshForm',
          'wrapper' => 'gc-upload-start-form',
          'progress' => ['type' => 'throbber'],
        ],
        '#limit_validation_errors' => [
          ['grint_user_id'],
          ['gc_id'],
        ],
        '#disabled' => ($wave <= 0),
      ];

      $options = [];
      $round_meta_map = [];
      $round_date_map = [];

      foreach ($rounds as $round) {
        $rid = (string) $round['numberPost'];
        $label = trim((string) ($round['linkText'] ?? ''));
        $dateText = trim((string) ($round['dateText'] ?? ''));

        $display = ($label !== '' ? $label : ('Round ' . $rid)) . ($dateText !== '' ? (' — ' . $dateText) : '');
        $options[$rid] = $display;

        $round_meta_map[$rid] = $this->parseGrintRoundLabel($label);
        $round_date_map[$rid] = [
          'dateText' => $dateText,
          'ymd' => $this->parseGrintDateTextToYmd($dateText),
        ];
      }

      $form_state->set('round_meta_map', $round_meta_map);
      $form_state->set('round_date_map', $round_date_map);

      $default_selected = (string) ($form_state->getValue('selected_round') ?: array_key_first($options));

      $section['selected_round'] = [
        '#type' => 'radios',
        '#title' => $this->t('Choose a round'),
        '#options' => $options,
        '#default_value' => $default_selected,
      ];

      $section['actions'] = ['#type' => 'actions'];
      $section['actions']['load_round'] = [
        '#type' => 'submit',
        '#value' => $this->t('Load round'),
        '#button_type' => 'primary',
        '#submit' => ['::loadRoundSubmit'],
        '#ajax' => [
          'callback' => '::ajaxRefreshForm',
          'wrapper' => 'gc-upload-start-form',
          'progress' => ['type' => 'throbber'],
        ],
        '#limit_validation_errors' => [
          ['grint_user_id'],
          ['gc_id'],
          ['selected_round'],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logDebug('Error loading rounds: @msg', ['@msg' => $e->getMessage()]);
      $section['error'] = [
        '#type' => 'markup',
        '#markup' => '<div class="gc-upload-error">Could not load rounds (API error). Check logs.</div>',
      ];
    }

    return $section;
  }

  public function ajaxRefreshForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form;
  }

  public function ajaxRefreshPostFields(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['rounds_wrapper']['loaded_wrap']['post_fields_wrapper'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Keep simple for now.
  }

  public function roundPagerOlderSubmit(array &$form, FormStateInterface $form_state) {
    $wave = (int) ($form_state->get('round_wave') ?? 0);
    $form_state->set('round_wave', $wave + 1);
    $form_state->setValue('selected_round', NULL);
    $form_state->setRebuild(TRUE);
  }

  public function roundPagerNewerSubmit(array &$form, FormStateInterface $form_state) {
    $wave = (int) ($form_state->get('round_wave') ?? 0);
    $form_state->set('round_wave', max(0, $wave - 1));
    $form_state->setValue('selected_round', NULL);
    $form_state->setRebuild(TRUE);
  }

  public function loadRoundSubmit(array &$form, FormStateInterface $form_state) {
    $selected_round = (string) $form_state->getValue('selected_round');
    $gc_id = (string) $form_state->getValue('gc_id');

    $this->logDebug('Load round clicked. selected_round=@rid', ['@rid' => $selected_round]);

    $form_state->setValue('loaded_round_id', (int) $selected_round);

    $round_meta_map = (array) $form_state->get('round_meta_map');
    $meta = $round_meta_map[$selected_round] ?? ['facility_name' => '', 'course_name' => '', 'tee_name' => ''];

    $form_state->setValue('grint_facility_name', (string) ($meta['facility_name'] ?? ''));
    $form_state->setValue('grint_course_name', (string) ($meta['course_name'] ?? ''));
    $form_state->setValue('grint_tee_name', (string) ($meta['tee_name'] ?? ''));

    $this->logDebug('Grint meta from label: facility="@f" course="@c" tee="@t"', [
      '@f' => (string) ($meta['facility_name'] ?? ''),
      '@c' => (string) ($meta['course_name'] ?? ''),
      '@t' => (string) ($meta['tee_name'] ?? ''),
    ]);

    $this->resolveGcSelections($form_state, $gc_id, $meta);

    // NEW: Set played_date from selected round date if available.
    $round_date_map = (array) $form_state->get('round_date_map');
    $ymd = (string) ($round_date_map[$selected_round]['ymd'] ?? '');
    if ($ymd !== '') {
      $form_state->set('selected_round_date_ymd', $ymd);
      $form_state->setValue('played_date', $ymd);
      $this->logDebug('Selected round date parsed: @ymd', ['@ymd' => $ymd]);
    }
    else {
      $form_state->set('selected_round_date_ymd', '');
      $this->logDebug('Selected round date could not be parsed.');
    }

    $form_state->set('mode', 'loaded');
    $form_state->setRebuild(TRUE);
  }

  public function backToRoundChooser(array &$form, FormStateInterface $form_state) {
    $this->logDebug('Back to chooser clicked.');
    $form_state->set('mode', 'choose');
    $form_state->setValue('loaded_round_id', '');
    $form_state->setValue('grint_facility_name', '');
    $form_state->setValue('grint_course_name', '');
    $form_state->setValue('grint_tee_name', '');
    $form_state->setValue('gc_facility_id', '');
    $form_state->setValue('gc_course_id', '');
    $form_state->setValue('gc_tee_id', '');
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit: build a JSON payload using the external builder service, then log it.
   */
  public function postScoreSubmit(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\gc_upload\Service\GcUploadPostScorePayloadBuilder $builder */
    $builder = \Drupal::service('gc_upload.post_score_payload_builder');
    $payload = $builder->build($form_state);

    // Post to GC.
    /** @var \Drupal\gc_api\Service\GolfCanadaApiService $gc */
    $gc = \Drupal::service('gc_api.golf_canada_api_service');
    $result = $gc->postScore($payload);

    // Log everything (payload + result).
    $this->logDebug("GC Upload payload JSON:\n@json", [
      '@json' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ]);
    $this->logDebug("GC postScore() result:\n@json", [
      '@json' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ]);

    if (!empty($result['ok'])) {
      \Drupal::messenger()->addStatus($this->t('Score posted to Golf Canada successfully.'));
    }
    else {
      $msg = !empty($result['error']) ? (string) $result['error'] : 'Unknown error';
      \Drupal::messenger()->addError($this->t('Golf Canada post failed: @msg', ['@msg' => $msg]));
    }
  }

  protected function extractRounds($html): array {
    $rounds = [];

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $divNodes = $xpath->query("//div[contains(@class, 'newsfeed-container')][@number-post]");

    foreach ($divNodes as $divNode) {
      // This is sometimes a feed post id (NOT always the round id).
      $numberPost = $divNode->getAttribute('number-post');

      $linkNode = $xpath->query(".//a[contains(@class, 'newsfeed-link-message')]", $divNode)->item(0);
      $href = $linkNode ? (string) $linkNode->getAttribute('href') : '';
      $linkText = $linkNode ? (string) $linkNode->nodeValue : '';
      $linkText = trim($linkText);

      // Your existing behavior: remove leading "Score of " (first 9 chars).
      $linkTextRemoved = $linkText !== '' ? substr($linkText, 9) : '';
      $linkTextRemoved = trim((string) $linkTextRemoved);

      // IMPORTANT: Extract real round id from href if present.
      // Supports both /review_score/{id} and /score/review_score/{id}
      $roundId = '';
      if ($href !== '' && preg_match('~/(?:score/)?review_score/(\d+)~', $href, $m)) {
        $roundId = (string) $m[1];
      }

      // Prefer real round id; fallback to number-post if not found.
      $effectiveId = $roundId !== '' ? $roundId : $numberPost;

      $dateNode = $xpath->query(".//span[contains(@class, 'newsfeed-date')]", $divNode)->item(0);
      $dateText = $dateNode ? trim((string) $dateNode->nodeValue) : '';

      if ((int) $effectiveId > 0) {
        $rounds[] = [
          'numberPost' => $effectiveId,
          'linkText' => ucfirst($linkTextRemoved),
          'dateText' => $dateText,
        ];
      }
    }

    return $rounds;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No-op. We use postScoreSubmit().
  }

}
