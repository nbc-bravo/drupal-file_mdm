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
  protected function calculateHash($uri) {
    // @todo error if uri is null or invalid
    return hash('sha256', $uri);
  }

  /**
   * @todo
   */
  public function has($uri) {
    $hash = $this->calculateHash($uri);
    return isset($this->files[$hash]);
  }

  /**
   * @todo
   */
  public function uri($uri) {
    $hash = $this->calculateHash($uri);
    if (!isset($this->files[$hash])) {
      $this->files[$hash] = new FileMetadata($this->pluginManager, $this->logger, $this->fileSystem, $uri, $hash);
    }
    return $this->files[$hash];
  }

  /**
   * @todo
   */
  public function release($uri) {
    $hash = $this->calculateHash($uri);
    if (isset($this->files[$hash])) {
      unset($this->files[$hash]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @todo
   */
  public function count() {
    return count($this->files);
  }

}
