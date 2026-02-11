<?php

namespace Drupal\gc_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GcApiAutocompleteController extends ControllerBase {

  /**
   * Facility/Club autocomplete.
   * Returns Drupal core autocomplete format: [{value:"...", label:"..."}]
   */
  public function facility(Request $request): JsonResponse {
    $q = trim((string) $request->query->get('q'));
    if ($q === '') {
      return new JsonResponse([]);
    }

    try {
      /** @var \Drupal\gc_api\Service\GolfCanadaApiService $gc */
      $gc = \Drupal::service('gc_api.golf_canada_api_service');
      $facilities = $gc->searchFacilities($q, 10, 'RCGA');

      $out = [];
      foreach ($facilities as $f) {
        $id = $f['id'] ?? NULL;
        $name = $f['name'] ?? '';
        $city = $f['city'] ?? '';
        $region = $f['region'] ?? '';
        if ($id && $name) {
          // Put the facility id in a parseable prefix.
          $label = '(' . $id . ') ' . $name;
          if ($city || $region) {
            $label .= ' — ' . trim($city . ' ' . $region);
          }
          $out[] = ['value' => $label, 'label' => $label];
        }
      }

      return new JsonResponse($out);
    }
    catch (\Throwable $e) {
      \Drupal::logger('gc_api')->notice('Facility autocomplete failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([]);
    }
  }

}
