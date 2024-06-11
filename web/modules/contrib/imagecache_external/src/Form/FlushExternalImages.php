<?php

namespace Drupal\imagecache_external\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FlushExternalImages extends ConfirmFormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new SettingsForm.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   */
  public function __construct(FileSystemInterface $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imagecache_external_flush_external_images_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Flush all external images?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('imagecache_external.admin_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $file_count = 0;
    if (file_exists(imagecache_external_get_directory_path())) {
      $files = $this->fileSystem->scanDirectory(imagecache_external_get_directory_path(), '/.*/');
      if (!empty($files)) {
        $file_count = count($files);
      }
    }

    if ($file_count > 0) {
      return $this->formatPlural($file_count,'Are you sure? This cannot be undone. <strong>1 file will be deleted</strong>.<br />Image style derivatives will also be deleted.', 'Are you sure? This cannot be undone. <strong>@count files will be deleted</strong>.<br />Image style derivatives will also be deleted.');
    }
    else {
      return $this->t('No files found to flush.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Flush');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (imagecache_external_flush_cache()) {
      $this->messenger()->addMessage($this->t('Imagecache external images have been scheduled to be deleted on next cron run(s).'));
    }
    else {
      $this->messenger()->addMessage($this->t('Could not flush external images'), MessengerInterface::TYPE_ERROR);
    }
    $form_state->setRedirect('imagecache_external.admin_settings');
  }

}
