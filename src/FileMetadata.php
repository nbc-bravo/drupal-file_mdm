<?php

namespace Drupal\file_mdm;

use Drupal\file_mdm\Plugin\FileMetadataPluginManager;

/**
 * A file metadata object.
 */
class FileMetadata { // @todo implements

  /**
   * The FileMetadata plugin manager.
   *
   * @var \Drupal\file_mdm\Plugin\FileMetadataPluginManager
   */
  protected $pluginManager;

  protected $uri;
  protected $plugins = [];

  public function __construct(FileMetadataPluginManager $plugin_manager, $uri) {
    $this->pluginManager = $plugin_manager;
    $this->uri = $uri;
  }

  protected function getFileMetadataPlugin($metadata_id) {
    if (!isset($this->plugins[$metadata_id])) {
      $this->plugins[$metadata_id] = $this->pluginManager->createInstance($metadata_id);
      $this->plugins[$metadata_id]->setUri($this->uri);
    }
    return $this->plugins[$metadata_id];
  }

  public function getMetadata($metadata_id, $key = NULL) {
    $plugin = $this->getFileMetadataPlugin($metadata_id);
    $metadata = $plugin->getMetadata($key);
    return $metadata;
  }

}
