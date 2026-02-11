<?php

namespace Drupal\gc_upload\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GcUploadAutocompleteController extends ControllerBase {

  /**
   * Course autocomplete.
   * For now: uses Grint course search as placeholder until gc_api is available.
   * Returns: [{value: "...", label: "..."}]
   */
  public function course(Request $request): JsonResponse {
    $q = trim((string) $request->query->get('q'));
    if ($q === '') {
      return new JsonResponse([]);
    }

    $results = [];
    try {
      $grint = \Drupal::service('grint_api.grint_api_service');
      $courses = $grint->searchCourse($q); // returns JSON array

      // Try to format options nicely.
      if (is_array($courses)) {
        foreach ($courses as $c) {
          // Common shapes: {id, name} or {value, label} depending on endpoint.
          $id = $c->id ?? ($c->value ?? NULL);
          $name = $c->name ?? ($c->label ?? $c->text ?? NULL);

          if ($id && $name) {
            // Important: put (id) prefix so we can parse it server-side later.
            $label = '(' . $id . ') ' . $name;
            $results[] = ['value' => $label, 'label' => $label];
          }
        }
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('gc_upload')->notice('Course autocomplete failed: @msg', ['@msg' => $e->getMessage()]);
    }

    return new JsonResponse($results);
  }

  /**
   * Tee autocomplete.
   * Depends on course_id passed via ?course_id=12345
   * For now: uses Grint tee colors endpoint.
   */
  public function tee(Request $request): JsonResponse {
    $q = trim((string) $request->query->get('q'));
    $course_id = trim((string) $request->query->get('course_id'));

    if ($q === '' || $course_id === '' || !is_numeric($course_id)) {
      return new JsonResponse([]);
    }

    $results = [];
    try {
      $grint = \Drupal::service('grint_api.grint_api_service');
      $tees = $grint->searchCourseTeeColors((int) $course_id); // array keyed by color

      if (is_array($tees)) {
        foreach ($tees as $color => $data) {
          // Simple "contains" filter for autocomplete typing.
          if ($q === '' || stripos((string) $color, $q) !== FALSE) {
            $results[] = ['value' => (string) $color, 'label' => (string) $color];
          }
        }
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('gc_upload')->notice('Tee autocomplete failed: @msg', ['@msg' => $e->getMessage()]);
    }

    return new JsonResponse($results);
  }

}
