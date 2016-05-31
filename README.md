# File metadata manager

A Drupal 8 module providing a file metadata manager service and API. Allows to get, via an unified API, information stored in files
like EXIF photo information, TrueType font information, etc. 

Metadata protocols are defined as plugins, so developers can implement a plugin and use the service to get the metadata required.

It also provides an EXIF metadata plugin, which uses the [PHP Exif Library](https://github.com/lsolesen/pel) to read/write EXIF information to image files, so bypassing the limitations of the standard PHP Exif extensions which only provides read capabilities.

This module is inspired by discussions at [#2630242 Provide methods to retrieve EXIF image information via the Image object](https://www.drupal.org/node/2630242).

The module uses Composer to get its dependencies.

----------------------
Warning: module is in development, not all stated below is implemented.
----------------------

## Service / API features:

1. Load from, and save to, file embedded metadata directly from the files.
2. Metadata for a file is cached in memory during a request's lifetime. This avoids different modules all repeat I/O on the same file.
3. Metadata can also be cached in a Drupal cache bin to avoid repeating I/O on the files in successive requests.
4. Metadata standards (EXIF, TTF, etc.) are implemented as plugins. The service loads the metadata plugin needed based on the calling code request.
5. Manages copying to/from local temporary storage files stored in remote file systems, to allow PHP functions that do not support remote stream wrappers access the file locally.

## EXIF plugin features:

1. Uses the [PHP Exif Library](https://github.com/lsolesen/pel) to read/write EXIF information to image files.
2. Provides an abstraction layer to retrieve EXIF tags via metadata 'keys' and avoid need to know EXIF implementation details.

## Usage examples:

All examples are based on using the EXIF plugin.

1. Basic usage:

```php
  $fmdm = \Drupal::service('file_metadata_manager');
  $my_file_metadata = $fmdm->useUri('public::/my_directory/test-exif.jpeg');
  $make = $my_file_metadata->getMetadata('exif', 'Make');
  $model = $my_file_metadata->getMetadata('exif', 'Model');
  return ['#markup' => 'make: ' . $make->getValue() . ' - model: ' . $model->getValue()];
```

will return something like
```
make: Canon - model: Canon PowerShot SX10 IS
```

2. Save metadata to cache, so that following requests avoid re-reading from the file:

```php
  $fmdm = \Drupal::service('file_metadata_manager');
  $my_file_metadata = $fmdm->useUri('public::/my_directory/test-exif.jpeg');
  $my_file_metadata->loadMetadata('exif');
  $my_file_metadata->saveMetadataToCache('exif');
  $make = $my_file_metadata->getMetadata('exif', 'Make');
  ...
```

3. Use a known local temp copy of the remote file to avoid remote file access:

```php
  $fmdm = \Drupal::service('file_metadata_manager');
  $my_file_metadata = $fmdm->useUri('public::/my_directory/test-exif.jpeg');
  $my_file_metadata->setLocalTempPath($temp_path);
  $make = $my_file_metadata->getMetadata('exif', 'Make');
  ...
```

4. Make a local temp copy of the remote file to avoid remote file access:

```php
  $fmdm = \Drupal::service('file_metadata_manager');
  $my_file_metadata = $fmdm->useUri('public::/my_directory/test-exif.jpeg');
  $my_file_metadata->copyUriToTemp();
  $make = $my_file_metadata->getMetadata('exif', 'Make');
  ...
```

