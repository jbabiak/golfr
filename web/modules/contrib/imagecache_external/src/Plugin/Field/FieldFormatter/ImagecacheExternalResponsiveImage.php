<?php

namespace Drupal\imagecache_external\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'imagecache_external_image' formatter.
 *
 * @FieldFormatter(
 *   id = "imagecache_external_responsive_image",
 *   module = "imagecache_external",
 *   label = @Translation("Imagecache External Responsive Image"),
 *   field_types = {
 *     "link",
 *     "text",
 *     "string",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class ImagecacheExternalResponsiveImage extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The responsive image style storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a ImagecacheExternalResponsiveImage object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image style storage.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ImageFactory $image_factory, EntityStorageInterface $responsive_image_style_storage, LinkGeneratorInterface $link_generator, AccountInterface $current_user, RendererInterface $renderer, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->imageFactory = $image_factory;
    $this->responsiveImageStyleStorage = $responsive_image_style_storage;
    $this->linkGenerator = $link_generator;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('image.factory'),
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('link_generator'),
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'imagecache_external_responsive_style' => '',
        'imagecache_external_link' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $elements = [];

    $responsive_image_options = [];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if (!empty($responsive_image_styles)) {
      /** @var \Drupal\responsive_image\Entity\ResponsiveImageStyle $responsive_image_style */
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $elements['imagecache_external_responsive_style'] = [
      '#title' => $this->t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $settings['imagecache_external_responsive_style'],
      '#required' => TRUE,
      '#options' => $responsive_image_options,
      '#description' => [
        '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ],
    ];

    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $elements['imagecache_external_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $settings['imagecache_external_link'],
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('imagecache_external_responsive_style'));
    if ($responsive_image_style) {
      $summary[] = $this->t('Responsive image style: @responsive_image_style', ['@responsive_image_style' => $responsive_image_style->label()]);

      $link_types = [
        'content' => $this->t('Linked to content'),
        'file' => $this->t('Linked to file'),
      ];
      // Display this setting only if image is linked.
      if (isset($link_types[$this->getSetting('imagecache_external_link')])) {
        $summary[] = $link_types[$this->getSetting('imagecache_external_link')];
      }
    }
    else {
      $summary[] = $this->t('Select a responsive image style.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field = $items->getFieldDefinition();
    $field_settings = $this->getFieldSettings();

    $url = NULL;
    $image_link_setting = $this->getSetting('imagecache_external_responsive_style');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    // Check if the field provides a title.
    if ($field->getType() == 'link') {
      if ($field_settings['title'] != DRUPAL_DISABLED) {
        $field_title = TRUE;
      }
    }

    foreach ($items as $delta => $item) {
      // Get field value.
      $values = $item->toArray();

      $image_alt = '';
      if ($field->getType() == 'link') {
        $image_path = imagecache_external_generate_path($values['uri']);
        // If present, use the Link field title to provide the alt text.
        if (isset($field_title)) {
          // The link field appends the url as title when the title is empty.
          // We don't want the url in the alt tag, so let's check this.
          if ($values['title'] != $values['uri']) {
            $image_alt = isset($field_title) ? $values['title'] : '';
          }
        }
      }
      else {
        $image_path = imagecache_external_generate_path($values['value']);
      }

      // Skip rendering this item if there is no image_path.
      if (!$image_path) {
        continue;
      }

      if (isset($link_file)) {
        $url = $this->fileUrlGenerator->generate($image_path);
      }

      $image = $this->imageFactory->get($image_path);
      $style_settings = $this->getSetting('imagecache_external_responsive_style');

      $image_build_base = [
        '#width' => $image->getWidth(),
        '#height' => $image->getHeight(),
        '#uri' => $image_path,
        '#alt' => $image_alt,
        '#title' => '',
      ];

      if (empty($style_settings)) {
        $image_build = [
          '#theme' => 'image',
        ] + $image_build_base;
      }
      else {
        $image_build = [
          '#theme' => 'imagecache_external_responsive',
          '#responsive_image_style_id' => $style_settings,
        ] + $image_build_base;
      }

      if ($url) {
        $rendered_image = $this->renderer->render($image_build);
        $elements[$delta] = Link::fromTextAndUrl($rendered_image, $url)->toRenderable();
      }
      else {
        $elements[$delta] = $image_build;
      }

    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->getSetting('imagecache_external_responsive_style');
    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $style */
    if ($style_id && $style = ResponsiveImageStyle::load($style_id)) {
      // Add the responsive image style as dependency.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return \Drupal::moduleHandler()->moduleExists('responsive_image');
  }
}