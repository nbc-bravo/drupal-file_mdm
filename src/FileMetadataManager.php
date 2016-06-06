<?php

namespace Drupal\file_mdm;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
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

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(FileMetadataPluginManager $plugin_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system) {
    $this->pluginManager = $plugin_manager;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
  }

  /**
   * @todo
   */
  public function hasUri($uri) {
    $uri_hash = hash('sha256', $uri);
    return isset($this->files[$uri_hash]) ? $uri_hash : FALSE;
  }

  /**
   * @todo
   */
  public function useUri($uri) {
    $hash = $this->hasUri($uri);
    if (!$hash) {
      $hash = hash('sha256', $uri);
      $this->files[$hash] = new FileMetadata($this->pluginManager, $this->logger, $this->fileSystem, $uri, $hash);
    }
    return $this->files[$hash];
  }

  /**
   * @todo
   */
  public function releaseUri($uri) {
    if ($hash = $this->hasUri($uri)) {
      unset($this->files[$hash]);
    }
    return $this;
  }

}
