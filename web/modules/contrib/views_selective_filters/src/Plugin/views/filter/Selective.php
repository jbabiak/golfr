<?php

namespace Drupal\views_selective_filters\Plugin\views\filter;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Views filter handler for selective values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_selective_filters_filter")
 */
class Selective extends InOperator {

  /**
   * The original filter value options, if it's an options list handler.
   *
   * @var array|false
   */
  protected $originalOptions;

  /**
   * A static cache used for storing results.
   *
   * @var array
   */
  protected static array $results = [];

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->options['exposed'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Storage for field used to display values.
    $options['selective_display_field']['default'] = '';
    // Storage for optional entity type to get labels from.
    $options['selective_entity_type']['default'] = '';
    // Storage for sort used to sort display values.
    $options['selective_display_sort']['default'] = 'ASC';
    // Storage for aggregated fields.
    $options['selective_aggregated_fields']['default'] = '';
    // Limit aggregated items to prevent a huge number of options in select.
    $options['selective_items_limit']['default'] = 100;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [];

    // If $this->view->selective_oids means that the handler is being called
    // inside the cloned view used to obtain the selective values and thus this
    // is to prevent infinite recursive loop.
    if (empty($this->view->selective_oids) && !empty($this->view->inited)) {
      $this->valueOptions = $this->getOids();
      // @todo Omit null values in result: they are improperly handled.
      // When constructing the query.
      $this->valueOptions = array_diff_key($this->valueOptions, ['' => NULL]);
      // Set a flag in the view so we know it is using selective filters.
      $this->view->using_selective = TRUE;
    }
    else {
      if (!empty($this->view->selective_oids)) {
        $this->valueOptions = [];
      }
      else {
        // This is a special case, if $this->valueOptions is not an array
        // then parent::valueForm() will throw an exception, so,
        // in our custom override no form is generated when $this->valueOptions
        // is not an array. We only want this to happen in the administrative
        // interface.
        // unset($this->valueOptions);.
      }
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $this->getValueOptions();
    // If you call parent::valueForm() and $this->valueOptions
    // is not an array, an exception is thrown.
    if (isset($this->valueOptions) && is_array($this->valueOptions)) {
      parent::valueForm($form, $form_state);
    }
    // Avoid the 'illegal values' Form API error.
    $form['value']['#validated'] = TRUE;
    // Add behaviour for ajax block refresh.
    // Don't do this if the view is being executed
    // to obtain selective values.
    // if (empty($this->view->selective_oids)) {
    //   $form['#attached']['js'][] = drupal_get_path('module', 'views_filters_selective') . '/js/attachBehaviours.js';
    // }.
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $this->getValueOptions();
    $errors = [];

    // If the operator is an operator which doesn't require a value, there is
    // no need for additional validation.
    if (in_array($this->operator, $this->operatorValues(0))) {
      return [];
    }

    if (!in_array($this->operator, $this->operatorValues(1))) {
      $errors[] = $this->t('The operator is invalid on filter: @filter.', ['@filter' => $this->adminLabel(TRUE)]);
    }
    if (is_array($this->value)) {
      // This is overridden because it causes problems during preview.
    }
    elseif (!empty($this->value) && ($this->operator == 'in' || $this->operator == 'not in')) {
      $errors[] = $this->t('The value @value is not an array for @operator on filter: @filter', ['@value' => var_export($this->value), '@operator' => $this->operator, '@filter' => $this->adminLabel(TRUE)]);
    }
    return $errors;
  }

  /**
   * Checks if two base fields are compatible.
   */
  protected function baseFieldCompatible($base_field1, $base_field2) {
    return str_starts_with($base_field2, $base_field1);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $base_field = $this->definition['field_base'];

    parent::buildOptionsForm($form, $form_state);
    // Filter should always be exposed, show warning.
    array_unshift($form['expose_button'], [
      'warning' => [
        '#theme' => 'status_messages',
        '#message_list' => ['warning' => [$this->t('This filter is always exposed to users.')]],
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ],
    ]);
    // Remove option to unexpose filter. Tried to disable, but did not work.
    $form['expose_button']['checkbox']['checkbox']['#type'] = 'hidden';
    unset($form['expose_button']['button']);
    unset($form['expose_button']['markup']);
    // Do not allow to check "all values".
    $form['value']['#access'] = FALSE;
    // Cannot group without values.
    unset($form['group_button']);

    // Add combo to pick display field for filter.
    $options = [];
    foreach ($this->view->display_handler->getHandlers('field') as $field) {
      if ($this->baseFieldCompatible($base_field, $field->field)) {
        $options[$field->options['id']] = $field->adminLabel();
      }
    }

    $form['selective_display_field'] = [
      '#title' => $this->t('Display field'),
      '#type' => 'select',
      '#description' => $this->t('Field to be used for the selective options.'),
      '#options' => $options,
      '#default_value' => $this->options['selective_display_field'],
    ];

    $form['selective_entity_type'] = [
      '#title' => $this->t('Entity type'),
      '#type' => 'select',
      '#description' => $this->t('Optionally, select an entity type to look up labels from instead of using the field style. Field keys must be entity IDs.'),
      '#options' => $this->getEntityTypeOptions(),
      '#default_value' => $this->options['selective_entity_type'],
    ];

    // Add combo to pick sort for display.
    $options = [];
    $options['NONE'] = $this->t('No sorting');
    // Add option for custom sortings.
    if ($this->getOriginalOptions()) {
      $options['ORIG'] = $this->t('As the original filter');
    }
    $options['KASC'] = $this->t('Custom key ascending (ksort)');
    $options['KDESC'] = $this->t('Custom key descending (ksort reverse)');
    $options['ASC'] = $this->t('Custom ascending (asort)');
    $options['DESC'] = $this->t('Custom descending (asort reverse)');
    // @todo Allow the use of view's sorts!
    // foreach ($this->view->display_handler->handlers['sort'] as $key => $handler) {
    //  $options[$handler->options['id']] = $handler->definition['group'] . ': ' . $handler->definition['title'];
    // }
    $form['selective_display_sort'] = [
      '#title' => $this->t('Sort field'),
      '#type' => 'select',
      '#description' => $this->t('Choose wich field to use for display'),
      '#options' => $options,
      '#default_value' => $this->options['selective_display_sort'],
    ];
    $form['selective_items_limit'] = [
      '#title' => $this->t('Limit number of select items'),
      '#type' => 'textfield',
      '#description' => $this->t("Don't allow a badly configured selective filter to return thousands of possible values. Enter a limit or remove any value for no limit. We recommend to set a limit no higher than 100."),
      '#default_value' => $this->options['selective_items_limit'],
      '#min' => 0,
    ];
  }

  /**
   * Gets all entity type options with label as the value and id as the key.
   *
   * @return array
   *   The entity types array.
   */
  protected function getEntityTypeOptions() {
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $options = ['' => $this->t('- None -')];

    /**
     * @var string $entity_type_id
     * @var \Drupal\Core\Entity\EntityTypeInterface $entity_type
     */
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $options[$entity_type_id] = $entity_type->getLabel();
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    // Remove reduce resultset interface.
    unset($form['expose']['reduce']);
    // @todo Populated somewhere through AJAX, I could not find it....
    // Provide default value for filter name.
    if (empty($form['expose']['identifier']['#default_value'])) {
      $form['expose']['identifier']['#default_value'] = $this->options['field'];
    }
    if (empty($form['expose']['label']['#default_value'])) {
      $form['expose']['label']['#default_value'] = $this->definition['title'];
    }
    if (empty($form['ui_name']['#default_value'])) {
      $form['ui_name']['#default_value'] = $this->definition['title'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // If this view was constructed to obtain the selective values for this
    // handler, the handler should not add any constraints itself.
    if (isset($this->view->selective_handler_signature) && $this->getSignature() === $this->view->selective_handler_signature) {
      return;
    }

    parent::query();
  }

  /**
   * Returns a signature for current filter handler.
   *
   * @return string
   *   The signature.
   */
  protected function getSignature() {
    return hash('sha256', serialize([
      'id' => $this->view->id(),
      'args' => $this->view->args,
      'input' => $this->view->getExposedInput(),
      'base_field' => $this->definition['field_base'],
      'real_field' => $this->realField,
      'field' => $this->field,
      'table' => $this->table,
      'ui_name' => $this->adminLabel(),
    ]));
  }

  /**
   * @return \Drupal\views\ViewExecutable|null
   */
  protected function getViewCopy() {
    // We don't want a badly configured selective filter
    // to return thousands of possible values.
    $max_items = (int) $this->options['selective_items_limit'];

    // Clone the view (so it works while editting) and get all results.
    $view_copy = Views::executableFactory()->get($this->view->storage);
    if (!$view_copy) {
      return NULL;
    }
    // Store a flag so that we can know from other places
    // that this view is being used to obtain selective data.
    $view_copy->selective_oids = TRUE;
    // Store information about what filter is this view being used for.
    $view_copy->selective_handler_signature = $this->getSignature();
    // Transfer contextual information to cloned view.
    $view_copy->setExposedInput($this->view->getExposedInput());
    $view_copy->setArguments($this->view->args);

    // Mess up with the field used for distinct have thousands of elements.
    // Limit result set to 100: anything above is not user friendly at all.
    $view_copy->setItemsPerPage($max_items);

    $view_copy->setDisplay($this->view->current_display);

    return $view_copy;
  }

  /**
   * Returns a list of options for current view, only at runtime.
   */
  protected function getOids() {
    // Parameters that we will be using during the process.
    $base_field = $this->definition['field_base'];
    $ui_name = $this->adminLabel();
    $signature = $this->getSignature();

    // Prevent same filters from being recalculated.
    if (empty(static::$results[$signature])) {
      // We don't want a badly configured selective filter
      // to return thousands of possible values.
      $max_items = (int) $this->options['selective_items_limit'];

      // Clone the view (so it works while editting) and get all results.
      $view_copy = $this->getViewCopy();
      if (!$view_copy) {
        return NULL;
      }

      $display = $view_copy->getDisplay();

      // Remove any exposed form configuration. This showed up with BEF module!
      unset($display->display['display_options']['exposed_form']);

      $fields =& $display->getHandlers('field');
      $fields = array_intersect_key($fields, [$this->options['selective_display_field'] => TRUE]);

      // Check to see if the user remembered to add the field.
      if (empty($fields)) {
        \Drupal::messenger()->addMessage(t('Selective query filter must have corresponding field added to view with Administrative Name set to "@name" and Base Type "@type"',
          [
            '@name' => $ui_name,
            '@type' => $base_field,
          ]),
          'error');
        return [];
      }

      // Get ID of field that will be used for rendering.
      /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface $field */
      $field = reset($fields);

      $field_options = $field->options;

      // Get field Id.
      $field_id = $field_options['id'];

      // Check that relationships are coherent between Field and Filter.
      $no_display_field_relationship = empty($field_options['relationship']) || $field_options['relationship'] === 'none';
      $no_filter_relationship = empty($this->options['relationship']) || $this->options['relationship'] === 'none';
      $equal
        = (($no_display_field_relationship === TRUE) && ($no_filter_relationship === TRUE)) ||
        ($field_options['relationship'] === $this->options['relationship']);

      if (!$equal) {
        \Drupal::messenger()->addMessage(t('Selective filter "@name": relationship of field and filter must match.',
          [
            '@name' => $ui_name,
            '@type' => $base_field,
          ]),
          'error');
        return [];
      }

      // If main field is excluded from presentation, bring it back.
      // Set group type for handler to populate database relationships in query.
      $field_options['exclude'] = 0;
      $field_options['group_type'] = 'group';

      // Remove all sorting: sorts must be added to aggregate fields.
      // $sorts =& $display->getHandlers('sort');
      // $sorts = [];.
      // Turn this into an aggregate query.
      $display->setOption('group_by', 1);

      // Aggregate is incompatible with distinct and pure distinct.
      // At least it does not make sense as it is implemented now.
      $query_options = $display->getOption('query');
      $query_options['options']['distinct'] = TRUE;
      $display->setOption('query', $query_options);

      // Some style plugins can affect the built query, make sure we use a
      // reliable field based style plugin.
      $display->setOption('pager', ['type' => 'none', 'options' => []]);
      $display->setOption('style', ['type' => 'default', 'options' => []]);
      $display->setOption('row', ['type' => 'fields', 'options' => []]);
      $display->setOption('cache', ['type' => 'none', 'options' => []]);

      // Run View.
      $view_copy->execute($this->view->current_display);

      // We show human-readable values when case.
      if (method_exists($field, 'getValueOptions')) {
        $field->getValueOptions();
      }

      /** @var \Drupal\views\Plugin\views\style\StylePluginBase $style */
      $style = $display->getPlugin('style');

      // Create array of objects for selector.
      $oids = [];
      $entityTypeStorage = NULL;
      if ($this->options['selective_entity_type']) {
        $entityTypeStorage = \Drupal::entityTypeManager()->getStorage($this->options['selective_entity_type']);
      }

      foreach ($view_copy->result as $row) {
        $key = $field->getValue($row);

        // Skip when there is a null key value.
        if (is_null($key)) {
          continue;
        }

        // $key = $field->getValue($row) should be more robust
        // but values are sometimes all values like on EntityField, and we need
        // scalar values for the filters.
        if (!is_scalar($key)) {
          $alias = $field->aliases[$field->realField] ?? $field->field_alias;
          $key = $row->{$alias} ?? NULL;
        }

        $value = NULL;
        if ($entityTypeStorage !== NULL) {
          $entity = $entityTypeStorage->load($key);

          if ($entity) {
            $value = $entity->label();
          }
        }
        else {
          $value = $style->getField($row->index, $field_id);
        }

        if (empty($value)) {
          $value = $this->t('Empty (@key)', ['@key' => empty($key) ? json_encode($key) : $key]);
        }
        $oids[$key] = strip_tags($value);
      }

      // Sort values.
      $sort_option = $this->options['selective_display_sort'];
      switch ($sort_option) {
        case 'ASC':
          asort($oids);
          break;

        case 'DESC':
          arsort($oids);
          break;

        case 'KASC':
          ksort($oids);
          break;

        case 'KDESC':
          krsort($oids);
          break;

        case 'ORIG':
          $oids = static::filterOriginalOptions($this->getOriginalOptions(), array_keys($oids));
          break;

        case 'NONE':
          break;

        default:
          asort($oids);
      }

      // If limit exceeded this field is not good for being "selective".
      if (!empty($max_items) && count($oids) === $max_items) {
        \Drupal::messenger()->addMessage(t('Selective filter "@field" has limited the amount of total results. Please, review you query configuration.', ['@field' => $ui_name]), 'warning');
      }

      static::$results[$signature] = $oids;
      $view_copy->destroy();
    }

    return array_filter(static::$results[$signature]);
  }

  /**
   * Filters a list of original options according to selected set.
   *
   * @param array $options
   *   The options list of the original filter.
   * @param array $set
   *   The narrowed set of results provided by the cloned view.
   *
   * @return array
   *   The original filter options list narrowed to the cloned query results.
   */
  protected static function filterOriginalOptions(array $options, array $set) {
    $filtered = [];

    foreach ($options as $key => $value) {
      // Handle grouped options.
      // @see hook_options_list().
      if (is_array($value)) {
        $nested = static::filterOriginalOptions($value, $set);
        if (!empty($nested)) {
          $filtered[$key] = $nested;
        }
        continue;
      }
      if (in_array($key, $set)) {
        $filtered[$key] = $value;
      }
    }

    return $filtered;
  }

  /**
   * Returns the original filter value options, if provides an options list.
   *
   * @return array|false
   *   The original filter option list, if available, or FALSE.
   */
  protected function getOriginalOptions() {
    if (!isset($this->originalOptions)) {
      /** @var \Drupal\views\Plugin\ViewsHandlerManager $filterManager */
      $filterManager = \Drupal::service('plugin.manager.views.filter');
      $this->originalOptions = FALSE;

      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $original_filter */
      $original_filter = $filterManager->createInstance($this->definition['proxy'], $this->configuration);
      $original_filter->definition = $this->getPluginDefinition();
      $original_filter->value = $this->value;
      $original_filter->view = $this->view;

      if (is_callable([$original_filter, 'getValueOptions'])) {
        $options = $original_filter->getValueOptions();

        // We store only non-empty array.
        $this->originalOptions = (is_array($options) && !empty($options)) ? $options : [];
      }

      if (empty($this->originalOptions)) {
        $form = [];
        $original_filter->buildExposedForm($form, new FormState());

        if (isset($form['value']['#options'])) {
          $this->originalOptions = $form['value']['#options'];
        }
      }
    }

    return $this->originalOptions;
  }

}
