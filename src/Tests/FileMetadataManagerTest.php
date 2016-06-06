<?php

namespace Drupal\file_mdm\Tests;

/**
 * Tests that File Metadata Manager works properly.
 *
 * @group File Metadata Manager
 */
class FileMetadataManagerTest extends FileMetadataManagerTestBase {

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
    // The image files that will be tested.
    $image_files = [
      [
        // Pass a path instead of the URI.
        'uri' => drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
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
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
      [
        // PHP getimagesize works on remote stream wrappers.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
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
          [2, IMAGETYPE_JPEG],
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
          [2, IMAGETYPE_TIFF_MM],
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
          [2, IMAGETYPE_PNG],
          ['bits', 8],
          ['mime', 'image/png'],
        ],
      ],
    ];

    // Get the file metadata manager service.
    $fmdm = $this->container->get('file_metadata_manager');

    // Walk through test files.
    foreach ($image_files as $image_file) {
      $file_metadata = $fmdm->uri($image_file['uri']);
      // Read from file.
      $this->assertEqual($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEqual($test[1], $entry);
      }
      // Try getting an unsupported key.
      $this->assertNull($file_metadata->getMetadata('getimagesize', 'baz'));
      // Try getting an invalid key.
      $this->assertNull($file_metadata->getMetadata('getimagesize', ['qux' => 'laa']));
      // Change MIME type.
      $this->assertTrue($file_metadata->setMetadata('getimagesize', 'mime', 'foo/bar'));
      $this->assertEqual('foo/bar', $file_metadata->getMetadata('getimagesize', 'mime'));
      // Try adding an unsupported key.
      $this->assertFalse($file_metadata->setMetadata('getimagesize', 'baz', 'qux'));
      $this->assertNull($file_metadata->getMetadata('getimagesize', 'baz'));
      // Try adding an invalid key.
      $this->assertFalse($file_metadata->setMetadata('getimagesize', ['qux' => 'laa'], 'hoz'));
      // Remove MIME type.
      $this->assertTrue($file_metadata->removeMetadata('getimagesize', 'mime'));
      $this->assertEqual($image_file['count_keys'] - 1, $this->countMetadataKeys($file_metadata, 'getimagesize'));
      $this->assertNull($file_metadata->getMetadata('getimagesize', 'mime'));
      // Try removing an unsupported key.
      $this->assertFalse($file_metadata->removeMetadata('getimagesize', 'baz'));
      // Try removing an invalid key.
      $this->assertFalse($file_metadata->removeMetadata('getimagesize', ['qux' => 'laa']));
    }

    // Test releasing URI.
    $this->assertEqual(6, $fmdm->count());
    $this->assertTrue($fmdm->has($image_files[0]['uri']));
    $this->assertTrue($fmdm->release($image_files[0]['uri']));
    $this->assertEqual(5, $fmdm->count());
    $this->assertFalse($fmdm->has($image_files[0]['uri']));
    $this->assertFalse($fmdm->release($image_files[0]['uri']));

    // Test loading metadata from an in-memory object.
    $file_metadata_from = $fmdm->uri($image_files[0]['uri']);
    $this->assertEqual(6, $fmdm->count());
    $metadata = $file_metadata_from->getMetadata('getimagesize');
    $new_file_metadata = $fmdm->uri('public://test-output.jpeg');
    $this->assertEqual(7, $fmdm->count());
    $new_file_metadata->loadMetadata('getimagesize', $metadata);
    $this->assertEqual($image_files[0]['count_keys'], $this->countMetadataKeys($new_file_metadata, 'getimagesize'));
    foreach ($image_files[0]['test_keys'] as $test) {
      $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
      $this->assertEqual($test[1], $new_file_metadata->getMetadata('getimagesize', $test[0]));
    }

    /* @todo
       - invalid plugin
       - caching (write to cache and reread after deleting file; read from cache then change data and resave to cache, re-read)
     */
  }

  /**
   * Tests remote files, setting local temp path explicitly.
   */
  public function testRemoteFileSetLocalPath() {
    // The image files that will be tested.
    $image_files = [
      [
        // Remote storage file. Pass the path to a local copy of the file.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'local_path' => $this->container->get('file_system')->realpath('temporary://test-exif.jpeg'),
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
    ];

    // Get the file metadata manager service.
    $fmdm = $this->container->get('file_metadata_manager');

    // Copy the test file to a temp location.
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg', 'temporary://', FILE_EXISTS_REPLACE);

    // Test setting local temp path explicitly. The files should be parsed
    // even if not available on the URI.
    foreach ($image_files as $image_file) {
      $file_metadata = $fmdm->uri($image_file['uri']);
      $file_metadata->setLocalTempPath($image_file['local_path']);
      // No file to be found at URI.
      $this->assertFalse(file_exists($image_file['uri']));
      // File to be found at local temp path.
      $this->assertTrue(file_exists($file_metadata->getLocalTempPath()));
      $this->assertEqual($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEqual($test[1], $entry);
      }
      // Copies temp to destination URI.
      $this->assertTrue($file_metadata->copyTempToUri());
      $this->assertTrue(file_exists($image_file['uri']));
    }
  }

  /**
   * Tests remote files, letting file_mdm manage setting local temp path.
   */
  public function testRemoteFileCopy() {
    // The image files that will be tested.
    $image_files = [
      [
        // Remote storage file. Pass the path to a local copy of the file.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'count_keys' => 7,
        'test_keys' => [
          [0, 100],
          [1, 75],
          [2, IMAGETYPE_JPEG],
          ['bits', 8],
          ['channels', 3],
          ['mime', 'image/jpeg'],
        ],
      ],
    ];

    // Get the file metadata manager service.
    $fmdm = $this->container->get('file_metadata_manager');
    $file_system = $this->container->get('file_system');

    // Copy the test file to dummy-remote wrapper.
    file_unmanaged_copy(drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg', 'dummy-remote://', FILE_EXISTS_REPLACE);

    foreach ($image_files as $image_file) {
      $file_metadata = $fmdm->uri($image_file['uri']);
      $file_metadata->copyUriToTemp();
      // File to be found at destination URI.
      $this->assertTrue(file_exists($image_file['uri']));
      // File to be found at local temp URI.
      $this->assertEqual('temporary', $file_system->uriScheme($file_metadata->getLocalTempPath()));
      $this->assertIdentical(0, strpos($file_system->basename($file_metadata->getLocalTempPath()), 'file_mdm_'));
      $this->assertTrue(file_exists($file_metadata->getLocalTempPath()));
      $this->assertEqual($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'getimagesize'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('getimagesize', $test[0]);
        $this->assertEqual($test[1], $entry);
      }
    }
  }

}
