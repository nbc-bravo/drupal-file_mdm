<?php

namespace Drupal\file_mdm;

use Drupal\file_mdm\Plugin\FileMetadataPluginManager;
use Psr\Log\LoggerInterface;

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
   * The file_mdm logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The URI of the file.
   *
   * @var string
   */
  protected $uri = '';

  /**
   * The hash used to reference the URI.
   *
   * @var string
   */
  protected $hash;

  /**
   * The local filesystem path to the file.
   *
   * This is used to allow accessing local copies of files stored remotely, to
   * minimise remote calls and allow functions that cannot access remote stream
   * wrappers to operate locally.
   *
   * @var string
   */
  protected $localTempPath;

  protected $plugins = [];

  public function __construct(FileMetadataPluginManager $plugin_manager, LoggerInterface $logger, $uri, $hash) {
    $this->pluginManager = $plugin_manager;
    $this->logger = $logger;
    $this->uri = $uri;
    $this->hash = $hash;
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
  public function getLocalTempPath() {
    return $this->localTempPath;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocalTempPath($path) {
    $this->localTempPath = $path;
    return $this;
  }

  /**
   * @todo
   */
  public function getFileMetadataPlugin($metadata_id) {
    // @todo excpetion if plugin missing
    if (!isset($this->plugins[$metadata_id])) {
      $this->plugins[$metadata_id] = $this->pluginManager->createInstance($metadata_id);
      $this->plugins[$metadata_id]->setUri($this->localTempPath ?: $this->uri);
      $this->plugins[$metadata_id]->setHash($this->hash);
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
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $metadata = NULL;
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedKeys($metadata_id, $options = NULL) {
    try {
      $plugin = $this->getFileMetadataPlugin($metadata_id);
      $keys = $plugin->getSupportedKeys($options);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $keys = NULL;
    }
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function setMetadata($metadata_id, $key, $value) {
    try {
      $plugin = $this->getFileMetadataPlugin($metadata_id);
      $success = $plugin->setMetadata($key, $value);
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $success = FALSE;
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadata($metadata_id, $metadata) {
    $plugin = $this->getFileMetadataPlugin($metadata_id);
    return $plugin->loadMetadata($metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadataFromCache($metadata_id) {
    $plugin = $this->getFileMetadataPlugin($metadata_id);
    return $plugin->loadMetadataFromCache();
  }

  /**
   * {@inheritdoc}
   */
  public function saveMetadataToCache($metadata_id) {
    $plugin = $this->getFileMetadataPlugin($metadata_id);
    return $plugin->saveMetadataToCache();
  }

  /**
   * {@inheritdoc}
   */
  public function saveMetadataToFile($metadata_id) {
    $plugin = $this->getFileMetadataPlugin($metadata_id);
    return $plugin->saveMetadataToFile();
  }

}
