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

    $properties['presetid'] = DataDefinition::create('string')
      ->setLabel(t('Preset Id'))
      ->setDescription(t('Select the Preset Id for this asset.'));

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
        
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('asset_id')->getValue());
  }

  /**
   * Define default settings for the field type.
   *
   * @return array
   *   An array of default settings.
   */
  public static function defaultFieldSettings() {
    return [
      'presetid' => 'webplargeimage',
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
  
    // Retrieve  comma-separated list from the module settings.
    $options = $this->fetchImageSizeOptions();
  
  
    $form2['presetid'] = [
      '#type' => 'select',
      '#title' => t('Preset Id'),
      '#options' => $options,
      '#default_value' => $settings['presetid'],
      '#description' => t('Select the default Preset Id for this field.'),
    ];
  
    return $form2;
  }


  public static function fetchImageSizeOptions() {
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
    if (isset($json['results']) && is_array($json['results'])) {
      foreach ($json['results'] as $preset) {
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

    $summary[] = t('Default Preset Id: @size', ['@size' => $settings['presetid']]);

    return $summary;
  }
}