<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberPulse.
 *
 * @Throbber(
 *   id = "throbber_pulse",
 *   label = @Translation("Pulse")
 * )
 */
class ThrobberPulse extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-pulse"></div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/pulse.css';
  }

}
