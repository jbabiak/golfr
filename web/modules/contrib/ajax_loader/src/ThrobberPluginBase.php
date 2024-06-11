<?php

namespace Drupal\ajax_loader;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ThrobberBase.
 */
abstract class ThrobberPluginBase extends PluginBase implements ThrobberPluginInterface, ContainerFactoryPluginInterface {

  protected $path;
  protected $markup;
  /** @codingStandardsIgnoreLine. */
  protected $css_file;
  protected $label;

  /**
   * ThrobberPluginBase constructor.
   *
   * @param array $configuration
   *   Array with configuration.
   * @param string $plugin_id
   *   String with plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition value.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleExtensionList $extensionList) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->path = '/' . $extensionList->getPath('ajax_loader');
    $this->markup = $this->setMarkup();
    $this->css_file = $this->setCssFile();
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.module'),
    );
  }

  /**
   * Function to get markup.
   *
   * @return mixed
   *   Return markup.
   */
  public function getMarkup() {
    return $this->markup;
  }

  /**
   * Function to get css file.
   *
   * @return mixed
   *   Return the css file.
   */
  public function getCssFile() {
    return $this->css_file;
  }

  /**
   * Function to get label.
   *
   * @return mixed
   *   Return the label.
   */
  public function getLabel() {
    return $this->configuration['label'];
  }

  /**
   * Sets markup for throbber.
   */
  abstract protected function setMarkup();

  /**
   * Sets css file for throbber.
   */
  abstract protected function setCssFile();

}
