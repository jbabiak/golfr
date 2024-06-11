<?php

namespace Drupal\ajax_loader\Plugin\ajax_loader;

use Drupal\ajax_loader\ThrobberPluginBase;

/**
 * Class ThrobberCubeGrid.
 *
 * @Throbber(
 *   id = "throbber_cube_grid",
 *   label = @Translation("Cube gird")
 * )
 */
class ThrobberCubeGrid extends ThrobberPluginBase {

  /**
   * Function to set markup.
   *
   * @inheritdoc
   */
  protected function setMarkup() {
    return '<div class="ajax-throbber sk-grid">
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
              <div class="sk-grid-cube"></div>
            </div>';
  }

  /**
   * Function to set css file.
   *
   * @inheritdoc
   */
  protected function setCssFile() {
    return $this->path . '/css/cube-grid.css';
  }

}
