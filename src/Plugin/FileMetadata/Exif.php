<?php

namespace Drupal\file_mdm\Plugin\FileMetadata;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use lsolesen\pel\PelIfd;
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
  protected function resolveKeyToIfdAndTag($key) {
    if ($key === NULL) {
      throw new \RuntimeException('No key passed');
    }
    if (is_string($key)) {
      $tagxxx = $this->stringToTag($key);
      if (!isset($tagxxx['ifds'][0])) {
        throw new \RuntimeException("No default ifd available for '{$key}'");
      }
      return ['ifd' => $this->stringToIfd($tagxxx['ifds'][0]), 'tag' => $tagxxx['tag']];
    }
    if (is_array($key)) {
      // Deal with tag.
      if (is_string($key[0])) {
        $tagxxx = $this->stringToTag($key[0]);
        $tag = $tagxxx['tag'];
      }
      elseif (is_int($key[0])) {
        $tag = $key[0];
      }
      else {
        throw new \RuntimeException("Invalid Exif tag specified");
      }
      // Deal with ifd.
      if (is_string($key[0]) && !isset($key[1])) {
        $tagxxx = $this->stringToTag($key[0]);
        if (!isset($tagxxx['ifds'][0])) {
          throw new \RuntimeException("No default ifd available for '{$key[0]}'");
        }
        $ifd = $this->stringToIfd($tagxxx['ifds'][0]);
      }
      elseif (is_string($key[1])) {
        $ifd = $this->stringToIfd($key[1]);
        if ($ifd === NULL) {
          throw new \RuntimeException("Invalid Ifd '{$key[1]}' specified");
        }
      }
      else {
        throw new \RuntimeException("Invalid Ifd specified");
      }
      return ['ifd' => $ifd, 'tag' => $tag];
    }
    throw new \RuntimeException('Invalid key passed');
  }

  /**
   * {@inheritdoc}
   */
  protected function getMetadataKey($key = NULL) {
    if (!$key) {
      return $this->metadata;
    }
    else {
      $ifd_tag = $this->resolveKeyToIfdAndTag($key);
      $ifd = $this->metadata->getTiff()->getIfd();
      if ($ifd === NULL) {
        return NULL;
      }
/* @todo
      'IFD1' => PelIfd::IFD1,
      'GPS' => PelIfd::GPS,
*/
      switch ($ifd_tag['ifd']) {
        case PelIfd::IFD0:
          return $ifd->getEntry($ifd_tag['tag']);

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

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setMetadataKey($key, $value) {
    // @todo
  }

  protected function stringToTag($value) {
    if (isset($this->stringToTagMap()[$value])) {
      return $this->stringToTagMap()[$value];
    }
    throw new \RuntimeException("No Exif tag found for '{$value}'");
  }

  protected function stringToTagMap() {
    return [
      // Interoperability tags.
      'InteroperabilityIndex' => ['tag' => 0x0001, 'ifds' => ['Interoperability']],
      'InteroperabilityVersion' => ['tag' => 0x0002, 'ifds' => ['Interoperability']],
      'RelatedImageFileFormat' => ['tag' => 0x1000, 'ifds' => ['Interoperability']],
      'RelatedImageWidth' => ['tag' => 0x1001, 'ifds' => ['Interoperability']],
      'RelatedImageLength' => ['tag' => 0x1002, 'ifds' => ['Interoperability']],
      // EXIF tags.
      'ImageWidth' => ['tag' => 0x0100, 'ifds' => ['IFD0', 'IFD1']],
      'ImageLength' => ['tag' => 0x0101, 'ifds' => ['IFD0', 'IFD1']],
      'BitsPerSample' => ['tag' => 0x0102, 'ifds' => ['IFD0', 'IFD1']],
      'Compression' => ['tag' => 0x0103, 'ifds' => ['IFD0', 'IFD1']],
      'PhotometricInterpretation' => ['tag' => 0x0106, 'ifds' => ['IFD0', 'IFD1']],
      'FillOrder' => ['tag' => 0x010A, 'ifds' => []],
      'DocumentName' => ['tag' => 0x010D, 'ifds' => []],
      'ImageDescription' => ['tag' => 0x010E, 'ifds' => ['IFD0', 'IFD1']],
      'Make' => ['tag' => 0x010F, 'ifds' => ['IFD0', 'IFD1']],
      'Model' => ['tag' => 0x0110, 'ifds' => ['IFD0', 'IFD1']],
      'StripOffsets' => ['tag' => 0x0111, 'ifds' => ['IFD0', 'IFD1']],
      'Orientation' => ['tag' => 0x0112, 'ifds' => ['IFD0', 'IFD1']],
      'SamplesPerPixel' => ['tag' => 0x0115, 'ifds' => ['IFD0', 'IFD1']],
      'RowsPerStrip' => ['tag' => 0x0116, 'ifds' => ['IFD0', 'IFD1']],
      'StripByteCounts' => ['tag' => 0x0117, 'ifds' => ['IFD0', 'IFD1']],
      'XResolution' => ['tag' => 0x011A, 'ifds' => ['IFD0', 'IFD1']],
      'YResolution' => ['tag' => 0x011B, 'ifds' => ['IFD0', 'IFD1']],
      'PlanarConfiguration' => ['tag' => 0x011C, 'ifds' => ['IFD0', 'IFD1']],
      'ResolutionUnit' => ['tag' => 0x0128, 'ifds' => ['IFD0', 'IFD1']],
      'TransferFunction' => ['tag' => 0x012D, 'ifds' => ['IFD0', 'IFD1']],
      'Software' => ['tag' => 0x0131, 'ifds' => ['IFD0', 'IFD1']],
      'DateTime' => ['tag' => 0x0132, 'ifds' => ['IFD0', 'IFD1']],
      'Artist' => ['tag' => 0x013B, 'ifds' => ['IFD0', 'IFD1']],
      'WhitePoint' => ['tag' => 0x013E, 'ifds' => ['IFD0', 'IFD1']],
      'PrimaryChromaticities' => ['tag' => 0x013F, 'ifds' => ['IFD0', 'IFD1']],
      'TransferRange' => ['tag' => 0x0156, 'ifds' => []],
      'JPEGProc' => ['tag' => 0x0200, 'ifds' => []],
      'JPEGInterchangeFormat' => ['tag' => 0x0201, 'ifds' => ['IFD0', 'IFD1']],
      'JPEGInterchangeFormatLength' => ['tag' => 0x0202, 'ifds' => ['IFD0', 'IFD1']],
      'YCbCrCoefficients' => ['tag' => 0x0211, 'ifds' => ['IFD0', 'IFD1']],
      'YCbCrSubSampling' => ['tag' => 0x0212, 'ifds' => ['IFD0', 'IFD1']],
      'YCbCrPositioning' => ['tag' => 0x0213, 'ifds' => ['IFD0', 'IFD1']],
      'ReferenceBlackWhite' => ['tag' => 0x0214, 'ifds' => ['IFD0', 'IFD1']],
      'CFARepeatPatternDim' => ['tag' => 0x828D, 'ifds' => []],
      'BatteryLevel' => ['tag' => 0x828F, 'ifds' => []],
      'Copyright' => ['tag' => 0x8298, 'ifds' => ['IFD0', 'IFD1']],
      'ExposureTime' => ['tag' => 0x829A, 'ifds' => ['Exif']],
      'FNumber' => ['tag' => 0x829D, 'ifds' => ['Exif']],
      'IPTC/NAA' => ['tag' => 0x83BB, 'ifds' => []],
      'ExifIFDPointer' => ['tag' => 0x8769, 'ifds' => ['IFD0', 'IFD1']],
      'InterColorProfile' => ['tag' => 0x8773, 'ifds' => []],
      'ExposureProgram' => ['tag' => 0x8822, 'ifds' => ['Exif']],
      'SpectralSensitivity' => ['tag' => 0x8824, 'ifds' => ['Exif']],
      'GPSInfoIFDPointer' => ['tag' => 0x8825, 'ifds' => ['IFD0', 'IFD1']],
      'ISOSpeedRatings' => ['tag' => 0x8827, 'ifds' => ['Exif']],
      'OECF' => ['tag' => 0x8828, 'ifds' => ['Exif']],
      'ExifVersion' => ['tag' => 0x9000, 'ifds' => ['Exif']],
      'DateTimeOriginal' => ['tag' => 0x9003, 'ifds' => ['Exif']],
      'DateTimeDigitized' => ['tag' => 0x9004, 'ifds' => ['Exif']],
      'ComponentsConfiguration' => ['tag' => 0x9101, 'ifds' => ['Exif']],
      'CompressedBitsPerPixel' => ['tag' => 0x9102, 'ifds' => ['Exif']],
      'ShutterSpeedValue' => ['tag' => 0x9201, 'ifds' => ['Exif']],
      'ApertureValue' => ['tag' => 0x9202, 'ifds' => ['Exif']],
      'BrightnessValue' => ['tag' => 0x9203, 'ifds' => ['Exif']],
      'ExposureBiasValue' => ['tag' => 0x9204, 'ifds' => ['Exif']],
      'MaxApertureValue' => ['tag' => 0x9205, 'ifds' => ['Exif']],
      'SubjectDistance' => ['tag' => 0x9206, 'ifds' => ['Exif']],
      'MeteringMode' => ['tag' => 0x9207, 'ifds' => ['Exif']],
      'LightSource' => ['tag' => 0x9208, 'ifds' => ['Exif']],
      'Flash' => ['tag' => 0x9209, 'ifds' => ['Exif']],
      'FocalLength' => ['tag' => 0x920A, 'ifds' => ['Exif']],
      'SubjectArea' => ['tag' => 0x9214, 'ifds' => []],
      'MakerNote' => ['tag' => 0x927C, 'ifds' => ['Exif']],
      'UserComment' => ['tag' => 0x9286, 'ifds' => ['Exif']],
      'SubSecTime' => ['tag' => 0x9290, 'ifds' => ['Exif']],
      'SubSecTimeOriginal' => ['tag' => 0x9291, 'ifds' => ['Exif']],
      'SubSecTimeDigitized' => ['tag' => 0x9292, 'ifds' => ['Exif']],
      'WindowsXPTitle' => ['tag' => 0x9C9B, 'ifds' => ['IFD0', 'IFD1']],
      'WindowsXPComment' => ['tag' => 0x9C9C, 'ifds' => ['IFD0', 'IFD1']],
      'WindowsXPAuthor' => ['tag' => 0x9C9D, 'ifds' => ['IFD0', 'IFD1']],
      'WindowsXPKeywords' => ['tag' => 0x9C9E, 'ifds' => ['IFD0', 'IFD1']],
      'WindowsXPSubject' => ['tag' => 0x9C9F, 'ifds' => ['IFD0', 'IFD1']],
      'FlashPixVersion' => ['tag' => 0xA000, 'ifds' => ['Exif']],
      'ColorSpace' => ['tag' => 0xA001, 'ifds' => ['Exif']],
      'PixelXDimension' => ['tag' => 0xA002, 'ifds' => ['Exif']],
      'PixelYDimension' => ['tag' => 0xA003, 'ifds' => ['Exif']],
      'RelatedSoundFile' => ['tag' => 0xA004, 'ifds' => ['Exif']],
      'InteroperabilityIFDPointer' => ['tag' => 0xA005, 'ifds' => ['Exif']],
      'FlashEnergy' => ['tag' => 0xA20B, 'ifds' => ['Exif']],
      'SpatialFrequencyResponse' => ['tag' => 0xA20C, 'ifds' => ['Exif']],
      'FocalPlaneXResolution' => ['tag' => 0xA20E, 'ifds' => ['Exif']],
      'FocalPlaneYResolution' => ['tag' => 0xA20F, 'ifds' => ['Exif']],
      'FocalPlaneResolutionUnit' => ['tag' => 0xA210, 'ifds' => ['Exif']],
      'SubjectLocation' => ['tag' => 0xA214, 'ifds' => ['Exif']],
      'ExposureIndex' => ['tag' => 0xA215, 'ifds' => ['Exif']],
      'SensingMethod' => ['tag' => 0xA217, 'ifds' => ['Exif']],
      'FileSource' => ['tag' => 0xA300, 'ifds' => ['Exif']],
      'SceneType' => ['tag' => 0xA301, 'ifds' => ['Exif']],
      'CFAPattern' => ['tag' => 0xA302, 'ifds' => ['Exif']],
      'CustomRendered' => ['tag' => 0xA401, 'ifds' => ['Exif']],
      'ExposureMode' => ['tag' => 0xA402, 'ifds' => ['Exif']],
      'WhiteBalance' => ['tag' => 0xA403, 'ifds' => ['Exif']],
      'DigitalZoomRatio' => ['tag' => 0xA404, 'ifds' => ['Exif']],
      'FocalLengthIn35mmFilm' => ['tag' => 0xA405, 'ifds' => ['Exif']],
      'SceneCaptureType' => ['tag' => 0xA406, 'ifds' => ['Exif']],
      'GainControl' => ['tag' => 0xA407, 'ifds' => ['Exif']],
      'Contrast' => ['tag' => 0xA408, 'ifds' => ['Exif']],
      'Saturation' => ['tag' => 0xA409, 'ifds' => ['Exif']],
      'Sharpness' => ['tag' => 0xA40A, 'ifds' => ['Exif']],
      'DeviceSettingDescription' => ['tag' => 0xA40B, 'ifds' => ['Exif']],
      'SubjectDistanceRange' => ['tag' => 0xA40C, 'ifds' => ['Exif']],
      'ImageUniqueID' => ['tag' => 0xA420, 'ifds' => ['Exif']],
      'Gamma' => ['tag' => 0xA500, 'ifds' => ['Exif']],
      'PrintIM' => ['tag' => 0xC4A5, 'ifds' => ['IFD0', 'IFD1']],
      // GPS tags.
      'GPSVersionID' => ['tag' => 0x0000, 'ifds' => ['GPS']],
      'GPSLatitudeRef' => ['tag' => 0x0001, 'ifds' => ['GPS']],
      'GPSLatitude' => ['tag' => 0x0002, 'ifds' => ['GPS']],
      'GPSLongitudeRef' => ['tag' => 0x0003, 'ifds' => ['GPS']],
      'GPSLongitude' => ['tag' => 0x0004, 'ifds' => ['GPS']],
      'GPSAltitudeRef' => ['tag' => 0x0005, 'ifds' => ['GPS']],
      'GPSAltitude' => ['tag' => 0x0006, 'ifds' => ['GPS']],
      'GPSTimeStamp' => ['tag' => 0x0007, 'ifds' => ['GPS']],
      'GPSSatellites' => ['tag' => 0x0008, 'ifds' => ['GPS']],
      'GPSStatus' => ['tag' => 0x0009, 'ifds' => ['GPS']],
      'GPSMeasureMode' => ['tag' => 0x000A, 'ifds' => ['GPS']],
      'GPSDOP' => ['tag' => 0x000B, 'ifds' => ['GPS']],
      'GPSSpeedRef' => ['tag' => 0x000C, 'ifds' => ['GPS']],
      'GPSSpeed' => ['tag' => 0x000D, 'ifds' => ['GPS']],
      'GPSTrackRef' => ['tag' => 0x000E, 'ifds' => ['GPS']],
      'GPSTrack' => ['tag' => 0x000F, 'ifds' => ['GPS']],
      'GPSImgDirectionRef' => ['tag' => 0x0010, 'ifds' => ['GPS']],
      'GPSImgDirection' => ['tag' => 0x0011, 'ifds' => ['GPS']],
      'GPSMapDatum' => ['tag' => 0x0012, 'ifds' => ['GPS']],
      'GPSDestLatitudeRef' => ['tag' => 0x0013, 'ifds' => ['GPS']],
      'GPSDestLatitude' => ['tag' => 0x0014, 'ifds' => ['GPS']],
      'GPSDestLongitudeRef' => ['tag' => 0x0015, 'ifds' => ['GPS']],
      'GPSDestLongitude' => ['tag' => 0x0016, 'ifds' => ['GPS']],
      'GPSDestBearingRef' => ['tag' => 0x0017, 'ifds' => ['GPS']],
      'GPSDestBearing' => ['tag' => 0x0018, 'ifds' => ['GPS']],
      'GPSDestDistanceRef' => ['tag' => 0x0019, 'ifds' => ['GPS']],
      'GPSDestDistance' => ['tag' => 0x001A, 'ifds' => ['GPS']],
      'GPSProcessingMethod' => ['tag' => 0x001B, 'ifds' => ['GPS']],
      'GPSAreaInformation' => ['tag' => 0x001C, 'ifds' => ['GPS']],
      'GPSDateStamp' => ['tag' => 0x001D, 'ifds' => ['GPS']],
      'GPSDifferential' => ['tag' => 0x001E, 'ifds' => ['GPS']],
    ];
  }

  protected function stringToIfd($value) {
    return isset($this->stringToIfdMap()[$value]) ? $this->stringToIfdMap()[$value] : NULL;
  }

  protected function stringToIfdMap() {
    return [
      'IFD0' => PelIfd::IFD0,
      'Main' => PelIfd::IFD0,
      'IFD1' => PelIfd::IFD1,
      'Thumbnail' => PelIfd::IFD1,
      'Exif' => PelIfd::EXIF,
      'GPS' => PelIfd::GPS,
      'Interoperability' => PelIfd::INTEROPERABILITY,
    ];
  }

}
