<?php

namespace Drupal\file_mdm\Plugin\file_mdm;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * FileMetadata plugin for EXIF.
 *
 * @FileMetadata(
 *   id = "exif",
 *   help = @Translation("FileMetadata plugin for EXIF."),
 * )
 */
class Exif extends FileMetadataPluginBase {

  /**
   * The MIME type guessing service.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * Constructs an Exif file metadata plugin.
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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system, MimeTypeGuesserInterface $mime_type_guesser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $file_system);
    $this->mimeTypeGuesser = $mime_type_guesser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('file.mime_type.guesser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadataFromFile() {
    $path = $this->localPath ?: $this->fileSystem->realpath($this->uri);
    $this->readFromFile = TRUE;
    if (!file_exists($path)) {
      // File does not exists.
      //$this->logger->error("@todo. Cannot read file at {$this->uri}. If it's a remote....");
      return FALSE;
    }
    if (!in_array($this->mimeTypeGuesser->guess($path), ['image/jpeg', 'image/tiff'])) {
      // File does not support EXIF.
      return FALSE;
    }
    if (!function_exists('exif_read_data')) {
      // No PHP EXIF extension enabled.
      //$this->logger->error('@todo. The PHP EXIF extension is not installed. Unable to retrieve EXIF image metadata.');
      return FALSE;
    }
    $this->metadata = @exif_read_data($path));
    $this->hasMetadataChanged = FALSE;
    return (bool) $this->metadata;
  }

  /**
   * @todo
   */
  protected function getMetadataKey($key = NULL) {
    if (!$key) {
      return $this->metadata;
    }
    else {
      return isset($this->metadata[$key]) ? $this->metadata[$key] : NULL;
    }
  }

  /**
   * @todo
   */
  protected function setMetadataKey($key, $value) {
    if (!$key) {
      // @todo error;
      return FALSE;
    }
    else {
      $this->metadata[$key] = $value;
      $this->hasMetadataChanged = TRUE;  // @todo only if actually changed
      return TRUE;
    }
  }

}
