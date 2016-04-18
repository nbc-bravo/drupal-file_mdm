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
  public function getFileHandle($uri) {
    if (file_exists($uri)) {
      $handle = hash('sha256', $uri);
      if (!isset($this->files[$handle])) {
        $this->files[$handle] = ['uri' => $uri];
      }
      return $handle;
    }
    return NULL;
  }

  protected function getFileMetadataPlugin($handle, $metadata_id) {
    if (!isset($this->files[$handle])) {
      throw new \RuntimeException('File entry not initialised');
    }
    if (!isset($this->files[$handle]['plugins'][$metadata_id])) {
      $this->files[$handle]['plugins'][$metadata_id] = $this->pluginManager->createInstance($metadata_id);
      $this->files[$handle]['plugins'][$metadata_id]->setUri($this->files[$handle]['uri']);
    }
    return $this->files[$handle]['plugins'][$metadata_id];
  }

  public function getFileMetadataFromFile($handle, $metadata_id) {
    $plugin = $this->getFileMetadataPlugin($handle, $metadata_id);
    $metadata = $plugin->getMetadataFromUri();
    return $metadata;
  }

}
