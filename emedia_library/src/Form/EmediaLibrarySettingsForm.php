<?php

namespace Drupal\emedia_library\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure eMedia Library settings for this site.
 */
class EmediaLibrarySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['emedia_library.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'emedia_library_settings';
  }

  /**
   * Build the settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The modified form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('emedia_library.settings');

    // Add a text field for the eMedia Library URL.
    $form['emedialibrary_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('eMedia Library URL'),
      '#default_value' => $config->get('emedialibrary-url'),
      '#description' => $this->t('Enter the base URL for the eMedia Library.'),
      '#required' => TRUE,
    ];

    // Add a text field for the eMedia Library API key.
    $form['emedialibrary_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('eMedia Library API Key'),
      '#default_value' => $config->get('emedialibrary-key'),
      '#description' => $this->t('Enter the API key for accessing eMedia Library services.'),
      '#required' => TRUE,
    ];

    $form['image_size_options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image Size Options'),
      '#default_value' => $config->get('image_size_options'),
      '#description' => $this->t('Enter a comma-separated list of image size options (e.g., webpthumbimage,webplargeimage,webpwidesscreencrop).'),
      '#required' => TRUE,
    ];
  

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit handler for the settings form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('emedia_library.settings')
      ->set('emedialibrary-url', $form_state->getValue('emedialibrary_url'))
      ->set('emedialibrary-key', $form_state->getValue('emedialibrary_key'))
      ->set('image_size_options', $form_state->getValue('image_size_options'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}