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
  protected function getMetadataKey($key = NULL) {
    if (!$key) {
      return $this->metadata;
    }
    else {
      $tag = is_string($key) ? $this->stringToTag($key) : $key;
      switch ($this->stringToIfd($tag['ifds'][0])) {
/*      '0' => PelIfd::IFD0,
      '1' => PelIfd::IFD1,
      'Exif' => PelIfd::EXIF,
      'GPS' => PelIfd::GPS,
      'Interoperability' => PelIfd::INTEROPERABILITY,*/
        case PelIfd::IFD0:
          return $this->metadata->getTiff()->getIfd()->getEntry($tag['tag']);

        case PelIfd::EXIF:
          return $this->metadata->getTiff()->getIfd()->getSubIfd(PelIfd::EXIF)->getEntry($tag['tag']);

        case PelIfd::INTEROPERABILITY:
          return $this->metadata->getTiff()->getIfd()->getSubIfd(PelIfd::EXIF)->getSubIfd(PelIfd::INTEROPERABILITY)->getEntry($tag['tag']);

      }
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
      $key = is_string($key) ? $this->stringToTag($key) : $key;
      return $this->metadata->getTiff()->getIfd()->getEntry($key['tag'])->setValue($value);
      $this->hasMetadataChanged = TRUE;  // @todo only if actually changed
      return TRUE;
    }
  }

  protected function stringToTag($value) {
    return isset($this->stringToTagMap()[$value]) ? $this->stringToTagMap()[$value] : NULL;
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
      'ImageWidth' => ['tag' => 0x0100, 'ifds' => ['0', '1']],
      'ImageLength' => ['tag' => 0x0101, 'ifds' => ['0', '1']],
      'BitsPerSample' => ['tag' => 0x0102, 'ifds' => ['0', '1']],
      'Compression' => ['tag' => 0x0103, 'ifds' => ['0', '1']],
      'PhotometricInterpretation' => ['tag' => 0x0106, 'ifds' => ['0', '1']],
      'FillOrder' => ['tag' => 0x010A, 'ifds' => []],
      'DocumentName' => ['tag' => 0x010D, 'ifds' => []],
      'ImageDescription' => ['tag' => 0x010E, 'ifds' => ['0', '1']],
      'Make' => ['tag' => 0x010F, 'ifds' => ['0', '1']],
      'Model' => ['tag' => 0x0110, 'ifds' => ['0', '1']],
      'StripOffsets' => ['tag' => 0x0111, 'ifds' => ['0', '1']],
      'Orientation' => ['tag' => 0x0112, 'ifds' => ['0', '1']],
      'SamplesPerPixel' => ['tag' => 0x0115, 'ifds' => ['0', '1']],
      'RowsPerStrip' => ['tag' => 0x0116, 'ifds' => ['0', '1']],
      'StripByteCounts' => ['tag' => 0x0117, 'ifds' => ['0', '1']],
      'XResolution' => ['tag' => 0x011A, 'ifds' => ['0', '1']],
      'YResolution' => ['tag' => 0x011B, 'ifds' => ['0', '1']],
      'PlanarConfiguration' => ['tag' => 0x011C, 'ifds' => ['0', '1']],
      'ResolutionUnit' => ['tag' => 0x0128, 'ifds' => ['0', '1']],
      'TransferFunction' => ['tag' => 0x012D, 'ifds' => ['0', '1']],
      'Software' => ['tag' => 0x0131, 'ifds' => ['0', '1']],
      'DateTime' => ['tag' => 0x0132, 'ifds' => ['0', '1']],
      'Artist' => ['tag' => 0x013B, 'ifds' => ['0', '1']],
      'WhitePoint' => ['tag' => 0x013E, 'ifds' => ['0', '1']],
      'PrimaryChromaticities' => ['tag' => 0x013F, 'ifds' => ['0', '1']],
      'TransferRange' => ['tag' => 0x0156, 'ifds' => []],
      'JPEGProc' => ['tag' => 0x0200, 'ifds' => []],
      'JPEGInterchangeFormat' => ['tag' => 0x0201, 'ifds' => ['0', '1']],
      'JPEGInterchangeFormatLength' => ['tag' => 0x0202, 'ifds' => ['0', '1']],
      'YCbCrCoefficients' => ['tag' => 0x0211, 'ifds' => ['0', '1']],
      'YCbCrSubSampling' => ['tag' => 0x0212, 'ifds' => ['0', '1']],
      'YCbCrPositioning' => ['tag' => 0x0213, 'ifds' => ['0', '1']],
      'ReferenceBlackWhite' => ['tag' => 0x0214, 'ifds' => ['0', '1']],
      'CFARepeatPatternDim' => ['tag' => 0x828D, 'ifds' => []],
      'BatteryLevel' => ['tag' => 0x828F, 'ifds' => []],
      'Copyright' => ['tag' => 0x8298, 'ifds' => ['0', '1']],
      'ExposureTime' => ['tag' => 0x829A, 'ifds' => ['Exif']],
      'FNumber' => ['tag' => 0x829D, 'ifds' => ['Exif']],
      'IPTC/NAA' => ['tag' => 0x83BB, 'ifds' => []],
      'ExifIFDPointer' => ['tag' => 0x8769, 'ifds' => ['0', '1']],
      'InterColorProfile' => ['tag' => 0x8773, 'ifds' => []],
      'ExposureProgram' => ['tag' => 0x8822, 'ifds' => ['Exif']],
      'SpectralSensitivity' => ['tag' => 0x8824, 'ifds' => ['Exif']],
      'GPSInfoIFDPointer' => ['tag' => 0x8825, 'ifds' => ['0', '1']],
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
      'WindowsXPTitle' => ['tag' => 0x9C9B, 'ifds' => ['0', '1']],
      'WindowsXPComment' => ['tag' => 0x9C9C, 'ifds' => ['0', '1']],
      'WindowsXPAuthor' => ['tag' => 0x9C9D, 'ifds' => ['0', '1']],
      'WindowsXPKeywords' => ['tag' => 0x9C9E, 'ifds' => ['0', '1']],
      'WindowsXPSubject' => ['tag' => 0x9C9F, 'ifds' => ['0', '1']],
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
      'PrintIM' => ['tag' => 0xC4A5, 'ifds' => ['0', '1']],
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
      '0' => PelIfd::IFD0,
      '1' => PelIfd::IFD1,
      'Exif' => PelIfd::EXIF,
      'GPS' => PelIfd::GPS,
      'Interoperability' => PelIfd::INTEROPERABILITY,
    ];
  }

}
