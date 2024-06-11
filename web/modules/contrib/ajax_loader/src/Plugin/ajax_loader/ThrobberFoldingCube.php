<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberFoldingCube.
 *
 * @Throbber(
 *   id = "throbber_folding_cube",
 *   label = @Translation("Folding cube")
 * )
 */
class ThrobberFoldingCube extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-fold">
              <div class="sk-fold-cube"></div>
              <div class="sk-fold-cube"></div>
              <div class="sk-fold-cube"></div>
              <div class="sk-fold-cube"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/folding-cube.css';
  }

}
