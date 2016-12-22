<?php
namespace Drupal\taxonomy_entity_index\Plugin\views\field;
use Drupal\Component\Utility\Html;
use Drupal\taxonomy\Plugin\views\field\TaxonomyIndexTid;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Field handler to display all taxonomy terms of an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("taxonomy_entity_index_tid")
 */
class TaxonomyEntityIndexTid extends TaxonomyIndexTid {
  /**
   * Stores the base table information.
   */
  var $base_table_info = NULL;

  /**
   * Stores the entity info of the base table.
   */
  var $entity_info = NULL;

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface.
   */
  protected $vocabularyStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vocabulary_storage);
    $this->vocabularyStorage = $vocabulary_storage;
  }

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Reset the variables which are set by the parent class.
    unset($this->additional_fields['nid']);

    $this->base_table_info = \Drupal::service('views.views_data')->get($this->table);
    $this->entity_info = \Drupal::entityTypeManager()->getDefinition($this->base_table_info['table']['entity type']);
    $this->additional_fields['entity_id'] = array('table' => $this->entity_info->getBaseTable(), 'field' => $this->entity_info->getKey('id'));
  }

  public function pre_render(&$values) {
    $this->field_alias = $this->aliases['entity_id'];
    $entity_ids = array();
    foreach ($values as $result) {
      if (!empty($result->{$this->field_alias})) {
        $entity_ids[] = $result->{$this->field_alias};
      }
    }

    if ($entity_ids) {
      $query = db_select('taxonomy_term_data', 'td');
      $query->innerJoin('taxonomy_entity_index', 'tei', 'td.tid = tei.tid');
      $query->innerJoin('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid');
      $query->fields('td');
      $query->addField('tei', 'entity_id', 'entity_id');
      $query->addField('tei', 'entity_type', 'entity_type');
      $query->addField('tei', 'revision_id', 'revision_id');
      $query->addField('tv', 'name', 'vocabulary');
      $query->addField('tv', 'machine_name', 'vocabulary_machine_name');
      $query->orderby('td.weight');
      $query->orderby('td.name');
      $query->condition('tei.entity_id', $entity_ids);
      $query->condition('tei.entity_type', $this->base_table_info['table']['entity type']);
      $query->addTag('term_access');
      $vocabs = array_filter($this->options['vids']);
      if (!empty($this->options['limit']) && !empty($vocabs)) {
        $query->condition('tv.machine_name', $vocabs);
      }
      $result = $query->execute();

      foreach ($result as $term) {
        $this->items[$term->entity_id][$term->tid]['name'] = Html::escape($term->name);
        $this->items[$term->entity_id][$term->tid]['tid'] = $term->tid;
        $this->items[$term->entity_id][$term->tid]['vocabulary_machine_name'] = Html::escape($term->vocabulary_machine_name);
        $this->items[$term->entity_id][$term->tid]['vocabulary'] = Html::escape($term->vocabulary);

        if (!empty($this->options['link_to_taxonomy'])) {
          $this->items[$term->entity_id][$term->tid]['make_link'] = TRUE;
          $this->items[$term->entity_id][$term->tid]['path'] = 'taxonomy/term/' . $term->tid;
        }
      }
    }
  }
}
