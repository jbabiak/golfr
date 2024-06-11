<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberThreeBounce.
 *
 * @Throbber(
 *   id = "throbber_three_bounce",
 *   label = @Translation("Three bounce")
 * )
 */
class ThrobberThreeBounce extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-flow">
              <div class="sk-flow-dot"></div>
              <div class="sk-flow-dot"></div>
              <div class="sk-flow-dot"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/three-bounce.css';
  }

}
