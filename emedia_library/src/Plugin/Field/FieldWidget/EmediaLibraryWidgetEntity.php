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
 *   id = "emedia_library_widget_entity",
 *   label = @Translation("eMedia Library Widget Entity"),
 *   field_types = {"emedia_library_field_gallery"}
 * )
 */


class EmediaLibraryWidgetEntity extends WidgetBase {

    public static function trustedCallbacks() {
        return ['pullEmediaAsset'];
    }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('emedia_library.settings');
    $emedialibraryUrl = $config->get('emedialibrary-url');
    $entermediaKey = $config->get('emedialibrary-key');

    $field_label = $element['#title'];
    $field_definition = $items->getFieldDefinition();
    $uid = $field_definition->getUniqueIdentifier();
    $emedia_module_id = $field_definition->getSetting('emedia_module_id') ?? '';

    $blockfindUrl = $emedialibraryUrl . '/blockfind/start.html?entitymoduleid=' .$emedia_module_id . '&entermedia.key=' . $entermediaKey . '&pickingmoduleid=' . $emedia_module_id .'&pickingtargettype=entity';
    
    $entity_id = $items[$delta]->entity_id ?? '';
    $player_id = $field_definition->getSetting('player_id') ?? 'gallery';
    $assetid = $items[$delta]->primarymedia_id ?? '';
   
    $field_wrapper_id = 'wrapper-'.$delta;
    $element['#prefix'] = '<div id="eml-field-' . $uid . '" data-fieldid="'.$uid.'" class="eml-field-container">';
    $element['#suffix'] = '</div>';

    $element['entity_id_label'] = [
      '#type' => 'markup',
      '#markup' => '<span class="form-item__label">' . $this->t('@label', ['@label' => $field_label]) . '</span>',      
    ];

    $element['entity_id'] = [
      '#type' => 'hidden',
      '#default_value' => $entity_id,
      '#attributes' => [
        'class' => ['emedia-entityid'],
      ],
    ];

    $element['player_id'] = [
      '#type' => 'hidden',
      '#default_value' => $player_id,
      '#attributes' => [
        'class' => ['emedia-playerid'],
      ],
    ];

    $element['primarymedia_id'] = [
      '#type' => 'hidden',
      '#default_value' => $assetid,
      '#attributes' => [
        'class' => ['emedia-primarymediaid'],
      ],
    ];

    $thumbnail_url = '';
    if ($assetid != '') { 
      $mediadbUrl = $emedialibraryUrl . "/mediadb/services/module/asset/data";

      $assetURL = $mediadbUrl . "/" . $assetid;
      try {

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
          //$body = $response->getBody()->getContents();
          //$jsonresponse = json_decode($body, TRUE);
          $jsonresponse = json_decode($response, TRUE);
          if (isset($jsonresponse["response"])) {
            if($jsonresponse["response"]["status"] == 'ok') {
              if (isset($jsonresponse["data"])) {
                $data = $jsonresponse["data"];
                
                if (is_array($data["downloads"]) && count($data["downloads"]) > 0) {

                  $downloads = $data["downloads"];
                  $imgsrc = '';
                  $presetid = "mediumimage";
                  foreach ($downloads as $download) {
                    if (isset($download['id']) && $download['id'] === $presetid) {
                      $imgsrc = $download['download'];
                      break;
                    }
                  }

                  // If no match is found, default to the first download element.
                  if ($imgsrc === '' && isset($downloads[0]['download'])) {
                    $imgsrc = $downloads[0]['download'];
                  }
                  
                  if ($imgsrc !== '') {
                    $thumbnail_url = $imgsrc;
                  }
                }
                
                if ($imgsrc!== '')
                {
                  $thumbnail_url = $imgsrc;
                }
              }
            }
          }
      }
      }
      catch (GuzzleException $error) {
        // Get the original response
        $response = $error->getResponse();
        // Get the info returned from the remote server.
        $response_info = $response->getBody()->getContents();
        // Using FormattableMarkup allows for the use of <pre/> tags, giving a more readable log item.
        $message = new FormattableMarkup('API connection error. Error details are as follows:<pre>@response</pre>', ['@response' => print_r(json_decode($response_info), TRUE)]);
        // Log the error
        watchdog_exception('Remote API Connection', $error, $message);
      }

    }

    // Add a thumbnail field to display the selected asset's preview image.
    if ($thumbnail_url !== '') {
      $thumbnailmarkup = '<img id="eml-thumbnail-' . $uid . '" class="emedia-thumbnail" src="' . $thumbnail_url . '" alt="' . $this->t('Thumbnail') . '" style="max-width: 150px; max-height: 150px;">';
    }
    else
    {
      $thumbnailmarkup = '';
    }

    
    
    $element['thumbnail'] = [
      '#type' => 'markup',
      '#markup' => $thumbnailmarkup,
      '#prefix' => '<div class="emedia-thumbnail-wrapper">',
      '#suffix' => '</div>',
    ];
    

    $element['pull_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Find @label', ['@label' => $field_label]),
      '#attributes' => [
        'id' => 'pull-default-button-' . $delta,
        'class' => ['pull-emedia-entity-button'],
        'data-emedialibrary-url' => $emedialibraryUrl,
        'data-blockfind-url' => $blockfindUrl,
        'data-target-id' => $field_wrapper_id, 
      ],
      '#prefix' => '<div class="emedia-find-entity-wrapper">',
      '#suffix' => '</div>',
    ];

    // Attach the library to the widget.
    $element['#attached']['library'][] = 'emedia_library/emedia_library_widget_entity';

    return $element;
  }

}