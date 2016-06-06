<?php

namespace Drupal\file_mdm;

/**
 * Provides an interface for file metadata objects.
 */
interface FileMetadataInterface {
  /**
   * @todo
   */
  public function getUri();

  /**
   * @todo
   */
  public function getLocalTempPath();

  /**
   * @todo
   */
  public function setLocalTempPath($path);

  /**
   * Copies the file at URI to a local temporary file.
   *
   * @param string $temp_uri
   *   (optional) a URI to a temporary file. If NULL, a temp URI will be
   *   defined by the operation. Defaults to NULL.
   *
   * @return bool
   *   TRUE if the file was copied successfully, FALSE
   *   otherwise.
   */
  public function copyUriToTemp($temp_uri = NULL);

  /**
   * Copies the local temporary file to the destination URI.
   *
   * @return bool
   *   TRUE if the file was copied successfully, FALSE
   *   otherwise.
   */
  public function copyTempToUri();

  /**
   * @todo
   */
  public function getFileMetadataPlugin($metadata_id);

  /**
   * @todo
   */
  public function getMetadata($metadata_id, $key = NULL);

  /**
   * @todo
   */
  public function getSupportedKeys($metadata_id, $options = NULL);

  /**
   * @todo
   */
  public function setMetadata($metadata_id, $key, $value);

  /**
   * @todo
   */
  public function loadMetadata($metadata_id, $metadata);

  /**
   * @todo
   */
  public function loadMetadataFromCache($metadata_id);

  /**
   * @todo
   */
  public function saveMetadataToCache($metadata_id);

  /**
   * @todo
   */
  public function saveMetadataToFile($metadata_id);

}
