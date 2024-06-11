<?php

namespace Drupal\choices_autocomplete\Plugin\Field\FieldWidget;

use Drupal\choices_autocomplete\ChoicesAutocompleteDefaults;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Choices.js autocomplete widget trait.
 */
trait ChoicesWidgetTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return ['options' => ChoicesAutocompleteDefaults::getOptions()] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    $setting = parent::getSetting($key);
    if (is_array($setting)) {
      $defaultOptions = static::defaultSettings()['options'];
      return NestedArray::mergeDeep($defaultOptions, $setting);
    }
    return $setting;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $options = $this->getSetting('options');
    if ($value = $options['plugin']['searchPlaceholderValue']) {
      $summary[] = $this->t('Start typing text: @value', [
        '@value' => $value,
      ]);
    }
    if ($value = $options['plugin']['loadingText']) {
      $summary[] = $this->t('Searching text: @value', [
        '@value' => $value,
      ]);
    }
    if ($value = $options['plugin']['noResultsText']) {
      $summary[] = $this->t('No results text: @value', [
        '@value' => $value,
      ]);
    }
    if ($value = $options['plugin']['itemSelectText']) {
      $summary[] = $this->t('Press to select text: @value', [
        '@value' => $value,
      ]);
    }
    if ($value = $options['plugin']['maxItemText']) {
      $summary[] = $this->t('Max items text: @value', [
        '@value' => $value,
      ]);
    }
    if ($value = $options['instance']['remove_item_text']) {
      $summary[] = $this->t('Remove item text: @value', [
        '@value' => $value,
      ]);
    }
    $summary[] = $this->t('Dropdown position: @value', [
      '@value' => $this->getDropdownOptions()[$options['plugin']['position']],
    ]);
    if ($value = $options['instance']['none_text']) {
      $summary[] = $this->t('No selection text: @value', [
        '@value' => $value,
      ]);
    }

    return $summary;
  }

  /**
   * Get dropdown options.
   *
   * @return array
   *   Returns an array of options.
   */
  protected function getDropdownOptions(): array {
    return [
      'auto' => $this->t('Automatic (recommended)'),
      'top' => $this->t('Top'),
      'bottom' => $this->t('Bottom'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['placeholder']['#access'] = $form['size']['#access'] = FALSE;
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $options = $this->getSetting('options');
    $form['options'] = ['#tree' => TRUE];
    $form['options']['plugin']['searchPlaceholderValue'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start typing text'),
      '#description' => $this->t('Text shown as search input placeholder.'),
      '#default_value' => $options['plugin']['searchPlaceholderValue'],
    ];
    $form['options']['plugin']['loadingText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Searching text'),
      '#description' => $this->t('Text shown in dropdown as search results are being retrieved.'),
      '#default_value' => $options['plugin']['loadingText'],
    ];
    $form['options']['plugin']['noResultsText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#description' => $this->t('Text shown in dropdown when no results were found.'),
      '#default_value' => $options['plugin']['noResultsText'],
    ];
    $form['options']['plugin']['itemSelectText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Press to select text'),
      '#description' => $this->t('Text shown in dropdown next to the current highlighted result.'),
      '#default_value' => $options['plugin']['itemSelectText'],
    ];
    $form['options']['plugin']['maxItemText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max items text'),
      '#description' => $this->t('Text shown in dropdown when the maximum number of items are selected.'),
      '#default_value' => $options['plugin']['maxItemText'],
    ];
    $form['options']['instance']['remove_item_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remove item text'),
      '#description' => $this->t('Text shown in button to remove a selected item.'),
      '#default_value' => $options['instance']['remove_item_text'],
    ];
    $form['options']['plugin']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Dropdown position'),
      '#description' => $this->t('Change the search results dropdown position.'),
      '#options' => $this->getDropdownOptions(),
      '#default_value' => $options['plugin']['position'],
      '#required' => TRUE,
    ];
    $form['options']['instance']['none_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No selection text'),
      '#description' => $this->t('Text shown when no selection has been made.'),
      '#default_value' => $options['instance']['none_text'],
    ];
    if ($cardinality !== 1) {
      $form['options']['instance']['none_text']['#description'] = $this->t('Use "Start typing text" on multi-value fields.');
      $form['options']['instance']['none_text']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

    $element += [
      '#type' => 'choices_autocomplete',
      '#multiple' => $cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $cardinality > 1,
      '#cardinality' => $cardinality,
      '#choices_autocomplete_options' => $this->getSetting('options'),
      '#default_value' => [],
    ];

    return $element;
  }

}
