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
  public function manage($plugin_id, $uri) {
    $plugin = $this->pluginManager->createInstance($plugin_id);
    return $plugin->getFromUri($uri);
  }

}
