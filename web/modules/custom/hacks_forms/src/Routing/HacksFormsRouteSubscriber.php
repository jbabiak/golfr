<?php

namespace Drupal\hacks_forms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class HacksFormsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Remove the "Reset your password" tab from the user login page.
    if ($route = $collection->get('user.pass')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
