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
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm') . '/misc/test-exif.jpeg', 'dummy-remote://', FILE_EXISTS_REPLACE);
    // The image files that will be tested.
    $image_files = [
      [
        'uri' => drupal_get_path('module', 'file_mdm') . '/misc/test-exif.jpeg',
        'Orientation' => 8,
        'ShutterSpeedValue' => '106/32',
      ],
      [
        'uri' => 'dummy-remote://test-exif.jpeg',
        'Orientation' => 8,
        'ShutterSpeedValue' => '106/32',
      ],
      [
        'uri' => 'public://image-test.jpg',
        'Orientation' => NULL,
        'ShutterSpeedValue' => NULL,
      ],
      [
        'uri' => 'public://image-test.png',
        'Orientation' => NULL,
        'ShutterSpeedValue' => NULL,
      ],
      [
        'uri' => 'public://image-test.gif',
        'Orientation' => NULL,
        'ShutterSpeedValue' => NULL,
      ],
      [
        'uri' => NULL,
        'Orientation' => NULL,
        'ShutterSpeedValue' => NULL,
      ],
    ];

    $fmdm = $this->container->get('file_metadata_manager');
    foreach($image_files as $image_file) {
      $file_metadata = $fmdm->useUri($image_file['uri']);
      $this->assertIdentical($image_file['Orientation'], $file_metadata->getMetadata('exif', 'Orientation'));
      $this->assertIdentical($image_file['ShutterSpeedValue'], $file_metadata->getMetadata('exif', 'ShutterSpeedValue'));
    }
    $fmdm->debugDumpHashes();

  }

}
