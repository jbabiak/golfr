<?php

namespace Drupal\hacks_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class HacksFormsAutoCompleteTeeColorController extends ControllerBase {

  public function getSelectOptions($selectedValue) {
    $grintAPI = \Drupal::service('grint_api.grint_api_service');
    $course_id = $grintAPI->getCourseIdFromString($selectedValue);
    $request = $grintAPI->searchCourseTeeColors($course_id);
    $colors = [];
    foreach ($request  as $tee) {
      $colors[$tee['value']] = '['.$tee['value'] . '] MR: '.$tee['mr'].' / MS: '.$tee['ms'] . ' LR: '.$tee['lr'].' / LS: '.$tee['ls'];
    }
    return new JsonResponse($colors);
  }
}
