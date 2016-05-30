<?php

namespace Drupal\file_mdm_exif;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTag;

/**
 * Provides a mapping service for EXIF ifds and tags.
 */
class ExifTagMapper {  // @todo implements

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_service
   *   The cache service.
   */
  public function __construct(LoggerInterface $logger, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_service) {
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->cache = $cache_service;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveKeyToIfdAndTag($key) {
    if ($key === NULL) {
      throw new \RuntimeException('No key passed');
    }
    if (is_string($key)) {
      if (!$tag = $this->stringToTag($key)) {
        throw new \RuntimeException("No default ifd available for '{$key}'");
      }
      return ['ifd' => $tag[0], 'tag' => $tag[1]];
    }
    if (is_array($key)) {
      if (!isset($key[0]) || !isset($key[1])) {
        throw new \RuntimeException("Invalid Exif tag specified");
      }
      // Deal with ifd.
      if (is_int($key[0])) {
        $ifd = $key[0];
      }
      elseif (is_string($key[0])) {
        $ifd = $this->stringToIfd($key[0]);
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

  /**
   * {@inheritdoc}
   */
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
      $cache_id = 'supportedIfds';
      if ($cache = $this->getCache($cache_id)) {
        $this->supportedIfdsMap = $cache->data;
      }
      else {
        $this->supportedIfdsMap = [];
        foreach ([PelIfd::IFD0, PelIfd::IFD1, PelIfd::EXIF, PelIfd::GPS, PelIfd::INTEROPERABILITY] as $type) {
          $ifd = new PelIfd($type);
          $this->supportedIfdsMap[] = [PelIfd::getTypeName($type), $type];
        }
        $this->setCache($cache_id, $this->supportedIfdsMap);
      }
    }
    return $this->supportedIfdsMap;
  }

  protected function getSupportedKeysMap() {
    if (!$this->supportedKeysMap) {
      $cache_id = 'supportedKeys';
      if ($cache = $this->getCache($cache_id)) {
        $this->supportedKeysMap = $cache->data;
      }
      else {
        $this->supportedKeysMap = [];
        foreach ($this->getSupportedIfdsMap() as $ifd) {
          $ifd_obj = new PelIfd($ifd[1]);
          $valid_tags = $ifd_obj->getValidTags();
          foreach ($valid_tags as $tag) {
            $this->supportedKeysMap[] = [$ifd[0], PelTag::getName($ifd[1], $tag)];
          }
        }
        $this->setCache($cache_id, $this->supportedKeysMap);
      }
    }
    return $this->supportedKeysMap;
  }

  protected function stringToTag($value) {
    $v = strtolower($value);
    $tag = isset($this->getStringToTagMap()[$v]) ? $this->getStringToTagMap()[$v] : NULL;
    if ($tag) {
      return $tag;
    }
    throw new \RuntimeException("No Exif tag found for '{$value}'");
  }

  protected function getStringToTagMap() {
    if (!$this->stringToTagMap) {
      $cache_id = 'stringToTag';
      if ($cache = $this->getCache($cache_id)) {
        $this->stringToTagMap = $cache->data;
      }
      else {
        foreach ($this->getSupportedIfdsMap() as $ifd) {
          $ifd_obj = new PelIfd($ifd[1]);
          $valid_tags = $ifd_obj->getValidTags();
          foreach ($valid_tags as $tag) {
            $tag_name = strtolower(PelTag::getName($ifd[1], $tag));
            if (!isset($this->stringToTagMap[$tag_name])) {
              $this->stringToTagMap[$tag_name] = [$ifd[1], $tag];
            }
          }
        }
        $this->setCache($cache_id, $this->stringToTagMap);
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
      $cache_id = 'stringToIfd';
      if ($cache = $this->getCache($cache_id)) {
        $this->stringToIfdMap = $cache->data;
      }
      else {
        $config_map = $this->configFactory->get('file_mdm_exif.settings')->get('ifd_map');
        $this->stringToIfdMap = [];
        foreach ($config_map as $key => $value) {
          foreach ($value['aliases'] as $alias) {
            $k = strtolower($alias);
            $this->stringToIfdMap[$k] = $value['type'];
          }
        }
        $this->setCache($cache_id, $this->stringToIfdMap);
      }
    }
    return $this->stringToIfdMap;
  }

  protected function getCache($id) {
    if ($cache = $this->cache->get("file_mdm_exif:{$id}")) {
      return $cache;
    }
    else {
      return NULL;
    }
  }

  protected function setCache($id, $value) {
    $config = $this->configFactory->get('file_mdm_exif.settings');
    $this->cache->set("file_mdm_exif:{$id}", $value, Cache::PERMANENT, $config->getCacheTags());
    return $this;
  }

}
