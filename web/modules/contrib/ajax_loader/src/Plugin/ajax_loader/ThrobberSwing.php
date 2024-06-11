<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberSwing.
 *
 * @Throbber(
 *   id = "throbber_swing",
 *   label = @Translation("Swing")
 * )
 */
class ThrobberSwing extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-swing">
              <div class="sk-swing-dot"></div>
              <div class="sk-swing-dot"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/swing.css';
  }

}
