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
        'count_keys' => 48,
        'test_keys' => [
          ['Orientation', 8],
          ['orientation', 8],
          ['OrIeNtAtIoN', 8],
          ['ShutterSpeedValue', [106, 32]],
          ['ApertureValue', [128, 32]],
          [['exif', 'aperturevalue'], [128, 32]],
          [[2, 'aperturevalue'], [128, 32]],
          [['exif', 0x9202], [128, 32]],
          [[2, 0x9202], [128, 32]],
        ],
      ],
      [
        // Pass a URI.
        'uri' => 'public://test-exif.jpeg',
        'count_keys' => 48,
        'test_keys' => [
          ['Orientation', 8],
          ['ShutterSpeedValue', [106, 32]],
        ],
      ],
      [
        // Remote storage file. Pass the path to a local copy of the file.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'local_path' => $this->container->get('file_system')->realpath('temporary://test-exif.jpeg'),
        'count_keys' => 48,
        'test_keys' => [
          ['Orientation', 8],
          ['ShutterSpeedValue', [106, 32]],
        ],
      ],
      [
        // JPEG Image with GPS data.
        'uri' => drupal_get_path('module', 'file_mdm_exif') . '/tests/files/1024-2006_1011_093752.jpg',
        'count_keys' => 59,
        'test_keys' => [
          ['Orientation', 1],
          ['FocalLength', [8513, 256]],
          ['GPSLatitudeRef', 'S'],
          ['GPSLatitude', [[33, 1], [51, 1], [2191, 100]]],
          ['GPSLongitudeRef', 'E'],
          ['GPSLongitude', [[151, 1], [13, 1], [1173, 100]]],
        ],
      ],
      [
        // JPEG Image with no EXIF data.
        'uri' => 'public://image-test.jpg',
        'count_keys' => 0,
        'test_keys' => [],
      ],
      [
        // TIFF image.
        'uri' => drupal_get_path('module', 'file_mdm_exif') . '/tests/files/sample-1.tiff',
        'count_keys' => 11,
        'test_keys' => [
          ['Orientation', 1],
          ['BitsPerSample', [8, 8, 8, 8]],
        ],
      ],
      [
        // PNG should not have any data.
        'uri' => 'public://image-test.png',
        'count_keys' => 0,
        'test_keys' => [],
      ],
    ];

    $fmdm = $this->container->get('file_metadata_manager');

    // Walk through test files.
    foreach($image_files as $image_file) {
      $file_metadata = $fmdm->useUri($image_file['uri']);
      if (isset($image_file['local_path'])) {
        $file_metadata->setLocalTempPath($image_file['local_path']);
      }
      $this->assertEqual($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'exif'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('exif', $test[0]);
        $this->assertEqual($test[1], $entry ? $entry->getValue() : NULL);
      }
    }

    // Test loading metadata from an in-memory object.
    $file_metadata_from = $fmdm->useUri($image_files[0]['uri']);
    $metadata = $file_metadata_from->getMetadata('exif');
    $new_file_metadata = $fmdm->useUri('public://test-output.jpeg');
    $new_file_metadata->loadMetadata('exif', $metadata);
    $this->assertEqual($image_files[0]['count_keys'], $this->countMetadataKeys($new_file_metadata, 'exif'));
    foreach ($image_files[0]['test_keys'] as $test) {
      $entry = $file_metadata->getMetadata('exif', $test[0]);
      $this->assertEqual($test[1], $new_file_metadata->getMetadata('exif', $test[0])->getValue());
    }

    $fmdm->debugDumpHashes();

  }

  /**
   * @todo
   */
  protected function countMetadataKeys($file_metadata, $metadata_id, $options = NULL) {
    $supported_keys = $file_metadata->getSupportedKeys($metadata_id, $options);
    $keys = 0;
    foreach ($supported_keys as $key) {
      if ($entry = $file_metadata->getMetadata($metadata_id , $key)) {
        $keys++;
      }
    }
    return $keys;
  }

}
