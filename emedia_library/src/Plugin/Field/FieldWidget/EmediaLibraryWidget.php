<?php

namespace Drupal\emedia_library\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface; 
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Plugin implementation of the 'emedia_library_widget' widget.
 *
 * @FieldWidget(
 *   id = "emedia_library_widget",
 *   label = @Translation("eMedia Library Widget"),
 *   field_types = {"emedia_library_field"}
 * )
 */


class EmediaLibraryWidget extends WidgetBase {

    public static function trustedCallbacks() {
        return ['pullEmediaAsset'];
    }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('emedia_library.settings');
    $emedialibraryUrl = $config->get('emedialibrary-url');
    $emedialibraryKey = $config->get('emedialibrary-key');

    // Append the key as a query parameter to the URL.
    $emedialibraryUrl = $emedialibraryUrl . '/blockfind/index.html?entermedia.key=' . $emedialibraryKey;

    // Dynamically pull the field label.
    $field_label = $element['#title'];
    
    // Generate a unique ID for the asset_id field using $element['#id'].
    $field_wrapper_id = 'wrapper-'.$delta;

    $assetid = $items[$delta]->asset_id ?? '';

    // Add a container for all elements.
    $element['#prefix'] = '<div id="emedia-library-container-' . $delta . '" class="emedia-library-container">';
    $element['#suffix'] = '</div>';


    $element['asset_id_label'] = [
      '#type' => 'markup',
      '#markup' => '<span class="form-item__label">' . $this->t('@label', ['@label' => $field_label]) . '</span>',
      
    ];

    $element['asset_id'] = [
      '#type' => 'hidden',
      '#default_value' => $assetid,
      '#attributes' => [
        'class' => ['emedia-image-assetid'],
      ],
    ];
    $thumbnail_url = '';
    if ($assetid != '') {
        // Get the eMedia Library URL and API key from the module settings.
      $emedialibraryUrl = \Drupal::config('emedia_library.settings')->get('emedialibrary-url');
      $entermediaKey = \Drupal::config('emedia_library.settings')->get('emedialibrary-key');
      $mediadbUrl = $emedialibraryUrl . "/mediadb/services/module/asset/data";

      $assetURL = $mediadbUrl . "/" . $assetid;

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
        $data = $jsonresponse["data"];
        $downloads = $data["downloads"];
        $imgsrc = $downloads[0]['download'];
        
        if ($imgsrc!== '')
        {
          $thumbnail_url = $imgsrc;
        }
     }
    }

    // Add a thumbnail field to display the selected asset's preview image.
    if ($thumbnail_url !== '') {
      $element['thumbnail'] = [
        '#type' => 'markup',
        '#markup' => '<img id="thumbnail-' . $delta . '" class="emedia-thumbnail" src="' . $thumbnail_url . '" alt="' . $this->t('Thumbnail') . '" style="max-width: 150px; max-height: 150px;">',
        '#prefix' => '<div class="emedia-thumbnail-wrapper">',
        '#suffix' => '</div>',
      ];
    }

    $element['pull_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Find @label', ['@label' => $field_label]),
      '#attributes' => [
        'id' => 'pull-default-button-' . $delta,
        'class' => ['pull-emedia-asset-button'],
        'data-emedialibrary-url' => $emedialibraryUrl,
        'data-target-id' => $field_wrapper_id, 
      ],
      '#prefix' => '<div class="emedia-find-asset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Attach the library to the widget.
    $element['#attached']['library'][] = 'emedia_library/emedia_library_widget';

    return $element;
  }

}