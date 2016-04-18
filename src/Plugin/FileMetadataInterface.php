<?php

namespace Drupal\file_mdm\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface defining a FileMetadata plugin.
 */
interface FileMetadataInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Gets the local filesystem path to the file.
   *
   * @return string
   *   A filesystem path.
   */
  public function getLocalPath();

  /**
   * Sets the local filesystem path to the file.
   *
   * @param string $path
   *   A filesystem path.
   *
   * @return $this
   */
  public function setLocalPath($path);

  /**
   * @todo
   */
  public function getFromUri($uri);

}
