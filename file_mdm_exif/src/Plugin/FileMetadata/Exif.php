<?php

namespace Drupal\file_mdm_exif\Plugin\FileMetadata;

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
   * The EXIF tag mapping service.
   *
   * @var \Drupal\file_mdm_exif\ExifTagMapperInterface
   */
  protected $tagMapper;

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
   * @param \Drupal\file_mdm_exif\ExifTagMapperInterface $tag_mapper
   *   The EXIF tag mapping service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MimeTypeGuesserInterface $mime_type_guesser, ExifTagMapperInterface $tag_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('file.mime_type.guesser'),
      $container->get('file_mdm_exif.tag_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadataFromFile() {
    if (!file_exists($this->getUri())) {
      // File does not exists.
      throw new \RuntimeException("Cannot read file at '{$this->getUri()}'");
    }
    $this->readFromFile = TRUE;
    switch ($this->mimeTypeGuesser->guess($this->getUri())) {
      case 'image/jpeg':
        $jpeg = new PelJpeg($this->getUri());
        if ($jpeg !== NULL && ($exif = $jpeg->getExif())) {
          if (($tiff = $exif->getTiff()) !== NULL) {
            $this->metadata = $tiff;
          }
        }
        break;

      case 'image/tiff':
        $tiff = new PelTiff($this->getUri());
        if ($tiff !== NULL) {
          $this->metadata = $tiff;
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
  protected function getMetadataKey($key = NULL) {
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
  protected function setMetadataKey($key, $value) {
    // @todo
  }

  public function getSupportedKeys($options = NULL) {
    return $this->tagMapper->getSupportedKeys($options);
  }

}
