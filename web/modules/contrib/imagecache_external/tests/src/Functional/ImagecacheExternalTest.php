<?php

namespace Drupal\Tests\imagecache_external\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Imagecache External.
 *
 * @group imagecache_external
 */
class ImagecacheExternalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'imagecache_external',
    'imagecache_external_test',
    'text',
    'node',
    'image'
  ];

  /**
   * The default theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with elevated permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  /**
   * Default text field name to store urls.
   *
   * @var string
   */
  protected $default_text_field_name = 'field_url';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create admin user.
    $this->admin_user = $this->drupalCreateUser([
      'access content',
      'administer imagecache external'
    ]);

    // Set whitelist.
    \Drupal::configFactory()->getEditable('imagecache_external.settings')
      ->set('imagecache_external_use_whitelist', TRUE)
      ->set('imagecache_external_hosts', \Drupal::request()->getHost())
      ->save();
  }

  /**
   * Test caching an external image on node.
   */
  public function testCachingExternalImageOnNode() {

    // Create page content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Page',
    ]);

    // Setup text field and entity displays.
    $this->setupTextfieldAndEntityDisplays($this->default_text_field_name);

    // Create a new node with an image attached.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Hello page',
      'uid' => $this->admin_user->id(),
      'status' => 1,
    ]);
    $node->set($this->default_text_field_name, $this->getExternalImageUrl());
    $node->save();

    // Test that image is displayed using newly created style.
    $this->drupalGet('node/' . $node->id());

    // Assert the file exists on the file system.
    self::assertFileExists(imagecache_external_generate_path($this->getExternalImageUrl()));

    // Find the raw url.
    $image_url = $this->getImagecacheExternalImageStyleUrl($this->getExternalImageUrl());
    $this->assertSession()->responseContains($image_url);

    // Test theming function as well.
    $build = [
      '#theme' => 'imagecache_external',
      '#style_name' => 'large',
      '#uri' => $this->getExternalImageUrl(),
      '#width' => 175,
      '#height' => 200,
    ];
    $tag = html_entity_decode(\Drupal::service('renderer')->renderPlain($build));
    $this->assertSession()->assertNoEscaped($tag);
  }

  /**
   * Test caching an external image using the managed file system.
   */
  function testCachingExternalImageUsingManagedFileSystem() {
    \Drupal::configFactory()->getEditable('imagecache_external.settings')
      ->set('imagecache_external_management', 'managed')
      ->save();

    if ($path = imagecache_external_generate_path($this->getExternalImageUrl())) {
      $fid = \Drupal::database()->select('file_managed', 'f')
        ->fields('f', ['fid'])
        ->condition('uri', $path)
        ->execute()
        ->fetchField();

      self::assertNotEmpty($fid);
    }
  }

  /**
   * Test caching an external image with style using the public:// scheme.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testCachingExternalImageWithImageStylePublic() {
    $this->testCachingExternalImageWithImageStyle('public');
  }

  /**
   * Test caching an external image with style using the private:// scheme.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testCachingExternalImageWithImageStylePrivate() {
    $this->testCachingExternalImageWithImageStyle('private');
  }

  /**
   * Test subdirectories.
   */
  public function testSubdirectories() {
    \Drupal::configFactory()->getEditable('imagecache_external.settings')
      ->set('imagecache_subdirectories', TRUE)
      ->save();

    $url = $this->getExternalImageUrl();
    $hash = md5($url);
    $subdirectory = $hash[0] . '/' . $hash[1] . '/';
    $file_1 = imagecache_external_generate_path($url);
    self::assertFileDoesNotExist('public://externals/' . $hash . '.png');
    self::assertFileExists('public://externals/' . $subdirectory . $hash . '.png');

    $file_1_derivative = $this->generateDerivative($file_1);
    self::assertFileExists($file_1_derivative);
    self::assertDirectoryExists('public://styles/large/public/externals/' . $subdirectory);

    // Test flushing with subdirectories.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/media/imagecache_external/flush');
    $this->assertSession()->pageTextContains(' Are you sure? This cannot be undone. 1 file will be deleted.');
    $this->submitForm([], 'Flush');
    $this->runCron();

    self::assertFileDoesNotExist('public://externals/' . $subdirectory . $hash . '.png');
    self::assertDirectoryDoesNotExist('public://externals');
    self::assertFileDoesNotExist($file_1_derivative);
    self::assertDirectoryDoesNotExist('public://styles/large/public/externals/' . $subdirectory);
    self::assertDirectoryDoesNotExist('public://styles/large/public/externals/');
  }

  /**
   * Tests flushing images via admin UI and cron.
   */
  public function testFlushingImages() {

    // Test via UI.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/media/imagecache_external/flush');
    $this->assertSession()->pageTextContains('No files found to flush.');
    $file_1 = imagecache_external_generate_path($this->getExternalImageUrl());
    self::assertFileExists($file_1);
    $file_1_derivative = $this->generateDerivative($file_1);
    self::assertFileExists($file_1_derivative);
    $this->drupalGet('admin/config/media/imagecache_external/flush');
    $this->assertSession()->pageTextContains('Are you sure? This cannot be undone. 1 file will be deleted.');
    $file_2 = imagecache_external_generate_path($this->getExternalImageUrl('el-blue.png'));
    self::assertFileExists($file_2);
    $file_2_derivative = $this->generateDerivative($file_2);
    self::assertFileExists($file_2_derivative);
    $this->drupalGet('admin/config/media/imagecache_external/flush');
    $this->assertSession()->pageTextContains(' Are you sure? This cannot be undone. 2 files will be deleted.');
    $this->submitForm([], 'Flush');
    $this->assertSession()->pageTextContains('Imagecache external images have been scheduled to be deleted on next cron run(s).');
    $this->runCron();
    $this->drupalGet('admin/config/media/imagecache_external/flush');
    $this->assertSession()->pageTextContains('No files found to flush.');
    self::assertFileDoesNotExist($file_1);
    self::assertFileDoesNotExist($file_2);
    self::assertFileDoesNotExist($file_1_derivative);
    self::assertFileDoesNotExist($file_2_derivative);
    $this->drupalGet('admin/config/media/imagecache_external');
    $this->submitForm(['imagecache_external_cron_flush_frequency' => 1], 'Save');

    // Test via cron.
    $file_1 = imagecache_external_generate_path($this->getExternalImageUrl());
    $file_2 = imagecache_external_generate_path($this->getExternalImageUrl('el-blue.png'));
    self::assertFileExists($file_1);
    self::assertFileExists($file_2);
    // Note, only needs one cron run as the queue workers come later than
    // imagecache_external_cron().
    $this->runCron();
    self::assertFileDoesNotExist($file_1);
    self::assertFileDoesNotExist($file_2);
  }

  /**
   * Tests directory altering when downloading a URL.
   */
  public function testDirectoryAlter() {
    $url = $this->getExternalImageUrl('drupal-wordmark.png');
    $hash = md5($url);
    imagecache_external_generate_path($url);
    self::assertFileDoesNotExist('public://externals/' . $hash . '.png');
    self::assertFileExists('public://altered-directory/' . $hash . '.png');
  }

  /**
   * Test files with no extension.
   */
  public function testNoExtension() {
    $url = $this->getExternalImageUrl('stacked') . '?id=1234';
    $hash = md5($url);
    imagecache_external_generate_path($url);
    $filename = 'public://externals/' . $hash . '.jpg';
    self::assertFileExists($filename);
  }

  /**
   * Test caching an external image using a scheme and image style.
   *
   * @param $scheme
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function testCachingExternalImageWithImageStyle($scheme) {
    if ($scheme == 'private') {
      \Drupal::configFactory()->getEditable('system.file')->set('default_scheme', $scheme)->save();
    }

    $local_image_url = $this->getImagecacheExternalImageStyleUrl($this->getExternalImageUrl(), TRUE);
    if ($scheme == 'private') {
      self::assertTrue(strpos($local_image_url, 'system/files') !== FALSE);
    }

    // Check if we can access the generated image.
    $this->drupalGet($local_image_url);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Get an external image url.
   *
   * @param string $file
   *   The file to download.
   *
   * @return string
   */
  protected function getExternalImageUrl(string $file = 'druplicon-small.png') {
    return \Drupal::service('file_url_generator')->generateAbsoluteString(\Drupal::service('extension.list.module')->getPath('imagecache_external') . '/tests/assets/' . $file);
  }

  /**
   * Returns the image style url for an external image url.
   *
   * @param $url
   * @param bool $absolute
   * @param string $style_name
   *
   * @return string
   */
  protected function getImagecacheExternalImageStyleUrl($url, bool $absolute = FALSE, string $style_name = 'large') {
    $local_image_uri = imagecache_external_generate_path($url);
    $image_style = ImageStyle::load($style_name);

    if ($absolute) {
      return $image_style->buildUrl($local_image_uri);
    }
    else {
      $file_url_generator = \Drupal::service('file_url_generator');
      $image_style_uri = $image_style->buildUri($local_image_uri);
      return $file_url_generator->generateString($image_style_uri);
    }
  }

  /**
   * Generate derivative
   *
   * @param $uri
   * @param $style_name
   *
   * @return string
   */
  protected function generateDerivative($uri, $style_name = 'large') {
    $image_style = ImageStyle::load($style_name);
    $image_style_uri = $image_style->buildUri($uri);
    $image_style->createDerivative($uri, $image_style_uri);
    return $image_style_uri;
  }

  /**
   * Setup text field and entity displays.
   *
   * @param string $field_name
   *   The name of the new field (all lowercase).
   * @param string $field_label
   *   The label of the new field.
   * @param string $bundle
   *   The node type that this field will be added to.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setupTextfieldAndEntityDisplays(string $field_name, string $field_label = 'URL', string $bundle = 'page') {

    $field_storage =FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'text',
      'settings' => [
        'max_length' => 255,
      ],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $field_label,
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Assign widget settings for the default form mode.
    $display_repository->getFormDisplay('node', $bundle)
      ->setComponent($field_name, [
        'type' => 'text_textfield',
      ])
      ->save();

    $display_repository->getViewDisplay('node', $bundle)
      ->setComponent($field_name, [
        'label' => 'hidden',
        'type' => 'imagecache_external_image',
        'settings' => [
          'imagecache_external_style' => 'large',
        ],
      ])
      ->save();
  }

  /**
   * Run cron.
   */
  protected function runCron() {
    $this->container->get('cron')->run();
  }
}
