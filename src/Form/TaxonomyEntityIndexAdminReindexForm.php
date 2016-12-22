<?php

/**
 * @file
 * Contains \Drupal\taxonomy_entity_index\Form\TaxonomyEntityIndexAdminForm.
 */

namespace Drupal\taxonomy_entity_index\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class TaxonomyEntityIndexAdminReindexForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_entity_index_admin_reindex_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $entity_types_to_index = \Drupal::config('taxonomy_entity_index.settings')
      ->get('types');

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!is_null($entity_type->getBaseTable())
        && $entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')
        && in_array($entity_type_id, $entity_types_to_index)
      ) {
        $options[$entity_type_id] = $entity_type->getLabel() . " <em>($entity_type_id)</em>";
      }
    }
    asort($options);

    $form['description'] = [
      '#markup' => t('<p>Use this form to reindex all terms for the selected entity types.</p>'),
    ];

    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Entity types'),
      '#options' => $options,
      '#description' => t('Re-index all terms for the selected entity types.'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Rebuild Index',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $types = array_filter($form_state->getValue(['types']));

    // Add an operation for each entity type.
    foreach ($types as $type) {
      $operations[] = ['taxonomy_entity_index_reindex_entity_type', [$type]];
    }

    // Set a batch operation for each selected entity type.
    $batch = [
      'operations' => $operations,
      'finished' => 'taxonomy_entity_index_reindex_finished',
    ];

    // Execute the batch.
    batch_set($batch);
  }

}
