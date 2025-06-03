<?php

namespace Drupal\emedia_library\Plugin\media\Source;

use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\media\MediaInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a media source for external images.
 *
 * @MediaSource(
 *   id = "emedia_library",
 *   label = @Translation("eMedia Library Image"),
 *   description = @Translation("Use externally hosted images as media."),
 *   allowed_field_types = {"string", "uri"},
 *   default_thumbnail_filename = "no-thumbnail.png"
 * )
 */
class EmediaLibrary extends MediaSourceBase {

  public function getMetadataAttributes() {
    return [
      'title' => $this->t('Title'),
      'image_url' => $this->t('Image URL'),
    ];
  }

  public function getMetadata(MediaInterface $media, $attribute_name) {
    $field = $media->get($this->configuration['source_field'])->value;

    switch ($attribute_name) {
      case 'title':
        return $media->label();
      case 'image_url':
        return $field;
    }

    return parent::getMetadata($media, $attribute_name);
  }

  public function createSourceField(MediaTypeInterface $media_type) {
    return [
      'field_name' => 'field_emedia_library_image_url',
      'type' => 'string',
      'settings' => [
        'max_length' => 2048,
      ],
    ];
  }

  public function getSourceFieldValue(MediaInterface $media) {
    return $media->getSource()->getMetadata($media, 'image_url');
  }
}
