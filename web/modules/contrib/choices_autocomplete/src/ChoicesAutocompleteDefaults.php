<?php

namespace Drupal\choices_autocomplete;

/**
 * Provides Choices.js autocomplete defaults.
 */
final class ChoicesAutocompleteDefaults {

  /***
   * Get the default options.
   *
   * @return array
   *   Returns an array of default options.
   */
  public static function getOptions(): array {
    return [
      'instance' => [
        'remove_item_text' => 'Remove item',
        'none_text' => '',
      ],
      'plugin' => [
        'searchPlaceholderValue' => 'Start typing to find results',
        'loadingText' => 'Loading...',
        'noResultsText' => 'No results found',
        'itemSelectText' => 'Press to select',
        'maxItemText' => 'Max number of items selected',
        'position' => 'auto',
      ],
    ];
  }

}
