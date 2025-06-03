<?php

namespace Drupal\emedia_library\Plugin\CKEditor5;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\editor\EditorInterface;

/**
 * Provides dynamic configuration for the eMedia Library CKEditor plugin.
 *
 * @CKEditor5(
 *   id = "emedia_library_picker",
 *   label = @Translation("eMedia Library Picker")
 * )
 */
class EmediaLibraryPluginConfig extends CKEditor5PluginDefault {

   /**
     * {@inheritdoc}
     */
    public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor) : array {
          // Access Drupal configuration.
    $config = \Drupal::config('emedia_library.settings');

    // Retrieve the eMedia Library URL from the module settings.
    $emedialibraryUrl = $config->get('emedialibrary-url');
    $emedialibraryKey = $config->get('emedialibrary-key');

    // Append the key as a query parameter to the URL.
    $blockfindUrl = $emedialibraryUrl . '/blockfind/index.html?entermedia.key=' . $emedialibraryKey;
    
    return [
      'emedialibraryUrl' => $emedialibraryUrl,
      'blockfindUrl' => $blockfindUrl,
      'emedialibraryKey' => $emedialibraryKey,
    ];
  }
}
