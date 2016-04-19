<?php

namespace Drupal\file_mdm\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface defining a FileMetadata plugin.
 */
interface FileMetadataInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Sets the URI of the file.
   *
   * @param string $path
   *   A URI.
   *
   * @return $this
   */
  public function setUri($uri);

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
  public function getMetadata();
  public function getMetadataFromFile();

}
