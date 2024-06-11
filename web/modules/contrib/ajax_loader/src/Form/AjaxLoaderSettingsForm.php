<?php

namespace Drupal\ajax_loader\Form;

use Drupal\ajax_loader\ThrobberManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AjaxLoaderSettingsForm.
 *
 * @package Drupal\ajax_throbber\Form
 */
class AjaxLoaderSettingsForm extends ConfigFormBase {

  protected $throbberManager;

  /**
   * Function to construct.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThrobberManagerInterface $throbber_manager) {
    parent::__construct($config_factory);

    $this->throbberManager = $throbber_manager;
  }

  /**
   * Function to create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container value.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ajax_loader.throbber_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_throbber_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ajax_loader.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('ajax_loader.settings');

    $form['wrapper'] = [
      '#prefix' => '<div id="throbber-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['wrapper']['throbber'] = [
      '#type' => 'select',
      '#title' => $this->t('Throbber'),
      '#description' => $this->t('Choose your throbber'),
      '#required' => TRUE,
      '#options' => $this->throbberManager->getThrobberOptionList(),
      '#default_value' => $settings->get('throbber'),
      '#ajax' => [
        'wrapper' => 'throbber-wrapper',
        'callback' => [$this, 'ajaxThrobberChange'],
      ],
    ];

    if (!empty($form_state->getValue('throbber')) || !empty($settings->get('throbber'))) {
      $plugin_id = !empty($form_state->getValue('throbber')) ? $form_state->getValue('throbber') : $settings->get('throbber');
      if ($this->throbberManager->getDefinition($plugin_id, FALSE)) {
        // Show preview of throbber.
        if (!empty($form_state->getValue('throbber'))) {
          $throbber = $this->throbberManager->loadThrobberInstance($form_state->getValue('throbber'));
        }
        else {
          $throbber = $this->throbberManager->loadThrobberInstance($settings->get('throbber'));
        }

        $form['wrapper']['throbber']['#attached']['library'] = [
          'ajax_loader/ajax_loader.admin',
        ];

        $form['wrapper']['throbber']['#suffix'] = '<span class="throbber-example">' . $throbber->getMarkup() . '</span>';
      }
    }

    $form['hide_ajax_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Never show ajax loading message'),
      '#description' => $this->t('Choose whether you want to hide the loading ajax message even when it is set.'),
      '#default_value' => $settings->get('hide_ajax_message') ?: 0,
    ];

    $form['always_fullscreen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show loader as overlay (fullscreen)'),
      '#description' => $this->t('Choose whether you want to show the loader as an overlay, no matter what the settings of the loader are.'),
      '#default_value' => $settings->get('always_fullscreen') ?: 0,
    ];

    $form['show_admin_paths'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use ajax loader on admin pages'),
      '#description' => $this->t('Choose whether you also want to show the loader on admin pages or still like to use the default core loader.'),
      '#default_value' => $settings->get('show_admin_paths') ?: 0,
    ];

    $form['throbber_position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Throbber position'),
      '#required' => TRUE,
      '#description' => $this->t('Allows you to change the position where the throbber is inserted. A valid css selector must be used here. The default value is: body'),
      '#default_value' => $settings->get('throbber_position') ?: 'body',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback when throbber is changed.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   New throbber wrapper array.
   */
  public function ajaxThrobberChange(array $form, FormStateInterface $form_state) {
    return $form['wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ajax_loader.settings')
      ->set('throbber', $form_state->getValue('throbber'))
      ->set('hide_ajax_message', $form_state->getValue('hide_ajax_message'))
      ->set('always_fullscreen', $form_state->getValue('always_fullscreen'))
      ->set('show_admin_paths', $form_state->getValue('show_admin_paths'))
      ->set('throbber_position', $form_state->getValue('throbber_position'))
      ->save();

    // Clear cache, so that library is picked up.
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
