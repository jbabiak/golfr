<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberWanderingCubes.
 *
 * @Throbber(
 *   id = "throbber_wandering_cubes",
 *   label = @Translation("Wandering cubes")
 * )
 */
class ThrobberWanderingCubes extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-wander">
              <div class="sk-wander-cube"></div>
              <div class="sk-wander-cube"></div>
              <div class="sk-wander-cube"></div>
              <div class="sk-wander-cube"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/wandering-cubes.css';
  }

}
