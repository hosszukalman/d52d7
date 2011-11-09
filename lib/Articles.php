<?php
/**
 * Import terms
 */
class Articles extends Nodes {

  function __construct() {
    parent::__construct();

  }

  public function deleteAll() {

  }

  public function execute() {
    $counter = 0;
    foreach ($this->dbhImport->query("SELECT n.*, cft.field_trzs_value, cfb.field_bevezet_value, cfea.field_eng_abstract_value FROM node n
      LEFT JOIN content_field_trzs cft USING(vid)
      LEFT JOIN content_field_bevezet cfb USING(vid)
      LEFT JOIN content_field_eng_abstract cfea USING(vid)
      WHERE n.type = 'normal_tartalom' ORDER BY n.created", PDO::FETCH_ASSOC) as $oldContent) {

      // New node objec
      $node = new stdClass();

      // Base information
      $node->title = $oldContent['title'];
      $node->type = 'cikk';
      $node->language = LANGUAGE_NONE;
      $node->status = $oldContent['status'];
      $node->comment = $oldContent['comment'];
      $node->promote = $oldContent['promote'];
      $node->sticky = $oldContent['sticky'];

      // Fields
      $node->body[LANGUAGE_NONE][0]['value'] = $oldContent['field_trzs_value'];
      $node->body[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $node->field_hun_summory[LANGUAGE_NONE][0]['value'] = $oldContent['field_bevezet_value'];
      $node->field_hun_summory[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $node->field_eng_summory[LANGUAGE_NONE][0]['value'] = $oldContent['field_eng_abstract_value'];
      $node->field_eng_summory[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      node_save($node);

      $this->saveNodeToConnectionTable($oldContent['nid'], $node->nid);

      echo $counter++ . PHP_EOL;
    }
  }
}