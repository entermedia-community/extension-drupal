<?php

namespace Drupal\emedia_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'emedia_library_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "emedia_library_formatter",
 *   label = @Translation("eMedia Library Formatter"),
 *   field_types = {"emedia_library_field"}
 * )
 */
class EmediaLibraryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $values, $langcode) {
    $elements = [];

    // Get the eMedia Library URL and API key from the module settings.
    $emedialibraryUrl = \Drupal::config('emedia_library.settings')->get('emedialibrary-url');
    $entermediaKey = \Drupal::config('emedia_library.settings')->get('emedialibrary-key');

    $field_definition = $values->getFieldDefinition();
    $preset_id = $field_definition->getSetting('preset_id') ?? '';

    $mediadbUrl = $emedialibraryUrl . "/mediadb/services/module/asset/players/webplayer/render.json";

    foreach ($values as $delta => $value) {     
      //Only one value exists in the field.
      $mediadbUrl = $mediadbUrl . "?assetid=".$value->asset_id."&presetid=".$preset_id;

     
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
        $jsonresponse = json_decode($body, TRUE);

        if (isset($jsonresponse["response"])) {
          
          if ($jsonresponse["response"]["status"] == 'ok')
          {
            
            $html = $jsonresponse["html"];

            if ($html!== '')
            {

              $elements[$delta] = [
                '#type' => 'processed_text',
                '#text' => $html,
                '#format' => 'full_html',
                '#cache' => [
                  'max-age' => 1000 * 60 * 60, // Cache for 1 hour.
                ],
                //'#label_hidden' => 'true', 
              ];
            }
            else {
              // If no image is found, log the error and display a message.
              \Drupal::logger('emedia_library')->error('No image found for asset ID @id at @address', [
                '@id' => $value->asset_id,
                '@address' => $mediadbUrl,
              ]);

              $elements[$delta] = [
                '#markup' => $this->t('No image found for this eMedia Library asset.'),
                //'#label_hidden' => 'true', // Hide the label.
              ];
            }
          }
        }
      } else {
        // Log the error if the cURL request fails.
        
        \Drupal::logger('emedia_library')->error('Failed at @address - HTTP Code: @code. cURL Error: @error', [
          '@code' => $httpcode,
          '@error' => $curl_error,
          '@address' => $mediadbUrl,
        ]);

        $elements[$delta] = [
          '#markup' => $this->t('Failed to fetch the eMedia Library asset. ' . $curl_error),
          //'#label_hidden' => 'true', // Hide the label.
        ];
      }
    }

    return $elements;
  }
}