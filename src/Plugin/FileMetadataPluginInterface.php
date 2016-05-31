<?php

namespace Drupal\file_mdm\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface defining a FileMetadata plugin.
 */
interface FileMetadataPluginInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, PluginFormInterface {

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
   * Gets the URI of the file.
   *
   * @return string
   *   The URI of the file.
   */
  public function getUri();

  /**
   * Returns a list of metadata keys supported by the plugin.
   *
   * @param mixed $options
   *   (optional) Allows specifying additional options to control the list of
   *   metadata keys returned.
   *
   * @return array
   *   A simple array of metadata keys supported.
   */
  public function getSupportedKeys($options = NULL);

  /**
   * Gets a metadata element.
   *
   * @param mixed|NULL $key
   *   A key to determine the metadata element to be returned. If NULL, the
   *   entire metadata will be returned.
   *
   * @return mixed
   *   The value of the element specified by $key. If $key is NULL, the entire
   *   metadata.
   */
  public function getMetadata($key = NULL);

  /**
   * Sets a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be changed.
   * @param mixed $value
   *   The value to change the metadata element to.
   *
   * @return bool
   *   TRUE if metadata was changed successfully, FALSE otherwise.
   */
  public function setMetadata($key, $value);

  /**
   * Loads file metadata from an in-memory object/array.
   *
   * @param mixed $metadata
   *   The file metadata associated to the file at URI.
   *
   * @return bool
   *   TRUE if metadata was loaded successfully, FALSE otherwise.
   */
  public function loadMetadata($metadata);

  /**
   * Loads file metadata from the file at URI/local path.
   *
   * @throws \RuntimeException
   *   In case there were significant errors reading from file.
   *
   * @return bool
   *   TRUE if metadata was loaded successfully, FALSE otherwise.
   */
  public function loadMetadataFromFile();

  /**
   * Determines if plugin is capable of writing metadata to files.
   *
   * @return bool
   *   TRUE if plugin can save data to files, FALSE otherwise.
   */
  public function isSaveToFileSupported();

  /**
   * Saves metadata to file at URI.
   *
   * @return bool
   *   TRUE if metadata was saved successfully, FALSE otherwise.
   */
  public function saveMetadataToFile();

}
