# File metadata manager

A Drupal 8 module providing a file metadata manager service and API. Allows to get, via an unified API, information stored in files
like EXIF photo information, TrueType font information, etc. 

Metadata protocols are pluggable. Developers can implement a plugin and use the service framework to get the metadata required.
A 'getimagesize' plugin is provided, to manage through the service calls to the PHP ```getimagesize()``` function. 

This module is inspired by discussions at [#2630242 Provide methods to retrieve EXIF image information via the Image object](https://www.drupal.org/node/2630242).

A separate module implementing an EXIF plugin is work in progress, [File metadata EXIF](https://github.com/mondrake/file_mdm_exif).

----------------------
Warning: module is in development, not all stated below is implemented.
----------------------

## Features:

1. Load from, and save to, file embedded metadata directly from the files.
2. Metadata for a file is statically cached during a request's lifetime. This avoids different modules all repeat I/O on the same file.
3. Metadata can be cached in a Drupal cache bin to avoid repeating I/O on the files in successive requests.
4. Metadata standards (EXIF, TTF, etc.) are implemented as plugins. The service loads the metadata plugin needed based on the calling code request.
5. Manages copying to/from local temporary storage files stored in remote file systems, to allow PHP functions that do not support remote stream wrappers access the file locally.

## Usage examples:

All examples are based on using the 'getimagesize' plugin.

1. Basic usage:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_file_metadata = $fmdm->uri('public::/my_directory/test-image.jpeg');
    $mime = $my_file_metadata->getMetadata('getimagesize', 'mime');
    return ['#markup' => 'MIME type: ' . $mime];
  ```
  
  will return something like
  ```
  MIME type: image/jpeg
  ```

2. Save metadata to cache, so that following requests avoid re-reading from the file:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_file_metadata = $fmdm->uri('public::/my_directory/test-image.jpeg');
    $my_file_metadata->loadMetadata('getimagesize');
    $my_file_metadata->saveMetadataToCache('getimagesize');
    ...
  ```

3. Use a known local temp copy of the remote file to avoid remote file access:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_file_metadata = $fmdm->uri('remote_wrapper::/my_directory/test-image.jpeg');
    $my_file_metadata->setLocalTempPath($temp_path);
    $mime = $my_file_metadata->getMetadata('getimagesize', 'mime');
    ...
  ```

4. Make a local temp copy of the remote file to avoid remote file access:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_file_metadata = $fmdm->uri('remote_wrapper::/my_directory/test-image.jpeg');
    $my_file_metadata->copyUriToTemp();
    $mime = $my_file_metadata->getMetadata('getimagesize', 'mime');
    ...
  ```
