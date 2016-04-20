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
  public function debugDumpHashes() {
    $ret = [];
    foreach ($this->files as $hash => $file) {
      $ret[] = [$hash, $file->getUri(), $file->getLocalPath()];
    }
    debug($ret);
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
    $uri_hash = $this->hasUri($uri);
    if (!$uri_hash) {
      $uri_hash = hash('sha256', $uri);
      $this->files[$uri_hash] = new FileMetadata($this->pluginManager, $uri);
    }
    return $this->files[$uri_hash];
  }

}
