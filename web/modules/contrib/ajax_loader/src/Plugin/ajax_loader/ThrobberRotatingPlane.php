<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberRotatingPlane.
 *
 * @Throbber(
 *   id = "throbber_rotating_plane",
 *   label = @Translation("Rotating plane")
 * )
 */
class ThrobberRotatingPlane extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-plane"></div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/rotating-plane.css';
  }

}
