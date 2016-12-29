# File metadata EXIF

A Drupal 8 module providing a file metadata plugin for the EXIF protocol.

This module is inspired by discussions at [#2630242 Provide methods to retrieve EXIF image information via the Image object](https://www.drupal.org/node/2630242).

----------------------
Warning: module is in development, not all stated below is implemented.
----------------------


## EXIF plugin features:

The module provides an EXIF metadata plugin, which uses the [PHP Exif Library](https://github.com/lsolesen/pel) to read/write EXIF information to image files, so bypassing the limitations of the standard PHP Exif extensions which only provides read capabilities.
The module uses Composer to get its dependencies.

1. Uses the [PHP Exif Library](https://github.com/lsolesen/pel) to read/write EXIF information to image files.
2. Provides an abstraction layer to retrieve EXIF tags via metadata 'keys' and avoid the need to know Pel/EXIF implementation details.

## Usage examples:

1. Basic usage:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_file_metadata = $fmdm->uri('public::/my_directory/test-exif.jpeg');
    $make = $my_file_metadata->getMetadata('exif', 'Make');
    $model = $my_file_metadata->getMetadata('exif', 'Model');
    return ['#markup' => 'make: ' . $make['value'] . ' - model: ' . $model['value']];
  ```

  will return something like
  ```
  make: Canon - model: Canon PowerShot SX10 IS
  ```

2. Use a known local temp copy of the remote file to avoid remote file access:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_file_metadata = $fmdm->uri('remote-wrapper::/my_directory/test-exif.jpeg');
    $my_file_metadata->setLocalTempPath($my_temp_path);
    $make = $my_file_metadata->getMetadata('exif', 'Make');
    ...
  ```

3. Make a local temp copy of the remote file to avoid remote file access:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_file_metadata = $fmdm->uri('remote-wrapper::/my_directory/test-exif.jpeg');
    $my_file_metadata->copyUriToTemp();
    $make = $my_file_metadata->getMetadata('exif', 'Make');
    ...
  ```

