<?php

namespace Drupal\file_mdm\Plugin\FileMetadata;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\file_mdm\Plugin\FileMetadataPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * Abstract implementation of a base File Metadata plugin.
 */
abstract class FileMetadataPluginBase extends PluginBase implements FileMetadataPluginInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The URI of the file.
   *
   * @var string
   */
  protected $uri = '';

  /**
   * The metadata of the file.
   *
   * @var mixed
   */
  protected $metadata;

  /**
   * Track if file at URI has been parsed for metadata.
   *
   * @var bool
   */
  protected $readFromFile = FALSE;

  /**
   * Track if metadata has been changed via ::setMetadata().
   *
   * @var bool
   */
  protected $hasMetadataChanged = FALSE;

  /**
   * Constructs a FileMetadataPluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type mapping service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    // @todo manage if uri is null, it means in-memory object; if changed from existing, a file is being renamed etc.
    $this->uri = $uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadata($metadata) {
    $this->metadata = $metadata;
    $this->hasMetadataChanged = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key = NULL) {
    if (!$this->metadata && !empty($this->uri) && !$this->readFromFile) {
      // Metadata has not been loaded yet. Try loading it from file if URI is
      // defined and a read attempt was not made yet.
      $this->loadMetadataFromFile();
    }
    return $this->getMetadataKey($key);
  }

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
  abstract protected function getMetadataKey($key = NULL);

  /**
   * {@inheritdoc}
   */
  public function setMetadata($key, $value) {
    return $this->setMetadataKey($key, $value);
  }

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
  abstract protected function setMetadataKey($key, $value);

  /**
   * {@inheritdoc}
   */
  public function isSaveToFileSupported() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function saveMetadataToFile() {
    // @todo error
    return FALSE;
  }

}
