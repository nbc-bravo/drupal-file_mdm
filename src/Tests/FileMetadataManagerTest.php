<?php

namespace Drupal\file_mdm\Tests;

use Drupal\file_mdm\FileMetadataInterface;
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
   * Tests using the 'getimagesize' plugin.
   */
  public function testFileMetadata() {
    // Prepare a copy of test files.
    $this->drupalGetTestFiles('image');
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg', 'public://', FILE_EXISTS_REPLACE);
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg', 'temporary://', FILE_EXISTS_REPLACE);
    // The image files that will be tested.
    $image_files = [
      [
        // Pass a path instead of the URI.
        'uri' => drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, 2],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // Pass a URI.
        'uri' => 'public://test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, 2],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // Remote storage file. Pass the path to a local copy of the file.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'local_path' => $this->container->get('file_system')->realpath('temporary://test-exif.jpeg'),
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, 2],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // JPEG Image with GPS data.
        'uri' => drupal_get_path('module', 'file_mdm') . '/tests/files/1024-2006_1011_093752.jpg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 1024],
          [1, 768],
          [2, 2],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // TIFF image.
        'uri' => drupal_get_path('module', 'file_mdm') . '/tests/files/sample-1.tiff',
        'count_keys' => 5,
        'test_keys' => [
          [0, 174],
          [1, 38],
          [2, 8],
          ['mime', 'image/tiff'],
        ],
      ],
      [
        // PNG image.
        'uri' => 'public://image-test.png',
        'count_keys' => 6,
        'test_keys' => [
          [0, 40],
          [1, 20],
          [2, 3],
          ['bits', 8],
          ['mime', 'image/png'],
        ],
      ],
    ];

    $fmdm = $this->container->get('file_metadata_manager');

    // Walk through test files.
    foreach($image_files as $image_file) {
      $file_metadata = $fmdm->useUri($image_file['uri']);
      if (isset($image_file['local_path'])) {
        $file_metadata->setLocalTempPath($image_file['local_path']);
      }
      $this->assertEqual($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEqual($test[1], $entry);
      }
    }

    // Test loading metadata from an in-memory object.
    $file_metadata_from = $fmdm->useUri($image_files[0]['uri']);
    $metadata = $file_metadata_from->getMetadata('getimagesize');
    $new_file_metadata = $fmdm->useUri('public://test-output.jpeg');
    $new_file_metadata->loadMetadata('getimagesize', $metadata);
    $this->assertEqual($image_files[0]['count_keys'], $this->countMetadataKeys($new_file_metadata, 'getimagesize'));
    foreach ($image_files[0]['test_keys'] as $test) {
      $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
      $this->assertEqual($test[1], $new_file_metadata->getMetadata('getimagesize', $test[0]));
    }
    
    /* @todo
       - improve localpath test, delete target uri (separate test block)
       - invalid keys get/set/remove
       - setMetadata
       - removeMetadata
       - caching (write to cache and reread after deleting file; read from cache then change data and resave to cache, re-read)
     */  
  }

  /**
   * Returns the count of metadata keys found in the file.
   *
   * @param \Drupal\file_mdm\FileMetadataInterface $file_md
   *   The FileMetadata object.
   * @param string $metadata_id
   *   The file metadata plugin id.
   * @param mixed $options
   *   (optional) Allows specifying additional options to control the list of
   *   metadata keys returned.
   *
   * @return int
   *   The count of metadata keys found in the file.
   */
  protected function countMetadataKeys(FileMetadataInterface $file_md, $metadata_id, $options = NULL) {
    $supported_keys = $file_md->getSupportedKeys($metadata_id, $options);
    $count = 0;
    foreach ($supported_keys as $key) {
      if ($entry = $file_md->getMetadata($metadata_id , $key)) {
        $count++;
      }
    }
    return $count;
  }

}
