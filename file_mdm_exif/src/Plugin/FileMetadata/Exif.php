<?php

namespace Drupal\file_mdm_exif\Plugin\FileMetadata;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file_mdm\FileMetadataException;
use Drupal\file_mdm\Plugin\FileMetadata\FileMetadataPluginBase;
use Drupal\file_mdm_exif\ExifTagMapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTiff;

/**
 * FileMetadata plugin for EXIF.
 *
 * @FileMetadata(
 *   id = "exif",
 *   title = @Translation("EXIF image information"),
 *   help = @Translation("File metadata plugin for EXIF, using the PHP Exif Library (PEL)."),
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
   * The EXIF tag mapping service.
   *
   * @var \Drupal\file_mdm_exif\ExifTagMapperInterface
   */
  protected $tagMapper;

  // @todo
  protected $file;

  /**
   * Constructs an Exif file metadata plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_service
   *   The cache service.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type mapping service.
   * @param \Drupal\file_mdm_exif\ExifTagMapperInterface $tag_mapper
   *   The EXIF tag mapping service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CacheBackendInterface $cache_service, MimeTypeGuesserInterface $mime_type_guesser, ExifTagMapperInterface $tag_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $cache_service);
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->tagMapper = $tag_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.file_mdm'),
      $container->get('file.mime_type.guesser'),
      $container->get('file_mdm_exif.tag_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('file_mdm_exif.settings');
    $form['ifd_map'] = [  // @todo
      '#type' => 'textarea',
      '#rows' => 6,
      '#default_value' => Yaml::encode($config->get('ifd_map')),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedKeys($options = NULL) {
    return $this->tagMapper->getSupportedKeys($options);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadataFromFile() {
    if (!file_exists($this->getUri())) {
      // File does not exists.
      throw new FileMetadataException("Cannot read file at '{$this->getUri()}'", $this->getPluginId(), __FUNCTION__);
    }
    $this->readFromFile = TRUE;
    switch ($this->mimeTypeGuesser->guess($this->getUri())) {
      case 'image/jpeg':
        $this->file = new PelJpeg($this->getUri());
        if ($this->file !== NULL && ($exif = $this->file->getExif())) {
          if (($tiff = $exif->getTiff()) !== NULL) {
            $this->metadata = $tiff;
          }
        }
        break;

      case 'image/tiff':
        $this->file = new PelTiff($this->getUri());
        if ($this->file !== NULL) {
          $this->metadata = $this->file;
        }
        break;

      default:
        break;

    }
    $this->hasMetadataChanged = FALSE;
    return (bool) $this->metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function doSaveMetadataToFile() {
    // @todo error
    return $this->file->saveFile($this->getUri());
  }

  /**
   * {@inheritdoc}
   */
  protected function doGetMetadata($key = NULL) {
    if (!$key) {
      return $this->metadata;
    }
    else {
      $ifd_tag = $this->tagMapper->resolveKeyToIfdAndTag($key);
      if (!$this->metadata) {
        return NULL;
      }
      $ifd = $this->metadata->getIfd();
      if ($ifd === NULL) {
        return NULL;
      }
      switch ($ifd_tag['ifd']) {
        case PelIfd::IFD0:
          return $ifd->getEntry($ifd_tag['tag']);

        case PelIfd::IFD1:
          $ifd1 = $ifd->getNextIfd();
          if (!$ifd1) {
            return NULL;
          }
          return $ifd1->getEntry($ifd_tag['tag']);

        case PelIfd::EXIF:
          $exif = $ifd->getSubIfd(PelIfd::EXIF);
          if (!$exif) {
            return NULL;
          }
          return $exif->getEntry($ifd_tag['tag']);

        case PelIfd::INTEROPERABILITY:
          $exif = $ifd->getSubIfd(PelIfd::EXIF);
          if (!$exif) {
            return NULL;
          }
          $interop = $exif->getSubIfd(PelIfd::INTEROPERABILITY);
          if (!$interop) {
            return NULL;
          }
          return $interop->getEntry($ifd_tag['tag']);

        case PelIfd::GPS:
          $gps = $ifd->getSubIfd(PelIfd::GPS);
          if (!$gps) {
            return NULL;
          }
          return $gps->getEntry($ifd_tag['tag']);

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSetMetadata($key, $value) {
    if (!$key) {
      throw new FileMetadataException("No metadata key specified for file at '{$this->getUri()}'", $this->getPluginId(), __FUNCTION__);
    }
    elseif (!$this->metadata) {
      throw new FileMetadataException("No metadata loaded for file at '{$this->getUri()}'", $this->getPluginId(), __FUNCTION__);
    }
    else {
      $entry = $this->doGetMetadata($key);
      $entry->setValue($value);
      return TRUE;
    }
  }

}
