<?php

namespace Drupal\file_mdm;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_mdm\Plugin\FileMetadataPluginManager;
use Psr\Log\LoggerInterface;

/**
 * A service class to provide file metadata.
 */
class FileMetadataManager { // @todo implements

  use StringTranslationTrait;

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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $files = [];

  /**
   * Constructs a FileMetadataManager object.
   *
   * @param \Drupal\file_mdm\Plugin\FileMetadataPluginManager $plugin_manager
   *   The FileMetadata plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The file_mdm logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(FileMetadataPluginManager $plugin_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->pluginManager = $plugin_manager;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * @todo
   */
  public function setFile($uri) {
    if (file_exists($uri)) {
      $file_hash = hash('sha256', $uri);
      if (!isset($this->files[$file_hash])) {
        $this->files[$file_hash] = ['uri' => $uri];
      }
      return $file_hash;
    }
    return NULL;
  }

  protected function getFileMetadataPlugin($file_hash, $metadata_id) {
    if (!isset($this->files[$file_hash]['plugins'][$metadata_id])) {
      $this->files[$file_hash]['plugins'][$metadata_id] = $this->pluginManager->createInstance($metadata_id);
      $this->files[$file_hash]['plugins'][$metadata_id]->setUri($this->files[$file_hash]['uri']);
    }
    return $this->files[$file_hash]['plugins'][$metadata_id];
  }

  public function getMetadata($file_hash, $metadata_id, $key = NULL) {
    if (!isset($this->files[$file_hash])) {
      throw new \RuntimeException('File entry not initialised');
    }
    $plugin = $this->getFileMetadataPlugin($file_hash, $metadata_id, $key);
    $metadata = $plugin->getMetadata($key);
    return $metadata;
  }

}
