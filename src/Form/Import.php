<?php

namespace Drupal\image_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Import
 *
 * @author Attila Németh
 * 04.03.2021
 */
class Import extends FormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'image_import_import_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $definitions = $this->_getPluginDefinitions();
    $labels = [];
    foreach($definitions as $definition) {
      $labels[] = $definition['label'];
    }
    $form['url'] = [
      '#type' => 'url',
      '#required' => TRUE,
      '#title' => t('Source'),
      '#description' => t('Applicable Plugins: @plugins', [
        '@plugins' => implode(', ', $labels),
      ]),
    ];
    $form['fullsize'] = [
      '#type' => 'checkbox',
      '#title' => t('Actual size'),
      '#description' => t('Uncheck if you want to use images in a restricted size'),
      '#default_value' => TRUE,
    ];
    $form['size'] = [
      '#type' => 'number',
      '#title' => t('Size'),
      '#size' => 12,
      '#field_suffix' => t('Pixel'),
      '#description' => t('The length of the longer size of the image.'),
      '#min' => 48,
      '#max' => 9192,
      '#states' => [
        'invisible' => [
          ':input[name="fullsize"]' => array('checked' => TRUE),
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Import Image'),
    ];
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $definitions = $this->_getPluginDefinitions();
    $isApplicable = FALSE;
    foreach($definitions as $definition) {
      $plugin = \Drupal::service('plugin.manager.image_import_source')
          ->createInstance($definition['id'], [
            'url' => $form_state->getValue('url'),
          ]);
      if ($plugin->isApplicable()) {
        $isApplicable = TRUE;
      }
    }
    if (!$isApplicable) {
      $form_state->setErrorByName('url', t('This URL is not supported by any plugin'));
    }
    return parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $definitions = $this->_getPluginDefinitions();
    if ($form_state->getValue('fullsize') == 1) {
      $size = -1;
    }
    else {
      $size = $form_state->getValue('size');
    }
    foreach($definitions as $definition) {
      $plugin = \Drupal::service('plugin.manager.image_import_source')
          ->createInstance($definition['id'], [
            'url' => $form_state->getValue('url'),
            'size' => $size,
          ]);
      if ($plugin->isApplicable()) {
        $media = $plugin->createMedia();
        $link = $media->toUrl('edit-form');
        $form_state->setRedirectUrl($link);
        break;
      }
    }
  }
  
  /**
   * All available plugins
   * @return array
   */
  private function _getPluginDefinitions(): array
  {
    $pluginManager = \Drupal::service('plugin.manager.image_import_source');
    $pluginDefinitions = $pluginManager->getDefinitions();
    return $pluginDefinitions;
  }
  
}
