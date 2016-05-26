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

  /**
   * The URI of the file.
   *
   * @var string
   */
  protected $uri = '';

  /**
   * The local filesystem path to the file.
   *
   * This is used to allow accessing local copies of files stored remotely, to
   * minimise remote calls and allow functions that cannot access remote stream
   * wrappers to operate locally.
   *
   * @var string
   */
  protected $localPath = '';

  protected $plugins = [];

  public function __construct(FileMetadataPluginManager $plugin_manager, $uri) {
    $this->pluginManager = $plugin_manager;
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalPath() {
    return $this->localPath;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocalPath($path) {
    $this->localPath = $path;
    foreach ($this->plugins as $plugin) {
      $plugin->setLocalPath($this->localPath);
    }
    return $this;
  }

  /**
   * @todo
   */
  public function getFileMetadataPlugin($metadata_id) {
    if (!isset($this->plugins[$metadata_id])) {
      $this->plugins[$metadata_id] = $this->pluginManager->createInstance($metadata_id);
      $this->plugins[$metadata_id]
        ->setUri($this->uri)
        ->setLocalPath($this->localPath);
    }
    return $this->plugins[$metadata_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($metadata_id, $key = NULL) {
    try {
      $plugin = $this->getFileMetadataPlugin($metadata_id);
      $metadata = $plugin->getMetadata($key);
    }
    catch (\RuntimeException $e) {
      $this->logger->error($e->getMessage());
      $metadata = NULL;
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function setMetadata($metadata_id, $key, $value) {
    $plugin = $this->getFileMetadataPlugin($metadata_id);
    return $plugin->setMetadata($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadata($metadata_id, $metadata) {
    $plugin = $this->getFileMetadataPlugin($metadata_id);
    return $plugin->loadMetadata($metadata);
  }

}
