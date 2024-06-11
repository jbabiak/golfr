<?php

namespace Drupal\ajax_loader\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Throbber item annotation object.
 *
 * @Annotation
 */
class Throbber extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
