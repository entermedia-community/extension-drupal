<?php

namespace Drupal\emedia_library\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'emedia_library_field' field type.
 *
 * @FieldType(
 *   id = "emedia_library_field",
 *   label = @Translation("eMedia Image"),
 *   description = @Translation("A field that pulls an image URL from eMedia Library settings."),
 *   default_widget = "emedia_library_widget",
 *   default_formatter = "emedia_library_formatter"
 * )
 */
class EmediaLibraryField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['asset_id'] = DataDefinition::create('string')
      ->setLabel(t('Asset ID'));

      $config = \Drupal::config('emedia_library.settings');
      $emedialibraryUrl = $config->get('emedialibrary-url');
      $emedialibraryKey = $config->get('emedialibrary-key');


      

    $properties['image_size'] = DataDefinition::create('string')
      ->setLabel(t('Image Size'))
      ->setDescription(t('Select the image size.'))
      ->addConstraint('AllowedValues', [
        'choices' => [
          'thumbnail' => t('Thumbnail'),
          'full' => t('Full'),
          'custom' => t('Custom'),
        ],
      ]);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'asset_id' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'image_size' => [
          'type' => 'varchar',
          'length' => 128,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('asset_id')->getValue()) && empty($this->get('image_size')->getValue());
  }

  /**
   * Define default settings for the field type.
   *
   * @return array
   *   An array of default settings.
   */
  public static function defaultFieldSettings() {
    return [
      'image_size' => 'thumbnail',
    ];
  }

  /**
   * Build the settings form for the field type.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The modified form array.
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
  
    // Retrieve the comma-separated list from the module settings.
    $config = \Drupal::config('emedia_library.settings');
    $image_size_options = $config->get('image_size_options') ?? 'webpthumbimage,webplargeimage,webpwidesscreencrop';
  
    $options = [];
    foreach (explode(',', $image_size_options) as $option) {
      $options[trim($option)] = t(ucfirst(trim($option)));
    }
  
    $form2['image_size'] = [
      '#type' => 'select',
      '#title' => t('Default Image Size'),
      '#options' => $options,
      '#default_value' => $settings['image_size'],
      '#description' => t('Select the default image size for this field.'),
    ];
  
    return $form2;
  }

  /**
   * Validate the settings form for the field type.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateFieldSettingsForm(array &$form, FormStateInterface $form_state) {
    // Add validation logic if needed.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('Default image size: @size', ['@size' => $settings['image_size']]);

    return $summary;
  }
}