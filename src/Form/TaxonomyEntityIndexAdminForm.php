<?php

/**
 * @file
 * Contains \Drupal\taxonomy_entity_index\Form\TaxonomyEntityIndexAdminForm.
 */

namespace Drupal\taxonomy_entity_index\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class TaxonomyEntityIndexAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_entity_index_admin_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $options = [];

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!is_null($entity_type->getBaseTable()) && $entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
        $options[$entity_type_id] = $entity_type->getLabel() . " <em>($entity_type_id)</em>";
      }
    }
    asort($options);

    $form['description'] = [
      '#markup' => t('<p>Use this form to select which entity types to index.</p>'),
    ];

    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Entity types'),
      '#options' => $options,
      '#default_value' => \Drupal::config('taxonomy_entity_index.settings')
        ->get('types'),
      '#description' => t('Select which entity types to index.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()
      ->getEditable('taxonomy_entity_index.settings')
      ->set('types', array_filter($form_state->getValue(['types'])))
      ->save();
    drupal_set_message(t('The settings have been saved.'));
  }

}
