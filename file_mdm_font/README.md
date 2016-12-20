# File metadata Font

A Drupal 8 module providing a file metadata plugin to retrieve information from
font files.

## Features:

1. Uses the [PHP Font Lib](https://github.com/PhenX/php-font-lib) to read font
   information from TTF/OTF/WOFF font files.
2. Provides an abstraction layer to retrieve font information via metadata
   'keys'.

## Available keys:

Key                 |
--------------------|
FontType            |
FontWeight          |
Copyright           |
FontName            |
FontSubfamily       |
UniqueID            |
FullName            |
Version             |
PostScriptName      |
Trademark           |
Manufacturer        |
Designer            |
Description         |
FontVendorURL       |
FontDesignerURL     |
LicenseDescription  |
LicenseURL          |
PreferredFamily     |
PreferredSubfamily  |
CompatibleFullName  |
SampleText          |

## Code examples:

1. Initialize the metadata object for the desired font file:

  ```php
    $fmdm = \Drupal::service('file_metadata_manager');
    $my_font_metadata = $fmdm->uri('public::/my_font_directory/arial.ttf');
    ...
  ```

2. Get the value of a key:

  ```php
    ...
    $font_name = $my_font_metadata->getMetadata('font', 'FontName');
    return ['#markup' => 'Font name: ' . $font_name];
  ```

  will return something like
  ```
    Font name: Arial
  ```

3. Get an array with all the metadata values:

  ```php
    ...
    $my_font_info = [];
    foreach ($my_font_metadata->getSupportedKeys('font') as $key) {
      $my_font_info[$key] = $my_font_metadata->getMetadata('font', $key);
    }
    ...
  ```
