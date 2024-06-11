<?php

namespace Drupal\imagecache_external\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SettingsForm.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(FileSystemInterface $fileSystem, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->fileSystem = $fileSystem;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imagecache_external_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'imagecache_external.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('imagecache_external.settings');

    $form['imagecache_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Imagecache Directory'),
      '#required' => TRUE,
      '#description' => $this->t('Where, within the files directory, should the downloaded images be stored?'),
      '#default_value' => $config->get('imagecache_directory'),
      '#validate' => '::validateForm',
    ];

    $form['imagecache_subdirectories'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create subdirectories'),
      '#description' => $this->t('Store files in subdirectories for filesystem performance. Use this option if you are going to store a lot of files.'),
      '#default_value' => $config->get('imagecache_subdirectories'),
    ];

    $form['imagecache_default_extension'] = [
      '#type' => 'select',
      '#options' => [
        '' => 'none',
        '.jpg' => 'jpg',
        '.png' => 'png',
        '.gif' => 'gif',
        '.jpeg' => 'jpeg',
      ],
      '#title' => $this->t('Imagecache default extension'),
      '#required' => FALSE,
      '#description' => $this->t('If no extension is provided by the external host, specify a default extension'),
      '#default_value' => $config->get('imagecache_default_extension'),
    ];

    $form['imagecache_external_management'] = [
      '#type' => 'radios',
      '#title' => $this->t('How should Drupal handle the files?'),
      '#description' => $this->t('Managed files can be re-used elsewhere on the site, for instance in the Media Library if you use the Media module. Unmanaged files are not saved to the database, but can be cached using Image Styles.'),
      '#options' => [
        'unmanaged' => $this->t('Unmanaged: Only save the images to the files folder to be able to cache them.'),
        'managed' => $this->t('Managed: Download the images and save its metadata to the database.'),
      ],
      '#default_value' => $config->get('imagecache_external_management'),
    ];

    $form['imagecache_external_use_whitelist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use whitelist'),
      '#description' => $this->t('By default, all images are blocked except for images served from white-listed hosts. You can define hosts below.'),
      '#default_value' => $config->get('imagecache_external_use_whitelist'),
    ];

    $form['imagecache_external_hosts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Imagecache External hosts'),
      '#description' => $this->t('Add one host per line. You can use top-level domains to whitelist subdomains. Ex: staticflickr.com to whitelist farm1.staticflickr.com and farm2.staticflickr.com'),
      '#default_value' => $config->get('imagecache_external_hosts'),
      '#states' => [
        'visible' => [
          ':input[name="imagecache_external_use_whitelist"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['imagecache_fallback_image'] = [
      '#type' => 'managed_file',
      '#name' => 'imagecache_fallback_image',
      '#title' => $this->t('Fallback image'),
      '#description' => $this->t("When an external image couldn't be found, use this image as a fallback."),
      '#default_value' => $config->get('imagecache_fallback_image') ? [$config->get('imagecache_fallback_image')] : [],
      '#upload_location' => 'public://',
    ];

    $form['imagecache_external_cron_flush_frequency'] = [
      '#type' => 'number',
      '#title' => $this->t('Cron cache flush frequency'),
      '#description' => $this->t('The flush frequency, represented as the number of days, for flushing cached images during cron. Enter 0 to disable cron flushing.'),
      '#field_suffix' => $this->t('number of days'),
      '#default_value' => $config->get('imagecache_external_cron_flush_frequency', 0),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['#validate'][] = '::validateForm';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $scheme = $this->configFactory->get('system.file')->get('default_scheme');
    $directory = $scheme . '://' . $form_state->getValue('imagecache_directory');
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
      $error = $this->t('The directory %directory does not exist or is not writable.', ['%directory' => $directory]);
      $form_state->setErrorByName('imagecache_directory', $error);
      $this->logger('imagecache_external')->error($error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $fallback_image = 0;
    if (!empty($values['imagecache_fallback_image'])) {
      $fallback_image = $values['imagecache_fallback_image'][0];

      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fallback_image);
      if ($file instanceof FileInterface && !$file->isPermanent()) {
        $file->setPermanent();
        $file->save();
      }
    }

    $this->config('imagecache_external.settings')
      ->set('imagecache_directory', $values['imagecache_directory'])
      ->set('imagecache_subdirectories', $values['imagecache_subdirectories'])
      ->set('imagecache_default_extension', $values['imagecache_default_extension'])
      ->set('imagecache_external_management', $values['imagecache_external_management'])
      ->set('imagecache_external_use_whitelist', $values['imagecache_external_use_whitelist'])
      ->set('imagecache_external_hosts', $values['imagecache_external_hosts'])
      ->set('imagecache_fallback_image', $fallback_image)
      ->set('imagecache_external_cron_flush_frequency', $values['imagecache_external_cron_flush_frequency'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
