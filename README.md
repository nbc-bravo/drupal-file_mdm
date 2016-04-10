# file_mdm
File metadata manager - Drupal 8 module

A file metadata manager service taking clues from discussions at [#2630242 Provide methods to retrieve EXIF image information via the Image object](https://www.drupal.org/node/2630242)

Ideas:
1. Should be able to load and save file embedded metadata directly from the files (e.g. EXIF)
2. Should be also able to load/save metadata from serialised objects that can then be used in cache and or stored in file entities.
3. Metadata should be statically cached in the context of a request's lifetime. E.g. avoid different modules all call _exif_read_data_ with multiple I/O, load once and then get from cache
4. Should be pluggable i.e. the manager should use a plugin to get metadata of a specific standard from a file
5. Should provide an EXIF plugin to load data from image files.
