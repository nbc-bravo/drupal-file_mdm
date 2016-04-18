<?php

namespace Drupal\file_mdm\Plugin;

/**
 * Provides an interface defining a FileMetadata plugin.
 */
interface FileMetadataInterface {

  /**
   * @todo
   */
  public function get($uri);

}
