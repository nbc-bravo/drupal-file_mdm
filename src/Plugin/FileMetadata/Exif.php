<?php

namespace Drupal\file_mdm\Plugin\FileMetadata;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type mapping service.
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
    $path = $this->localPath ?: $this->uri;
    if (!file_exists($path)) {
      // File does not exists.
      throw new \RuntimeException("Cannot read file at '{$this->uri}'. Local path '{$path}'");
    }
    $this->readFromFile = TRUE;
    if (!in_array($this->mimeTypeGuesser->guess($path), ['image/jpeg', 'image/tiff'])) {
      // File does not support EXIF.
      return FALSE;
    }
    $jpeg = new PelJpeg($path);
    $this->metadata = $jpeg->getExif();
    $this->hasMetadataChanged = FALSE;
    return (bool) $this->metadata;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMetadataKey($key = NULL) {
    if (!$key) {
      return $this->metadata;
    }
    else {
      return $this->metadata->getTiff()->getIfd()->getEntry($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setMetadataKey($key, $value) {
    if (!$key) {
      // @todo error;
      return FALSE;
    }
    else {
      $this->metadata->getTiff()->getIfd()->getEntry($key)->setValue($value);
      $this->hasMetadataChanged = TRUE;  // @todo only if actually changed
      return TRUE;
    }
  }

}
