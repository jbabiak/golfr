<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberDoubleBounce.
 *
 * @Throbber(
 *   id = "throbber_double_bounce",
 *   label = @Translation("Double bounce")
 * )
 */
class ThrobberDoubleBounce extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-bounce">
              <div class="sk-bounce-dot"></div>
              <div class="sk-bounce-dot"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/double-bounce.css';
  }

}
