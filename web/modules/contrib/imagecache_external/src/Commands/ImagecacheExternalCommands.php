<?php

namespace Drupal\imagecache_external\Commands;

use Drush\Commands\DrushCommands;

class ImagecacheExternalCommands extends DrushCommands {

  /**
   * Generate the path and (optionally) download an image.
   *
   * @param $url
   *
   * @command imagecache-external:generate
   */
  public function generate($url) {
    $this->logger()->notice('Generating path for ' . $url);
    $uri = imagecache_external_generate_path($url);
    if ($uri) {
      $this->logger()->notice('File available at ' . \Drupal::service('file_system')->realPath($uri));
    }
    else {
      $this->logger()->notice('Failed generating path for ' . $url);
    }
  }

  /**
   * Set a default image in config.
   *
   * @param int $fid
   *
   * @command imagecache-external:set-default-image
   */
  public function setDefaultImageInConfig(int $fid = 0) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('imagecache_external.settings');
    $config->set('imagecache_fallback_image', $fid);
    $config->save();
  }

}