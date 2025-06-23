<?php

namespace Drupal\emedia_library\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'emedia_library_field_gallery' field type.
 *
 * @FieldType(
 *   id = "emedia_library_field_gallery",
 *   label = @Translation("eMedia Gallery"),
 *   description = @Translation("Pulls a Media Gallery from eMedia Library."),
 *   default_widget = "emedia_library_widget",
 *   default_formatter = "emedia_library_formatter"
 * )
 */
class EmediaLibraryFieldGallery extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['entity_id'] = DataDefinition::create('string')
      ->setLabel(t('Entity ID'));

    $properties['player_id'] = DataDefinition::create('string')
      ->setLabel(t('Entity Player'))
      ->setDescription(t('Select the Entity Player.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'entity_id' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'player_id' => [
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
    return empty($this->get('entity_id')->getValue());
  }

  /**
   * Define default settings for the field type.
   *
   * @return array
   *   An array of default settings.
   */
  public static function defaultFieldSettings() {
    return [
      'player_id' => 'gallery',
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

      // Get the eMedia Library URL and API key from the module settings.
    $emedialibraryUrl = \Drupal::config('emedia_library.settings')->get('emedialibrary-url');
    $entermediaKey = \Drupal::config('emedia_library.settings')->get('emedialibrary-key');
    $mediadbUrl = $emedialibraryUrl . "/mediadb/services/lists/search/entityplayer";
  
    // Retrieve  comma-separated list from the module settings.
    $options = $this->fetchEntityPlayers($mediadbUrl, $entermediaKey);
  
  
    $form2['player_id'] = [
      '#type' => 'select',
      '#title' => t('Player Type'),
      '#options' => $options,
      '#default_value' => $settings['player_id'],
      '#description' => t('Select the default Player type for this field.'),
    ];
  
    return $form2;
  }


public static function fetchEntityPlayers(String $mediadbUrl, String $entermediaKey) {
 $options = [];

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

  
  $client = \Drupal::httpClient();
  $response = $client->post($mediadbUrl, [
    'headers' => [
      'Content-Type' => 'application/json',
      'X-tokentype' => 'entermedia', 
      'X-token' => $entermediaKey,
    ],
    'body' => json_encode($query),
    'timeout' => 3,
  ]);
  $httpcode = $response->getStatusCode();

  if ($httpcode >= 200 && $httpcode < 300 && !empty($response)) {
    $body = $response->getBody()->getContents();
    
    $json = json_decode($body, TRUE);
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

    $summary[] = t('Default Preset Id: @size', ['@size' => $settings['player_id']]);

    return $summary;
  }
}