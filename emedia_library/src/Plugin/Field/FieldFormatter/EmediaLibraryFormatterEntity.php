<?php

namespace Drupal\emedia_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'emedia_library_formatter_entity' formatter.
 *
 * @FieldFormatter(
 *   id = "emedia_library_formatter_entity",
 *   label = @Translation("eMedia Library Formatter"),
 *   field_types = {"emedia_library_field_entity"}
 * )
 */
class EmediaLibraryFormatterEntity extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $values, $langcode) {
    $elements = [];

    // Get the eMedia Library URL and API key from the module settings.
    $emedialibraryUrl = \Drupal::config('emedia_library.settings')->get('emedialibrary-url');
    $entermediaKey = \Drupal::config('emedia_library.settings')->get('emedialibrary-key');

    $field_definition = $values->getFieldDefinition();
    $emedia_module_id = $field_definition->getSetting('emedia_module_id') ?? '';
    $player_id = $field_definition->getSetting('player_id') ?? 'gallery';

    $mediadbUrl = $emedialibraryUrl . "/mediadb/services/module/".$emedia_module_id."/players/render.json";

    foreach ($values as $delta => $value) {     
      //Only one value exists in the field.
      $mediadbUrl = $mediadbUrl . "?entityid=".$value->entity_id."&playertype=".$player_id;



    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $mediadbUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: text/html',
      'X-tokentype: entermedia', 
      'X-token: ' . $entermediaKey, 
    ]);
  

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

      if ($httpcode >= 200 && $httpcode < 300 && !empty($response)) {
        $jsonresponse = json_decode($response, TRUE);
       
        if (isset($jsonresponse["html"])) {
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
                '#attached' => [
                  'library' => [
                    'emedia_library/emedia_library_field_entity',
                  ],
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
      } else {
        // Log the error if the cURL request fails.
        
        \Drupal::logger('emedia_library')->error('Failed at @address - HTTP Code: @code. cURL Error: @error', [
          '@code' => $httpcode,
          '@error' => $curl_error,
          '@address' => $mediadbUrl,
        ]);

        $elements[$delta] = [
          '#markup' => $this->t('Failed to fetch the eMedia Library gallery. ' . $curl_error),
          //'#label_hidden' => 'true', // Hide the label.
        ];
      }
    }

    return $elements;
  }
}