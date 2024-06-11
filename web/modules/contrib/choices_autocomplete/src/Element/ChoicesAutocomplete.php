<?php

namespace Drupal\choices_autocomplete\Element;

use Drupal\choices_autocomplete\ChoicesAutocompleteDefaults;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 * Provides Choices.js autocomplete element.
 *
 * @FormElement("choices_autocomplete")
 */
class ChoicesAutocomplete extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    $info['#process_select'] = $info['#process'];
    $info['#process'] = [[static::class, 'processChoicesAutocomplete']];
    $info['#choices_autocomplete_options'] = [];
    $info['#default_value'] = [];
    return $info;
  }

  /**
   * Process Choices.js autocomplete element.
   */
  public static function processChoicesAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $element['#attached']['library'][] = 'choices_autocomplete/choices';
    $element['#attributes']['class'][] = 'choices-autocomplete';

    switch (\Drupal::theme()->getActiveTheme()->getName()) {
      case 'claro':
        $element['#attached']['library'][] = 'choices_autocomplete/choices.claro';
        break;

      case 'olivero':
        $element['#attached']['library'][] = 'choices_autocomplete/choices.olivero';
        break;
    }

    // Prepare the attachment settings.
    if (!$settings = $element['#choices_autocomplete_options']) {
      $settings = ChoicesAutocompleteDefaults::getOptions();
    }
    $element['#attached']['drupalSettings']['choices_autocomplete'][$element['#id']] = &$settings;

    // Invoke hook_choices_autocomplete_element_alter(). Allows modules and
    // themes to easily alter the element and attachment settings.
    \Drupal::moduleHandler()->alter('choices_autocomplete_element', $element, $settings, $form_state);
    \Drupal::theme()->alter('choices_autocomplete_element', $element, $settings, $form_state);

    // Prepare the placeholder or empty value.
    $cardinality = $element['#cardinality'] ?: NULL;
    if (isset($element['#cardinality']) && $cardinality !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $settings['plugin']['maxItemCount'] = max($cardinality, 1);
    }
    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $cardinality > 1 || $element['#multiple']) {
      $placeholder = $settings['plugin']['searchPlaceholderValue'];
      unset($settings['plugin']['searchPlaceholderValue']);
    }
    elseif (mb_strlen(trim($settings['instance']['none_text']))) {
      $placeholder = $settings['instance']['none_text'];
    }
    else {
      $placeholder = $element['#required'] ? t('- Select -') : t('- None -');
    }
    $element['#options'] = ['' => $placeholder] + $element['#options'];

    // Run the default select process callbacks.
    foreach ($element['#process_select'] as $callback) {
      $element = call_user_func_array($form_state->prepareCallback($callback), [
        &$element,
        &$form_state,
        &$complete_form,
      ]);
    }
    return $element;
  }

}
