<?php

namespace Drupal\emedia_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'emedia_webgallery' formatter.
 *
 * @FieldFormatter(
 *   id = "emedia_webgallery",
 *   label = @Translation("Emedia Web Gallery"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class EmediaWebGalleryFormatter extends FormatterBase {

     /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Fetch the web gallery from entermedia.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $enter_media = $entity_type_manager->getStorage('media_type')->load($items->getEntity()->bundle());
    $oembed_provider = $enter_media->get('source_configuration')['providers'][0];
    $providers = $entity_type_manager->getStorage('oembed_provider')->loadByProperties(array('label'=> $oembed_provider));
    $providers = reset($providers);
    $endpoint_url = $providers->get('endpoints')[0]['url'];
    foreach ($items as $delta => $item) {
        $emedia_url = $endpoint_url . '?url=' . $item->value;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $emedia_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode >= 200 && $httpcode < 300 && !empty($data)) {
            $data = json_decode($data, TRUE);
            $element[$delta] = [
                '#type' => 'processed_text',
                '#text' => $data['html'],
                '#format' => 'full_html',
                '#cache' => [
                    'max-age' => 0, // Cache for 1 hour
                ],
            ];
        } else {
            $element[$delta] = ['#markup' => t('Failed to fetch the enter media web gallery.')];
        }
    }

    return $element;
  }

}