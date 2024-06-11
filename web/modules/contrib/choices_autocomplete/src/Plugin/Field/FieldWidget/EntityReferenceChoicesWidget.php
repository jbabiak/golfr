<?php

namespace Drupal\choices_autocomplete\Plugin\Field\FieldWidget;

use Drupal\choices_autocomplete\Element\ChoicesAutocomplete;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'entity_reference_choices' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_choices",
 *   label = @Translation("Choices.js autocomplete"),
 *   description = @Translation("An autocomplete text field powered by Choices.js."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceChoicesWidget extends EntityReferenceAutocompleteWidget {

  use ChoicesWidgetTrait {
    defaultSettings as protected traitDefaultSettings;
    settingsForm as protected traitSettingsForm;
    settingsSummary as protected traitSettingsSummary;
    formElement as protected traitFormElement;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = static::traitDefaultSettings();
    $settings['options']['instance'] += [
      'input_type' => '',
      'allowed_characters' => '',
      'minlength' => 0,
      'maxlength' => 64,
      'auto_create' => FALSE,
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $operators = $this->getMatchOperatorOptions();
    $summary[] = $this->t('Autocomplete matching: @match_operator', ['@match_operator' => $operators[$this->getSetting('match_operator')]]);
    $size = $this->getSetting('match_limit') ?: $this->t('unlimited');
    $summary[] = $this->t('Autocomplete suggestion list size: @size', ['@size' => $size]);
    $summary = array_merge($summary, static::traitSettingsSummary());

    $options = $this->getSetting('options');
    if ($value = $options['instance']['input_type']) {
      $summary[] = $this->t('Limit characters: @value', [
        '@value' => $this->getCharacterOptions()[$value],
      ]);
    }
    if ($value = $options['instance']['allowed_characters']) {
      $summary[] = $this->t('Allowed characters: @value', [
        '@value' => $value,
      ]);
    }
    if ($value = $options['instance']['minlength']) {
      $summary[] = $this->t('Minimum length: @value', [
        '@value' => $value,
      ]);
    }
    if ($value = $options['instance']['maxlength']) {
      $summary[] = $this->t('Maximum length: @value', [
        '@value' => $value,
      ]);
    }

    return $summary;
  }

  /**
   * Get character limiter options.
   *
   * @return array
   *   Returns an array of options.
   */
  protected function getCharacterOptions(): array {
    return [
      'alpha' => $this->t('Letters'),
      'alphanumeric' => $this->t('Letters & Numbers'),
      'numeric' => $this->t('Numbers'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = $this->traitSettingsForm($form, $form_state);
    $name = $this->fieldDefinition->getName();
    $handler = $this->fieldDefinition->getSetting('handler_settings');

    $options = $this->getSetting('options');
    if (isset($handler['auto_create']) && $handler['auto_create']) {
      $form['options']['instance']['input_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Limit characters'),
        '#description' => $this->t('Allowed input search characters.'),
        '#options' => ['' => $this->t('- Any -')] + $this->getCharacterOptions(),
        '#default_value' => $options['instance']['input_type'],
      ];
      $form['options']['instance']['allowed_characters'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Allowed characters'),
        '#description' => $this->t('Additional input search characters to allow.'),
        '#default_value' => $options['instance']['allowed_characters'],
        '#states' => [
          'visible' => [
            "select[name='fields[$name][settings_edit_form][settings][options][instance][input_type]']" => ['!value' => ''],
          ],
        ],
      ];
    }
    $form['options']['instance']['minlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum length'),
      '#description' => $this->t('Start searching when input is equal to or exceeds a minimum number of characters.'),
      '#min' => 0,
      '#default_value' => $options['instance']['minlength'],
    ];
    $form['options']['instance']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum length'),
      '#description' => $this->t('Limit search input to a maximum number of characters.'),
      '#min' => 0,
      '#default_value' => $options['instance']['maxlength'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    $element = $this->traitFormElement($items, $delta, $element, $form, $form_state);
    $options = &$element['#choices_autocomplete_options'];

    $element['#process'] = [
      [EntityAutocomplete::class, 'processEntityAutocomplete'],
      [FormElement::class, 'processAutocomplete'],
      [static::class, 'processEntityAutocomplete'],
      [ChoicesAutocomplete::class, 'processChoicesAutocomplete'],
    ];
    $element['#element_validate'] = [
      [static::class, 'validateEntityAutocomplete'],
    ];

    foreach ($items->referencedEntities() as $entity) {
      $key = $entity->isNew() ? $entity->label() : "{$entity->label()} ({$entity->id()})";
      $element['#default_value'][$key] = $key;
    }

    // Auto create new entities.
    $element += parent::formElement($items, $delta, $element, $form, $form_state)['target_id'] + [
      '#autocreate' => FALSE,
    ];
    if ($element['#autocreate']) {
      $options['instance']['auto_create'] = TRUE;
    }

    return $element;
  }

  /**
   * Process entity autocomplete element.
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $options = &$element['#choices_autocomplete_options'];
    unset($element['#maxlength'], $element['#size']);

    // Extracts and attempts to format labels for the values. Labels are passed
    // in drupalSettings because option elements cannot include HTML. Values
    // with formatted labels are replaced when Choices.js initializes.
    if ($values = $element['#value']) {
      if (!is_array($values)) {
        $values = [$values];
      }
      $labels = [];
      foreach ($values as $value) {
        if ($entity = static::extractEntityFromInput($value, $element)) {
          $element['#options'][$value] = $value;

          if (!$entity->isNew()) {
            $labels[(string) $entity->id()] = $entity->entity_autocomplete_label;
          }
        }
      }
      $options['values'] = $labels;
    }

    return $element;
  }

  /**
   * Validate entity autocomplete element.
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $options = $element['#choices_autocomplete_options']['instance'];

    $values = array_filter(is_array($element['#value']) ? $element['#value'] : [$element['#value']]);
    $newValues = [];
    foreach ($values as $value) {
      if ($entity = static::extractEntityFromInput($value, $element)) {
        if ($entity->isNew()) {
          $value = preg_replace('/\s\(\d+\)$/', '', $value);

          if ($chars = static::validateAutoCreate($value, $options['input_type'], $options['allowed_characters'])) {
            $form_state->setError($element, t('Invalid characters: %value', [
              '%value' => implode('', $chars),
            ]));
          }
          elseif ($options['minlength'] && mb_strlen($value) < $options['minlength']) {
            $form_state->setError($element, t('Too few characters: %value', [
              '%value' => $value,
            ]));
          }
          elseif ($options['maxlength'] && mb_strlen($value) > $options['maxlength']) {
            $form_state->setError($element, t('Too many characters: %value', [
              '%value' => $value,
            ]));
          }
          else {
            $newValues[] = ['entity' => $entity];
          }
        }
        else {
          $newValues[] = ['target_id' => $entity->id()];
        }
      }
      else {
        $form_state->setError($element, t('Invalid selections.'));
      }
    }
    $form_state->setValueForElement($element, $newValues);
  }

  /**
   * Validate auto create entity characters.
   *
   * @param string $value
   *   The value.
   * @param string|null $type
   *   The input type.
   * @param string|null $allowed
   *   The allowed characters.
   *
   * @return array
   *   Returns an array of invalid characters.
   */
  public static function validateAutoCreate(string $value, ?string $type, ?string $allowed): array {
    if (!$type) {
      return [];
    }
    $chars = [];
    $allowed = mb_str_split($allowed);
    foreach (mb_str_split($value) as $char) {
      if (!$passed = in_array($char, $allowed, TRUE)) {
        switch ($type) {
          case 'alpha':
            $passed = preg_match('/[^\W_]/u', $char) && !preg_match('/\d/', $char);
            break;

          case 'numeric':
            $passed = preg_match('/\d/', $char);
            break;

          default:
            $passed = preg_match('/[^\W_]/u', $char);
            break;
        }
      }
      if (!$passed) {
        $chars[$char] = $char;
      }
    }
    return $chars;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues($values, array $form, FormStateInterface $form_state): array {
    return $values;
  }

  /**
   * Extract entity from user input.
   *
   * @param string $input
   *   The user input.
   * @param array $element
   *   The element.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns the fully loaded extracted entity.
   */
  public static function extractEntityFromInput(string $input, array $element): ?EntityInterface {
    $storage = \Drupal::entityTypeManager()->getStorage($element['#target_type']);
    $handler = static::getSelectionHandler($element);

    if ($id = (int) EntityAutocomplete::extractEntityIdFromAutocompleteInput($input)) {
      if ($entity = $storage->load($id)) {
        $matches = [];

        // Views selection handlers are extra fancy in that they can
        // match against any property or field. To extract and format a label
        // the underlying views display must be executed. Passing the
        // extracted ID ensures the view only includes that entity.
        if ($handler instanceof ViewsSelection) {
          $config = $handler->getConfiguration();

          $view = View::load($config['view']['view_name']);
          $view = $view->getExecutable();
          $view->setDisplay($config['view']['display_name']);
          $view->setArguments($config['view']['arguments']);
          $view->display_handler->setOption('entity_reference_options', [
            'limit' => 0,
            'ids' => [$id],
          ]);
          $view->execute();
          $rendered = $view->render();

          if (isset($rendered[$entity->id()])) {
            $matches[$entity->bundle()][$entity->id()] = \Drupal::service('renderer')->renderRoot($rendered[$entity->id()]);
          }
        }
        // Use default behavior for all other selection handlers.
        else {
          $matches = $handler->getReferenceableEntities($entity->label(), '=', 100);
        }

        if (isset($matches[$entity->bundle()][$entity->id()])) {
          $entity = clone $entity;
          $entity->entity_autocomplete_label = $matches[$entity->bundle()][$entity->id()];
          return $entity;
        }
      }
    }

    // Auto-create new entity with the user input.
    elseif ($element['#autocreate']) {
      /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface $handler */
      return $handler->createNewEntity($element['#target_type'], $element['#autocreate']['bundle'], $input, $element['#autocreate']['uid']);
    }

    return NULL;
  }

  /**
   * Get entity autocomplete selection handler.
   *
   * @param array $element
   *   The element.
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   *   Returns the selection handler plugin instance.
   */
  public static function getSelectionHandler(array $element): SelectionInterface {
    return \Drupal::service('plugin.manager.entity_reference_selection')
      ->getInstance($element['#selection_settings'] + [
        'target_type' => $element['#target_type'],
        'handler' => $element['#selection_handler'],
      ]);
  }

}
