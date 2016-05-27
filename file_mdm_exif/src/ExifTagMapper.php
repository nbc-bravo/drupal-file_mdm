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

  protected $stringToIfdMap;
  protected $stringToTagMap;
  protected $supportedKeysMap;
  protected $supportedIfdsMap;

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
      $tag = $this->stringToTag($key);
      if (!$tag[0]) {
        throw new \RuntimeException("No default ifd available for '{$key}'");
      }
      return ['ifd' => $tag[0], 'tag' => $tag[1]];
    }
    if (is_array($key)) {
      if (!isset($key[0]) || !isset($key[1])) {
        throw new \RuntimeException("Invalid Exif tag specified");
      }
      // Deal with ifd.
      if (is_string($key[0])) {
        $ifd = $this->stringToIfd($key[0]);
      }
      elseif (is_int($key[0])) {
        $ifd = $key[0];
      }
      else {
        throw new \RuntimeException("Invalid Ifd specified");
      }
      // Deal with tag.
      if (is_string($key[1])) {
        $tag = $this->stringToTag($key[1])[1];
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
    if ($options !== NULL && !is_array($options)) {
      throw new \RuntimeException('Invalid options passed to getSupportedKeys');
    }
    if (isset($options['ifds'])) {
      return $this->getSupportedIfdsMap();
    }
    elseif (isset($options['ifd'])) {
      return array_filter($this->getSupportedKeysMap(), function ($a) use ($options) { return strtolower($options['ifd']) === strtolower($a[0]); });
    }
    else {
      return $this->getSupportedKeysMap();
    }
  }

  protected function getSupportedIfdsMap() {
    if (!$this->supportedIfdsMap) {
      $config_map = $this->configFactory->get('file_mdm_exif.settings')->get('ifd_map');
      $this->supportedIfdsMap = [];
      foreach ($config_map as $key => $value) {
        $this->supportedIfdsMap[] = [$key, $value['type']];
      }
    }
    return $this->supportedIfdsMap;
  }

  protected function getSupportedKeysMap() {
    if (!$this->supportedKeysMap) {
      $config_map = $this->configFactory->get('file_mdm_exif.settings')->get('tag_map');
      $this->supportedKeysMap = [];
      foreach ($config_map as $key => $value) {
        foreach ($value['ifds'] as $ifd) {
          $this->supportedKeysMap[] = [$ifd, $key];
        }
      }
    }
    return $this->supportedKeysMap;
  }

  protected function stringToTag($value) {
    $v = strtolower($value);
    if (isset($this->getStringToTagMap()[$v])) {
      return $this->getStringToTagMap()[$v];
    }
    throw new \RuntimeException("No Exif tag found for '{$value}'");
  }

  protected function getStringToTagMap() {
    if (!$this->stringToTagMap) {
      $config_map = $this->configFactory->get('file_mdm_exif.settings')->get('tag_map');
      $this->stringToTagMap = [];
      foreach ($config_map as $key => $value) {
        $k = strtolower($key);
        $hex = substr($value['tag'], 1, 4);
        $this->stringToTagMap[$k] = [isset($value['ifds'][0]) ? $value['ifds'][0] : NULL, hexdec($hex)];
      }
    }
    return $this->stringToTagMap;
  }

  protected function stringToIfd($value) {
    $v = strtolower($value);
    if (isset($this->getStringToIfdMap()[$v])) {
      return $this->getStringToIfdMap()[$v];
    }
    throw new \RuntimeException("Invalid Ifd '{$value}' specified");
  }

  protected function getStringToIfdMap() {
    if (!$this->stringToIfdMap) {
      $config_map = $this->configFactory->get('file_mdm_exif.settings')->get('ifd_map');
      $this->stringToIfdMap = [];
      foreach ($config_map as $key => $value) {
        foreach ($value['aliases'] as $alias) {
          $k = strtolower($alias);
          $this->stringToIfdMap[$k] = $value['type'];
        }
      }
    }
    return $this->stringToIfdMap;
  }

}
