<?php

namespace Drupal\file_mdm\Plugin\FileMetadata;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\file_mdm\FileMetadataException;
use Drupal\file_mdm\FileMetadataInterface;
use Drupal\file_mdm\Plugin\FileMetadataPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract implementation of a base File Metadata plugin.
 */
abstract class FileMetadataPluginBase extends PluginBase implements FileMetadataPluginInterface {

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The URI of the file.
   *
   * @var string
   */
  protected $uri;

  /**
   * The hash used to reference the URI.
   *
   * @var string
   */
  protected $hash;

  /**
   * The metadata of the file.
   *
   * @var mixed
   */
  protected $metadata = NULL;

  /**
   * The metadata loading status.
   *
   * @var int
   */
  protected $isMetadataLoaded = FileMetadataInterface::NOT_LOADED;

  /**
   * Track if metadata has been changed via ::setMetadata().
   *
   * @var bool
   */
  protected $hasMetadataChanged = FALSE;

  /**
   * Track if file metadata on cache needs update.
   *
   * @var bool
   */
  protected $hasMetadataChangedFromCached = FALSE;

  /**
   * Constructs a FileMetadataPluginBase plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_service
   *   The cache service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, CacheBackendInterface $cache_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cache = $cache_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.file_mdm')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    if (!$uri) {
      throw new FileMetadataException('Missing $uri argument', $this->getPluginId(), __FUNCTION__);
    }
    $this->uri = $uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    if (!$hash) {
      throw new FileMetadataException('Missing $hash argument', $this->getPluginId(), __FUNCTION__);
    }
    $this->hash = $hash;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadata($metadata) {
    $this->metadata = $metadata;
    $this->hasMetadataChanged = FALSE;
    if ($this->isMetadataLoaded === FileMetadataInterface::LOADED_FROM_CACHE) {
      $this->hasMetadataChangedFromCached = TRUE;
    }
    if ($this->metadata === NULL) {
      $this->isMetadataLoaded = FileMetadataInterface::NOT_LOADED;
    }
    else {
      $this->isMetadataLoaded = FileMetadataInterface::LOADED_BY_CODE;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMetadataFromFile() {
    if (!file_exists($this->getUri())) {
      // File does not exists.
      throw new FileMetadataException("File at '{$this->getUri()}' does not exist", $this->getPluginId(), __FUNCTION__);
    }
    $this->metadata = $this->doGetMetadataFromFile();
    $this->hasMetadataChanged = FALSE;
    if ($this->isMetadataLoaded === FileMetadataInterface::LOADED_FROM_CACHE) {
      $this->hasMetadataChangedFromCached = TRUE;
    }
    if ($this->metadata === NULL) {
      $this->isMetadataLoaded = FileMetadataInterface::NOT_LOADED;
    }
    else {
      $this->isMetadataLoaded = FileMetadataInterface::LOADED_FROM_FILE;
    }
    return (bool) $this->metadata;
  }

  /**
   * Gets file metadata from the file at URI/local path.
   *
   * @return mixed
   *   The metadata retrieved from the file.
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   In case there were significant errors reading from file.
   */
  abstract protected function doGetMetadataFromFile();

  /**
   * {@inheritdoc}
   */
  public function loadMetadataFromCache() {
    $plugin_id = $this->getPluginId();
    if ($cache = $this->cache->get("hash:{$plugin_id}:{$this->hash}")) {
      $this->metadata = $cache->data;
      $this->hasMetadataChanged = FALSE;
      $this->hasMetadataChangedFromCached = FALSE;
      $this->isMetadataLoaded = FileMetadataInterface::LOADED_FROM_CACHE;
    }
    else {
      $this->metadata = NULL;
      $this->hasMetadataChanged = FALSE;
      $this->hasMetadataChangedFromCached = FALSE;
      $this->isMetadataLoaded = FileMetadataInterface::NOT_LOADED;
    }
    return (bool) $this->metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key = NULL) {
    if (!$this->uri || !$this->hash) {
      return NULL;
    }
    if ($this->metadata === NULL) {
      // Metadata has not been loaded yet. Try loading it from cache first.
      $this->loadMetadataFromCache();
    }
    if ($this->metadata === NULL && $this->isMetadataLoaded !== FileMetadataInterface::LOADED_FROM_FILE) {
      // Metadata has not been loaded yet. Try loading it from file if URI is
      // defined and a read attempt was not made yet.
      $this->loadMetadataFromFile();
    }
    return $this->doGetMetadata($key);
  }

  /**
   * Gets a metadata element.
   *
   * @param mixed|NULL $key
   *   A key to determine the metadata element to be returned. If NULL, the
   *   entire metadata will be returned.
   *
   * @return mixed
   *   The value of the element specified by $key. If $key is NULL, the entire
   *   metadata.
   */
  abstract protected function doGetMetadata($key = NULL);

  /**
   * {@inheritdoc}
   */
  public function setMetadata($key, $value) {
    if ($this->doSetMetadata($key, $value)) {
      $this->hasMetadataChanged = TRUE;
      if ($this->isMetadataLoaded === FileMetadataInterface::LOADED_FROM_CACHE) {
        $this->hasMetadataChangedFromCached = TRUE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Sets a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be changed.
   * @param mixed $value
   *   The value to change the metadata element to.
   *
   * @return bool
   *   TRUE if metadata was changed successfully, FALSE otherwise.
   */
  abstract protected function doSetMetadata($key, $value);

  /**
   * {@inheritdoc}
   */
  public function removeMetadata($key) {
    if ($this->doRemoveMetadata($key)) {
      $this->hasMetadataChanged = TRUE;
      if ($this->isMetadataLoaded === FileMetadataInterface::LOADED_FROM_CACHE) {
        $this->hasMetadataChangedFromCached = TRUE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Removes a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be removed.
   *
   * @return bool
   *   TRUE if metadata was removed successfully, FALSE otherwise.
   */
  abstract protected function doRemoveMetadata($key);

  /**
   * {@inheritdoc}
   */
  public function isSaveToFileSupported() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function saveMetadataToFile() {
    if (!$this->isSaveToFileSupported()) {
      throw new FileMetadataException('Write metadata to file is not supported', $this->getPluginId());
    }
    if ($this->metadata === NULL) {
      return FALSE;
    }
    if ($this->hasMetadataChanged) {
      return $this->doSaveMetadataToFile();
    }
    return FALSE;
  }

  /**
   * Saves metadata to file at URI.
   *
   * @return bool
   *   TRUE if metadata was saved successfully, FALSE otherwise.
   */
  protected function doSaveMetadataToFile() {
    return FALSE;
  }


  /**
   * {@inheritdoc}
   */
  public function saveMetadataToCache(array $tags = [], $expire = Cache::PERMANENT) {
    if ($this->metadata === NULL) {
      $this->getMetadata();
      if ($this->metadata === NULL) {
        return FALSE;
      }
    }
    if ($this->isMetadataLoaded !== FileMetadataInterface::LOADED_FROM_CACHE || ($this->isMetadataLoaded === FileMetadataInterface::LOADED_FROM_CACHE && $this->hasMetadataChangedFromCached)) {
      $plugin_id = $this->getPluginId();
      $this->cache->set("hash:{$plugin_id}:{$this->hash}", $this->metadata, $expire, $tags);
      $this->hasMetadataChangedFromCached = FALSE;
      return TRUE;
    }
    return FALSE;
  }

}
