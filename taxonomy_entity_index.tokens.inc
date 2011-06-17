<?php

/**
 * @file
 * Token integration for the taxonomy_entity_index module.
 */

function taxonomy_entity_index_token_info() {
  $info = array();

  if (!module_exists('token')) {
    // We depend on the token_get_entity_mapping() function in token.module.
    return $info;
  }

  // Add [entity:terms] tokens for all entities.
  $entities = entity_get_info();
  foreach ($entities as $entity => $entity_info) {
    if (!isset($entity_info['token type'])) {
      continue;
    }

    $tokens = &$info['tokens'][$entity_info['token type']];
    $tokens['terms'] = array(
      'name' => t('Terms'),
      'description' => t('The terms assoicated with the @entity.', array('@entity' => drupal_strtolower($entity_info['label']))),
      'type' => 'array',
    );
  }

  return $info;
}

/**
 * Implements hook_tokens().
 */
function taxonomy_entity_index_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();

  $sanitize = !empty($options['sanitize']);

  if (!module_exists('token')) {
    // We depend on the token_get_entity_mapping() function in token.module.
    return $replacements;
  }

  // Entity tokens.
  if (!empty($data[$type]) && $entity_type = token_get_entity_mapping('token', $type)) {
    $entity = $data[$type];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'terms':
          $terms = _taxonomy_entity_index_get_terms_array($entity_type, $entity);
          $replacements[$original] = token_render_array($terms, $options);
          break;
      }
    }

    // Chained token relationships.
    if ($terms_tokens = token_find_with_prefix($tokens, 'terms')) {
      if ($terms = _taxonomy_entity_index_get_terms_array($entity_type, $entity)) {
        $replacements += token_generate('array', $terms_tokens, array('array' => $terms), $options);
      }
    }
  }

  return $replacements;
}

function _taxonomy_entity_index_get_terms_array($entity_type, $entity) {
  list($entity_id, $revision_id) = entity_extract_ids($entity_type, $entity);
  // @todo How to properly 'order' these by field/delta?
  $tids = db_query("SELECT tid FROM {taxonomy_entity_index} WHERE entity_type = :type AND entity_id = :id AND revision_id = :vid ORDER BY delta", array(
    ':type' => $entity_type,
    ':id' => $entity_id,
    ':vid' => isset($revision_id) ? $revision_id : $entity_id,
  ))->fetchCol();
  $terms = taxonomy_term_load_multiple($tids);
  foreach ($terms as $tid => $term) {
    $terms[$tid] = $term->name;
  }
  return $terms;
}
