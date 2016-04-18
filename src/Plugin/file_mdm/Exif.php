<?php

namespace Drupal\file_mdm\Plugin\file_mdm;

use Drupal\Core\Plugin\PluginBase;
use Drupal\file_mdm\Plugin\FileMetadataInterface;

/**
 * FileMetadata plugin for EXIF.
 *
 * @FileMetadata(
 *   id = "exif",
 *   help = @Translation("FileMetadata plugin for EXIF."),
 * )
 */
class Exif extends PluginBase implements FileMetadataInterface {

  /**
   * {@inheritdoc}
   */
  public function get($uri) {
    if (!file_exists($uri)) {
      // File does not exists.
      return NULL;
    }
    $mime_type = \Drupal::service('file.mime_type.guesser')->guess($uri);  // @todo injection
    if (!in_array($mime_type, ['image/jpeg', 'image/tiff'])) {
      // File does not support EXIF.
      return NULL;
    }
    if (!function_exists('exif_read_data')) {
      // No PHP EXIF extension enabled.
      //$this->logger->error('@todo.');
      return NULL;
    }
    if ($exif_data = @exif_read_data($uri)) {
      return $exif_data;
    }
    else {
      // No data or read error.
      return NULL;
    }
  }

}
