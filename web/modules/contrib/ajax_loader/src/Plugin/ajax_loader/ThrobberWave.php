<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberWave.
 *
 * @Throbber(
 *   id = "throbber_wave",
 *   label = @Translation("Wave")
 * )
 */
class ThrobberWave extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-wave">
              <div class="sk-wave-rect"></div>
              <div class="sk-wave-rect"></div>
              <div class="sk-wave-rect"></div>
              <div class="sk-wave-rect"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/wave.css';
  }

}
