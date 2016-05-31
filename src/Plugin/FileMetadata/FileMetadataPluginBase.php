<?php

namespace Drupal\file_mdm\Plugin\FileMetadata;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\file_mdm\FileMetadataException;
use Drupal\file_mdm\Plugin\FileMetadataPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

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
  protected $metadata;

  /**
   * Track if file at URI has been parsed for metadata.
   *
   * @var bool
   */
  protected $readFromFile = FALSE;

  /**
   * Track if metadata has been changed via ::setMetadata().
   *
   * @var bool
   */
  protected $hasMetadataChanged = FALSE;

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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {  }

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
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getSupportedKeys($options = NULL);

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key = NULL) {
    if (!$this->metadata && $this->hash) {
      // Metadata has not been loaded yet. Try loading it from cache first.
      $this->loadMetadataFromCache();
    }
    if (!$this->metadata && $this->uri && !$this->readFromFile) {
      // Metadata has not been loaded yet. Try loading it from file if URI is
      // defined and a read attempt was not made yet.
      $this->loadMetadataFromFile();
    }
    return $this->getMetadataKey($key);
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
  abstract protected function getMetadataKey($key = NULL);

  /**
   * {@inheritdoc}
   */
  public function setMetadata($key, $value) {
    return $this->setMetadataKey($key, $value);
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
  abstract protected function setMetadataKey($key, $value);

  public function loadMetadataFromCache() {
    $plugin_id = $this->getPluginId();
    if ($cache = $this->cache->get("hash:{$plugin_id}:{$this->hash}")) {
      $this->loadMetadata($cache->data);  // @need to track that metadata was coming from cache to avoid re-writeing without need
    }
    else {
      $this->loadMetadata(NULL);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveMetadataToCache() {
    $plugin_id = $this->getPluginId();
    $this->cache->set("hash:{$plugin_id}:{$this->hash}", $this->metadata, Cache::PERMANENT);  // @todo tags
    return $this;
  }

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
    // @todo error
    return FALSE;
  }

}
