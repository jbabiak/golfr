<?php

namespace Drupal\choices_autocomplete\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'options_select_choices' widget.
 *
 * @FieldWidget(
 *   id = "options_select_choices",
 *   label = @Translation("Choices.js autocomplete"),
 *   description = @Translation("An autocomplete text field powered by Choices.js."),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectChoicesWidget extends OptionsSelectWidget {

  use ChoicesWidgetTrait {
    formElement as protected traitFormElement;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = $this->traitFormElement($items, $delta, $element, $form, $form_state);

    $this->required = $element['#required'];
    $this->multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $this->has_value = isset($items[0]->{$this->column});

    $options = $this->getOptions($items->getEntity());
    unset($element['#default_value'], $options['_none']);
    $element['#options'] = $options;
    $element += parent::formElement($items, $delta, $element, $form, $form_state);

    return $element;
  }

}
