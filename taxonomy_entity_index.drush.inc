<?php

/**
 * Implements hook_drush_command().
 *
 * @See drush_parse_command() for a list of recognized keys.
 *
 * @return array
 *   An associative array describing each command.
 */
function taxonomy_entity_index_drush_command() {
  $items = array();

  $items['taxonomy-entity-index-rebuild'] = array(
    'description' => "Rebuild taxonomy entity index",
    'options' => array(
      'entity_types' => 'A comma separated list of entity types to reindex.',
    ),
    'examples' => array(
      'drush tei-rebuild' => 'Rebuilds the index for all entity types.',
      'drush tei-rebuild --entity_types="paragraph,content"' => 'Rebuilds the index for the paragraph and content entity types',
    ),
    'aliases' => array('tei-rebuild'),
  );

  return $items;
}

/**
 * Rebuilds the taxonomy entity index.
 */
function drush_taxonomy_entity_index_rebuild() {
  $operations = array();
  $entity_types = drush_get_option('entity_types');

  if (empty($entity_types)) {
    $entity_types = array_keys(\Drupal::entityTypeManager()->getDefinitions());
  }
  else {
    $entity_types = explode(',', $entity_types);
  }

  foreach ($entity_types as $type) {
    $operations[] = array('taxonomy_entity_index_reindex_entity_type', array($type));
  }

  $batch = array(
    'operations' => $operations,
    'finished' => 'taxonomy_entity_index_finished',
  );

  batch_set($batch);

  // Process the batch.
  $batch =& batch_get();
  $batch['progressive'] = FALSE;
  batch_process();
}

/**
 * Validates the taxonomy entity index rebuild command.
 */
function drush_taxonomy_entity_index_rebuild_validate() {
  if ($ids_submitted = drush_get_option('entity_types')) {
    $ids_submitted = explode(',', $ids_submitted);
    $ids_valid = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    $ids_invalid = array();
    foreach ($ids_submitted as $id_submitted) {
      if (!in_array($id_submitted, $ids_valid)) {
        $ids_invalid[] = $id_submitted;
      }
    }
    if (!empty($ids_invalid)) {
      return drush_set_error('INVALID_ENTITY_TYPE', dt('The following are not valid entity types: ', array('@entity_type_id_submitted' => implode(',', $ids_invalid))));
    }
  }
}
