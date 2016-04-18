<?php

namespace Drupal\file_mdm\Plugin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for the FileMetadata plugin.
 *
 * @Annotation
 */
class FileMetadata extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * An informative description of the plugin.
   *
   * The string should be wrapped in a @Translation().
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $help;

}
