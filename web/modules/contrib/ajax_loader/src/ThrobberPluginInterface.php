<?php

namespace Drupal\ajax_loader;

/**
 * Interface ThrobberInterface.
 */
interface ThrobberPluginInterface {

  /**
   * Returns markup for throbber.
   */
  public function getMarkup();

  /**
   * Returns path to css file.
   */
  public function getCssFile();

  /**
   * Returns human readable label.
   */
  public function getLabel();

}
