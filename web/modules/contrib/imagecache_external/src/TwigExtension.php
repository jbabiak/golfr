<?php

namespace Drupal\imagecache_external;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $file_url_generator;

  /**
   * Constructs a \Drupal\imagecache_external\TwigExtension.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->file_url_generator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('imagecache_external', [$this, 'imageCacheExternal']),
    ];
  }

  /**
   * Returns the URL of this image derivative for an original image path or URI.
   *
   * Example:
   *
   * @code
   *  {{ 'https://my.web.site/my-image.jpg'|imagecache_external('thumbnail') }}
   * @endcode
   *
   * @param string $path
   *   The path or URI to the original image.
   * @param string $style
   *   The image style.
   *
   * @return string|null
   *   The relative URL where a style image can be downloaded, suitable for use
   *   in an <img> tag. Requesting the URL will cause the image to be created.
   */
  public function imageCacheExternal($path, $style) {
    $local_path = imagecache_external_generate_path($path);

    if (!$image_style = $this->entityTypeManager->getStorage('image_style')->load($style)) {
      trigger_error(sprintf('Could not load image style %s.', $style));
      return;
    }

    if (!$image_style->supportsUri($local_path)) {
      trigger_error(sprintf('Could not apply image style %s.', $style));
      return;
    }

    return $this->file_url_generator->transformRelative($image_style->buildUrl($local_path));
  }

}