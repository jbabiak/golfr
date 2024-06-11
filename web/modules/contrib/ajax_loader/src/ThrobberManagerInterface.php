<?php

namespace Drupal\ajax_loader;

/**
 * Interface for the class that gathers the throbber plugins.
 */
interface ThrobberManagerInterface {

  /**
   * Returns the definition of a plugin by a given plugin ID.
   *
   * @param string $plugin_id
   *   String with plugin id.
   * @param mixed $exception_on_invalid
   *   Exception on invalid.
   *
   * @return mixed
   *   Return Definition.
   */
  public function getDefinition($plugin_id, $exception_on_invalid);

  /**
   * Get an options list suitable for form elements for throbber selection.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getThrobberOptionList();

  /**
   * Loads an instance of a plugin by given plugin id.
   *
   * @param string $plugin_id
   *   String with plugin id.
   *
   * @return object
   *   Return object with Throbber.
   */
  public function loadThrobberInstance($plugin_id);

  /**
   * Loads all available throbbers.
   *
   * @return mixed
   *   Return incative for All Throbber Instances.
   */
  public function loadAllThrobberInstances();

  /**
   * Checks if ajax loader has to be included on current page.
   *
   * @return mixed
   *   Return the indicative if Route is Applicable.
   *
   * @codingStandardsIgnoreStart
   */
  public function RouteIsApplicable();
    // @codingStandardsIgnoreEnd

}
