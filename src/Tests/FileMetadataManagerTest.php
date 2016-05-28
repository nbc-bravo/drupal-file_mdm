<?php

namespace Drupal\file_mdm\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that File Metadata Manager works properly.
 *
 * @group File Metadata Manager
 */
class FileMetadataManagerTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;  // @todo

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'simpletest', 'file_mdm', 'file_mdm_exif', 'file_test'];

  /**
   * Test EXIF plugin.
   */
  public function testExifPlugin() {
    // Prepare a copy of test files.
    $this->drupalGetTestFiles('image');
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm_exif') . '/tests/files/test-exif.jpeg', 'public://', FILE_EXISTS_REPLACE);
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm_exif') . '/tests/files/test-exif.jpeg', 'temporary://', FILE_EXISTS_REPLACE);
    // The image files that will be tested.
    $image_files = [
      [
        // Pass a path instead of the URI.
        'uri' => drupal_get_path('module', 'file_mdm_exif') . '/tests/files/test-exif.jpeg',
        'count' => 80,
        'Orientation' => 8,
        'ShutterSpeedValue' => [106, 32],
      ],
      [
        // Pass a URI.
        'uri' => 'public://test-exif.jpeg',
        'count' => 80,
        'Orientation' => 8,
        'ShutterSpeedValue' => [106, 32],
      ],
      [
        // Remote storage file. Pass the path to a local copy of the file.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'local_path' => $this->container->get('file_system')->realpath('temporary://test-exif.jpeg'),
        'count' => 80,
        'Orientation' => 8,
        'ShutterSpeedValue' => [106, 32],
      ],
 /*     [
        // Image with no EXIF data. Still, exif_read_data returns some info.
        'uri' => 'public://image-test.jpg',
        'count' => 7,
      ],
      [
        // PNG should not reach exif_read_data.
        'uri' => 'public://image-test.png',
        'count' => 0,
      ],  */
    ];

    $fmdm = $this->container->get('file_metadata_manager');

    // Walk through test files.
    foreach($image_files as $image_file) {
      $file_metadata = $fmdm->useUri($image_file['uri']);
      if (isset($image_file['local_path'])) {
        $file_metadata->setLocalTempPath($image_file['local_path']);
      }
      //$this->assertEqual($image_file['count'], count($file_metadata->getMetadata('exif')));
      if (isset($image_file['Orientation'])) {
        $entry = $file_metadata->getMetadata('exif', 'Orientation');
        $this->assertEqual($image_file['Orientation'], $entry ? $entry->getValue() : NULL);
      }
      if (isset($image_file['ShutterSpeedValue'])) {
        $entry = $file_metadata->getMetadata('exif', 'ShutterSpeedValue');
        $this->assertEqual($image_file['ShutterSpeedValue'], $entry ? $entry->getValue() : NULL);
      }
    }

    // Test loading metadata from an in-memory object.
    $file_metadata_from = $fmdm->useUri($image_files[0]['uri']);
    $metadata = $file_metadata_from->getMetadata('exif');
    $new_file_metadata = $fmdm->useUri('public://test-output.jpeg');
    $new_file_metadata->loadMetadata('exif', $metadata);
    //$this->assertEqual($image_files[0]['count'], count($new_file_metadata->getMetadata('exif')));
    $this->assertEqual($image_files[0]['Orientation'], $new_file_metadata->getMetadata('exif', 'Orientation')->getValue());
    $this->assertEqual($image_files[0]['ShutterSpeedValue'], $new_file_metadata->getMetadata('exif', 'ShutterSpeedValue')->getValue());


    $fmdm->debugDumpHashes();

  }

}
