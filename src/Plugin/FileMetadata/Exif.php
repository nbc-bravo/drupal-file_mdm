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
      $key = is_string($key) ? $this->stringToTag($key) : $key;
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
      $key = is_string($key) ? $this->stringToTag($key) : $key;
      $this->metadata->getTiff()->getIfd()->getEntry($key)->setValue($value);
      $this->hasMetadataChanged = TRUE;  // @todo only if actually changed
      return TRUE;
    }
  }

  protected function stringToTag($value) {
    static $map = [
      // EXIF tags.
      'InteroperabilityIndex' => 0x0001,
      'InteroperabilityVersion' => 0x0002,
      'ImageWidth' => 0x0100,
      'ImageLength' => 0x0101,
      'BitsPerSample' => 0x0102,
      'Compression' => 0x0103,
      'PhotometricInterpretation' => 0x0106,
      'FillOrder' => 0x010A,
      'DocumentName' => 0x010D,
      'ImageDescription' => 0x010E,
      'Make' => 0x010F,
      'Model' => 0x0110,
      'StripOffsets' => 0x0111,
      'Orientation' => 0x0112,
      'SamplesPerPixel' => 0x0115,
      'RowsPerStrip' => 0x0116,
      'StripByteCounts' => 0x0117,
      'XResolution' => 0x011A,
      'YResolution' => 0x011B,
      'PlanarConfiguration' => 0x011C,
      'ResolutionUnit' => 0x0128,
      'TransferFunction' => 0x012D,
      'Software' => 0x0131,
      'DateTime' => 0x0132,
      'Artist' => 0x013B,
      'WhitePoint' => 0x013E,
      'PrimaryChromaticities' => 0x013F,
      'TransferRange' => 0x0156,
      'JPEGProc' => 0x0200,
      'JPEGInterchangeFormat' => 0x0201,
      'JPEGInterchangeFormatLength' => 0x0202,
      'YCbCrCoefficients' => 0x0211,
      'YCbCrSubSampling' => 0x0212,
      'YCbCrPositioning' => 0x0213,
      'ReferenceBlackWhite' => 0x0214,
      'RelatedImageFileFormat' => 0x1000,
      'RelatedImageWidth' => 0x1001,
      'RelatedImageLength' => 0x1002,
      'CFARepeatPatternDim' => 0x828D,
      'BatteryLevel' => 0x828F,
      'Copyright' => 0x8298,
      'ExposureTime' => 0x829A,
      'FNumber' => 0x829D,
      'IPTC/NAA' => 0x83BB,
      'ExifIFDPointer' => 0x8769,
      'InterColorProfile' => 0x8773,
      'ExposureProgram' => 0x8822,
      'SpectralSensitivity' => 0x8824,
      'GPSInfoIFDPointer' => 0x8825,
      'ISOSpeedRatings' => 0x8827,
      'OECF' => 0x8828,
      'ExifVersion' => 0x9000,
      'DateTimeOriginal' => 0x9003,
      'DateTimeDigitized' => 0x9004,
      'ComponentsConfiguration' => 0x9101,
      'CompressedBitsPerPixel' => 0x9102,
      'ShutterSpeedValue' => 0x9201,
      'ApertureValue' => 0x9202,
      'BrightnessValue' => 0x9203,
      'ExposureBiasValue' => 0x9204,
      'MaxApertureValue' => 0x9205,
      'SubjectDistance' => 0x9206,
      'MeteringMode' => 0x9207,
      'LightSource' => 0x9208,
      'Flash' => 0x9209,
      'FocalLength' => 0x920A,
      'SubjectArea' => 0x9214,
      'MakerNote' => 0x927C,
      'UserComment' => 0x9286,
      'SubSecTime' => 0x9290,
      'SubSecTimeOriginal' => 0x9291,
      'SubSecTimeDigitized' => 0x9292,
      'WindowsXPTitle' => 0x9C9B,
      'WindowsXPComment' => 0x9C9C,
      'WindowsXPAuthor' => 0x9C9D,
      'WindowsXPKeywords' => 0x9C9E,
      'WindowsXPSubject' => 0x9C9F,
      'FlashPixVersion' => 0xA000,
      'ColorSpace' => 0xA001,
      'PixelXDimension' => 0xA002,
      'PixelYDimension' => 0xA003,
      'RelatedSoundFile' => 0xA004,
      'InteroperabilityIFDPointer' => 0xA005,
      'FlashEnergy' => 0xA20B,
      'SpatialFrequencyResponse' => 0xA20C,
      'FocalPlaneXResolution' => 0xA20E,
      'FocalPlaneYResolution' => 0xA20F,
      'FocalPlaneResolutionUnit' => 0xA210,
      'SubjectLocation' => 0xA214,
      'ExposureIndex' => 0xA215,
      'SensingMethod' => 0xA217,
      'FileSource' => 0xA300,
      'SceneType' => 0xA301,
      'CFAPattern' => 0xA302,
      'CustomRendered' => 0xA401,
      'ExposureMode' => 0xA402,
      'WhiteBalance' => 0xA403,
      'DigitalZoomRatio' => 0xA404,
      'FocalLengthIn35mmFilm' => 0xA405,
      'SceneCaptureType' => 0xA406,
      'GainControl' => 0xA407,
      'Contrast' => 0xA408,
      'Saturation' => 0xA409,
      'Sharpness' => 0xA40A,
      'DeviceSettingDescription' => 0xA40B,
      'SubjectDistanceRange' => 0xA40C,
      'ImageUniqueID' => 0xA420,
      'Gamma' => 0xA500,
      'PrintIM' => 0xC4A5,
      // GPS tags.
      'GPSVersionID' => 0x0000,
      'GPSLatitudeRef' => 0x0001,
      'GPSLatitude' => 0x0002,
      'GPSLongitudeRef' => 0x0003,
      'GPSLongitude' => 0x0004,
      'GPSAltitudeRef' => 0x0005,
      'GPSAltitude' => 0x0006,
      'GPSTimeStamp' => 0x0007,
      'GPSSatellites' => 0x0008,
      'GPSStatus' => 0x0009,
      'GPSMeasureMode' => 0x000A,
      'GPSDOP' => 0x000B,
      'GPSSpeedRef' => 0x000C,
      'GPSSpeed' => 0x000D,
      'GPSTrackRef' => 0x000E,
      'GPSTrack' => 0x000F,
      'GPSImgDirectionRef' => 0x0010,
      'GPSImgDirection' => 0x0011,
      'GPSMapDatum' => 0x0012,
      'GPSDestLatitudeRef' => 0x0013,
      'GPSDestLatitude' => 0x0014,
      'GPSDestLongitudeRef' => 0x0015,
      'GPSDestLongitude' => 0x0016,
      'GPSDestBearingRef' => 0x0017,
      'GPSDestBearing' => 0x0018,
      'GPSDestDistanceRef' => 0x0019,
      'GPSDestDistance' => 0x001A,
      'GPSProcessingMethod' => 0x001B,
      'GPSAreaInformation' => 0x001C,
      'GPSDateStamp' => 0x001D,
      'GPSDifferential' => 0x001E,
    ];
    return isset($map[$value]) ? $map[$value] : NULL;
  }

}
