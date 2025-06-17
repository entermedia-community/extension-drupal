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
/*
    $image_size_options = $config->get('image_size_options') ?? 'webpthumbimage,webplargeimage,webpwidesscreencrop';
    $options = [];
    foreach (explode(',', $image_size_options) as $option) {
      $options[trim($option)] = t(ucfirst(trim($option)));
    }
*/
    $options = $this->fetchImageSizeOptions();
  
  
    $form2['image_size'] = [
      '#type' => 'select',
      '#title' => t('Default Image Size'),
      '#options' => $options,
      '#default_value' => $settings['image_size'],
      '#description' => t('Select the default image size for this field.'),
    ];
  
    return $form2;
  }


  protected function fetchImageSizeOptions() {
  $options = [];

  // Get the eMedia Library URL and API key from the module settings.
  $emedialibraryUrl = \Drupal::config('emedia_library.settings')->get('emedialibrary-url');
  $entermediaKey = \Drupal::config('emedia_library.settings')->get('emedialibrary-key');
  $mediadbUrl = $emedialibraryUrl . "/mediadb/services/lists/search/convertpreset";

 $query = [
    "page" => "1",
    "hitsperpage" => "40",
    "query" => [
      "terms" => [
        [
          "field" => "id",
          "operation" => "matches",
          "value" => "*"
        ]
      ]
    ]
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $mediadbUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-tokentype: entermedia', 
    'X-token: ' . $entermediaKey, 
  ]);

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));

  $response = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpcode >= 200 && $httpcode < 300 && !empty($response)) {
    $json = json_decode($response, TRUE);
    // Adjust the following line to match your API's JSON structure
    if (isset($json['results']) && is_array($json['results'])) {
      foreach ($json['results'] as $preset) {
        // Assuming each $size is a string, or adjust as needed
        $options[$preset["id"]] = $preset["name"];
      }
    }
  }

  return $options;
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