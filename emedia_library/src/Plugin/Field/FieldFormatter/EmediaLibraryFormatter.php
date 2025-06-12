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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Get the eMedia Library URL and API key from the module settings.
    $emedialibraryUrl = \Drupal::config('emedia_library.settings')->get('emedialibrary-url');
    $entermediaKey = \Drupal::config('emedia_library.settings')->get('emedialibrary-key');
    $mediadbUrl = $emedialibraryUrl . "/mediadb/services/module/asset/data";

    

    foreach ($items as $delta => $item) {
      
      $assetURL = $mediadbUrl . "/" . $item->asset_id;

      
      // Retrieve the image_size from the field type properties.
      $field_definition = $items->getFieldDefinition();
      $image_size = $field_definition->getSetting('image_size') ?? '';
 

      // Initialize cURL.
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $assetURL);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-tokentype: entermedia', 
        'X-token: ' . $entermediaKey, 
      ]);

      // Execute the cURL request.
      $response = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curl_error = curl_error($ch); // Capture cURL error message.
      curl_close($ch);

      if ($httpcode >= 200 && $httpcode < 300 && !empty($response)) {
        $jsonresponse = json_decode($response, TRUE);
        if (isset($jsonresponse["response"])) {
          
          if ($jsonresponse["response"]["status"] == 'ok')
          {
              
            $data = $jsonresponse["data"];
            $downloads = $data["downloads"];
            $imgsrc = $downloads[0]['download'];
            if ($imgsrc!== '')
            {
              $html = '<div class="emedia-image">';
              $html .= '<img src="' . $imgsrc . '" alt="" />';
              if (isset($data["assettitle"]) && $data["assettitle"] !== '') {
                
                $title = $data["assettitle"];
                if ($title !== '') {
                  $html .= '<p>' . $title . '</p>';
                }
              }

              //$html .= '<p>' . $data['description'] . '</p>';
              $html .= '</div>';

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
                '@id' => $item->asset_id,
                '@address' => $assetURL,
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
          '@address' => $assetURL,
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