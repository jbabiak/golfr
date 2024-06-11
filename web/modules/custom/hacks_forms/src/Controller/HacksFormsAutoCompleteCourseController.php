<?php

namespace Drupal\hacks_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class HacksFormsAutoCompleteCourseController extends ControllerBase
{

  public function autocomplete(Request $request)
  {
    $input = $request->query->get('q');

    // Fetch data from external API based on the input.
    $externalData = $this->fetchExternalData($input);

    return new JsonResponse($externalData);
  }

  private function fetchExternalData($input)
  {
    // Your logic to call the external API and get data based on $input.
    // Return the data in a format suitable for autocomplete.
    $grintAPI = \Drupal::service('grint_api.grint_api_service');

    try {
      $request = $grintAPI->searchCourse($input);
      $options = [];
      foreach ($request as $item) {
        $options[] = [
          'value' => '('.$item->id.') ' . $item->name,
          'label' => $item->name . ' [' . $item->city .', ' . $item->state .']'
        ];
      }
      // Process and return data in the correct format for select options.
      // Assuming the API returns a simple list of options.
      return $options;
    } catch (Exception $e) {
      return [];
    }
  }
}
