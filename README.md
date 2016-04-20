# file_mdm
File metadata manager - Drupal 8 module

A file metadata manager service taking cues from discussions at [#2630242 Provide methods to retrieve EXIF image information via the Image object](https://www.drupal.org/node/2630242)

## Ideas:

1. Should be able to load and save file embedded metadata (e.g. EXIF) directly from the files.
2. Should be also able to load/save metadata from serialised objects that can then be used in cache and or stored e.g. in file entities.
3. Metadata should be statically cached in memory in the context of a request's lifetime. E.g. avoid different modules all call _exif_read_data_ with multiple I/O, load in memory once and then get from it.
4. Should be pluggable i.e. the manager should use a plugin to get metadata of a specific standard from a file.
5. Should provide an EXIF plugin to load data from image files.
6. Should be able to specify a local path where a copy of a file stored in a remote stream wrapper exists, so that functions that do not support stream wrappers can find a viable alternative to fetch metadata.

## Usage:

```php
  $fmdm = \Drupal::service('file_metadata_manager');
  $file_metadata = $fmdm->useUri(drupal_get_path('module', 'file_mdm') . '/tests/files/test-exif.jpeg');
  $make = $file_metadata->getMetadata('exif', 'Make');
  $model = $file_metadata->getMetadata('exif', 'Model');
  return ['#markup' => 'make: ' . $make . ' - model: ' . $model];
```

will return
```
make: Canon - model: Canon PowerShot SX10 IS
```
