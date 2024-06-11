<?php

/**
 * @file
 * Documentation of Imagecache External hooks.
 */

/**
 * Add custom image refresh logic.
 *
 * Use this hook to add extra validation(s) whether to refresh images.
 *
 * @param $needs_refresh
 *   Whether to refresh or not
 * @param $filepath
 *   The path is being checked.
 */
function hook_imagecache_external_needs_refresh_alter(&$needs_refresh, $filepath) {
  // Example: refresh images at least once a week.
  if (filemtime($filepath) > \Drupal::time()->getRequestTime() - 60 * 60 * 24 * 7) {
    $needs_refresh = TRUE;
  }
}

/**
 * Add ability to alter the destination of the file to download. Use this hook
 * to change the scheme and/or directory
 *
 * @param $alter
 *   An array which contains two keys: scheme and directory
 * @param $context
 *   An array which contains two keys: url and hash
 */
function hook_imagecache_external_destination_alter(&$alter, $context) {
  // Example: make scheme for imagecache_external s3.
  if ($context['url'] === 'foo.com') {
    $alter['scheme'] = 's3';
  }

  if ($context['url'] == 'example.com') {
    $alter['directory'] = 'my-custom-directory';
  }
}
