<?php

namespace Drupal\imagecache_external\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the imagecache_external_flush_images queue worker.
 *
 * @QueueWorker (
 *   id = "imagecache_external_flush_images",
 *   title = @Translation("Imagechache external flush images."),
 *   cron = {"time" = 30}
 * )
 */
class FlushImagesQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * FileSystemInterface
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * FlushImagesQueue constructor.
   *
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.imagecache_external'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    try {
      $count = 0;
      $default_scheme = $this->configFactory->get('system.file')->get('default_scheme');
      $base_path = $default_scheme . '://' . $this->configFactory->get('imagecache_external.settings')->get('imagecache_directory');
      foreach ($data as $file) {
        if ($file == IMAGECACHE_EXTERNAL_FLUSH_DIRECTORIES) {

          // Imagecache external directory.
          $count++;
          $this->fileSystem->deleteRecursive($base_path);

          // Style paths.
          /** @var \Drupal\image\ImageStyleInterface[] $style */
          foreach ($this->entityTypeManager->getStorage('image_style')->loadMultiple() as $style) {
            $style_directory = $default_scheme . '://styles/' . $style->id() . '/' . $default_scheme . '/externals';
            if (file_exists($style_directory)) {
              $count++;
              $this->fileSystem->deleteRecursive($style_directory);
            }
          }
        }
        else {
          $count++;
          image_path_flush($base_path . '/' . $file);
          $this->fileSystem->deleteRecursive($base_path . '/' . $file);
        }
      }
      $this->logger->notice('@count file(s) have flushed', ['@count' => $count]);
    }
    catch (FileException $e) {
      $this->logger->error('Error flush image: @message', ['@message' => $e->getMessage()]);
    }
  }

}
