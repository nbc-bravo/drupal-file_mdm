<?php

namespace Drupal\file_mdm\Plugin\file_mdm;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\file_mdm\Plugin\FileMetadataInterface;
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
class Exif extends PluginBase implements FileMetadataInterface {

  /**
   * The MIME type guessing service.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The local filesystem path to the file.
   *
   * This is used to allow accessing local copies of files stored remotely, to
   * minimise remote calls and allow functions that cannot access remote stream
   * wrappers to operate locally.
   *
   * @var string
   */
  protected $localPath = '';

  /**
   * Constructs an ImagemagickToolkit object.
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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MimeTypeGuesserInterface $mime_type_guesser, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file.mime_type.guesser'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalPath() {
    return $this->localPath;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocalPath($path) {
    $this->localPath = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromUri($uri) {
    $path = $this->getLocalPath() ?: $this->fileSystem->realpath($uri);
    if (!file_exists($path)) {
      // File does not exists.
      return NULL;
    }
    $mime_type = $this->mimeTypeGuesser->guess($path);
    if (!in_array($mime_type, ['image/jpeg', 'image/tiff'])) {
      // File does not support EXIF.
      return NULL;
    }
    if (!function_exists('exif_read_data')) {
      // No PHP EXIF extension enabled.
      //$this->logger->error('@todo.');
      return NULL;
    }
    if ($exif_data = @exif_read_data($path)) {
      return $exif_data;
    }
    else {
      // No data or read error.
      return NULL;
    }
  }

}
