<?php

/**
 * @file
 * Hooks provided by the Choices Autocomplete module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter Choices.js autocomplete element.
 *
 * @param array $element
 *   The form element.
 * @param array $settings
 *   The attachment settings.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 */
function hook_choices_autocomplete_element_alter(array &$element, array &$settings, FormStateInterface $form_state): void {
  $settings['widget']['loadingText'] = t('One moment…');
}

/**
 * @} End of "addtogroup hooks".
 */
