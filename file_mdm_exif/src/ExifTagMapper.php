<?php

namespace Drupal\file_mdm_exif;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;

/**
 * @todo
 */
class ExifTagMapper {  // @todo implements

  /**
   * The file_mdm logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ExifTagMapper object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The file_mdm logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveKeyToIfdAndTag($key) {
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
      if (!isset($key[0]) || !isset($key[1])) {
        throw new \RuntimeException("Invalid Exif tag specified");
      }
      // Deal with ifd.
      if (is_string($key[0])) {
        $ifd = $this->stringToIfd($key[0]);
        if ($ifd === NULL) {
          throw new \RuntimeException("Invalid Ifd '{$key[0]}' specified");
        }
      }
      else {
        throw new \RuntimeException("Invalid Ifd specified");
      }
      // Deal with tag.
      if (is_string($key[1])) {
        $tagxxx = $this->stringToTag($key[1]);
        $tag = $tagxxx['tag'];
      }
      elseif (is_int($key[1])) {
        $tag = $key[1];
      }
      else {
        throw new \RuntimeException("Invalid Exif tag specified");
      }
      return ['ifd' => $ifd, 'tag' => $tag];
    }
    throw new \RuntimeException('Invalid key passed');
  }

  public function getSupportedKeys($options = NULL) {
    $map = $this->stringToTagMap();
    if ($options) {
      $filtered_map = [];
      foreach ($map as $key => $tagxxx) {
        if (in_array($options, $tagxxx['ifds'])) {
          $filtered_map[] = $key;
        }
      }
      return $filtered_map;
    }
    else {
      return array_keys($map);
    }
  }

  protected function stringToTag($value) {
    if (isset($this->stringToTagMap()[$value])) {
      $v = $this->stringToTagMap()[$value];
      $hex = substr($v['tag'], 1, 4);
      $v['tag'] = hexdec($hex);
      return $v;
    }
    throw new \RuntimeException("No Exif tag found for '{$value}'");
  }

  protected function stringToTagMap() {
    $map = $this->configFactory->get('file_mdm_exif.settings')->get('tag_map');
    return $map;
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
      'Interop' => PelIfd::INTEROPERABILITY,
    ];
  }

}
