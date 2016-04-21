<?php

namespace Drupal\file_mdm\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for FileMetadata plugins.
 */
class FileMetadataPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FileMetadata', $namespaces, $module_handler, 'Drupal\file_mdm\Plugin\FileMetadataPluginInterface', 'Drupal\file_mdm\Plugin\Annotation\FileMetadata');
    $this->alterInfo('file_metadata_plugin_info');
    $this->setCacheBackend($cache_backend, 'file_metadata_plugins');
  }

}
