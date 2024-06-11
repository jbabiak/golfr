<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberCircle.
 *
 * @Throbber(
 *   id = "throbber_circle",
 *   label = @Translation("Circle")
 * )
 */
class ThrobberCircle extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-circle">
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
              <div class="sk-circle-dot"></div>
            </div>';
  }

  /**
   * Function set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/circle.css';
  }

}
