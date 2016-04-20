<?php

namespace Drupal\file_mdm\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that File Metadata Manager works properly.
 *
 * @group File Metadata Manager
 */
class FileMetadataManagerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'simpletest', 'file_mdm', 'file_test'];

  /**
   * Test EXIF plugin.
   */
  public function testExifPlugin() {
    // Prepare a copy of test files.
    $this->drupalGetTestFiles('image');
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg', 'public://', FILE_EXISTS_REPLACE);
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg', 'temporary://', FILE_EXISTS_REPLACE);
    // The image files that will be tested.
    $image_files = [
      [
        'uri' => drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg',
        'Orientation' => 8,
        'ShutterSpeedValue' => '106/32',
      ],
      [
        'uri' => 'public://test-exif.jpeg',
        'Orientation' => 8,
        'ShutterSpeedValue' => '106/32',
      ],
      [
        'uri' => 'dummy-remote://test-exif.jpeg',
        'local_path' => $this->container->get('file_system')->realpath('temporary://test-exif.jpeg'),
        'Orientation' => 8,
        'ShutterSpeedValue' => '106/32',
      ],
      [
        'uri' => 'public://image-test.jpg',
      ],
      [
        'uri' => NULL,
      ],
    ];

    $fmdm = $this->container->get('file_metadata_manager');
    foreach($image_files as $image_file) {
      $file_metadata = $fmdm->useUri($image_file['uri']);
      if (isset($image_file['local_path'])) {
        $file_metadata->setLocalPath($image_file['local_path']);
      }
      $this->assertIdentical(isset($image_file['Orientation']) ? $image_file['Orientation'] : NULL, $file_metadata->getMetadata('exif', 'Orientation'));
      $this->assertIdentical(isset($image_file['ShutterSpeedValue']) ? $image_file['ShutterSpeedValue'] : NULL, $file_metadata->getMetadata('exif', 'ShutterSpeedValue'));
    }
    $fmdm->debugDumpHashes();

  }

}
