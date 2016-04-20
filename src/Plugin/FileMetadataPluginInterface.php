<?php

namespace Drupal\file_mdm\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface defining a FileMetadata plugin.
 */
interface FileMetadataPluginInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Sets the URI of the file.
   *
   * @param string $path
   *   A URI.
   *
   * @return $this
   */
  public function setUri($uri);
  public function getUri();

  /**
   * Sets the local filesystem path to the file.
   *
   * @param string $path
   *   A filesystem path.
   *
   * @return $this
   */
  public function setLocalPath($path);
  public function getLocalPath();

  /**
   * @todo
   */
  public function getMetadata($key = NULL);
  public function setMetadata($key, $value);
  public function loadMetadata($metadata);
  public function loadMetadataFromFile();

}
