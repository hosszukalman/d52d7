<?php
/**
 * Import terms
 */
class Articles extends Nodes {

  private $termMap;

  /**
   * @var PDOStatement
   */
  private $getNewTerms;

  /**
   * @var PDOStatement
   */
  private $getOldTerms;

  function __construct() {
    parent::__construct();

    $this->getNewTerms = $this->dbhConnection->prepare("SELECT t.new_tid FROM terms t WHERE t.old_tid = :old_tid");
    $this->getOldTerms = $this->dbhImport->prepare("SELECT td.* FROM term_node tn
      INNER JOIN term_data td USING(tid)
      WHERE tn.nid = :nid AND td.vid IN (13, 2, 8, 7, 14)");

    foreach ($this->dbhImport->query("SELECT td.* FROM node n
      RIGHT JOIN term_node tn USING(nid)
      RIGHT JOIN term_data td ON (tn.tid = td.tid)
      WHERE n.type = 'normal_tartalom' AND td.vid IN (13, 2, 8, 7, 14)
      GROUP BY td.tid ORDER BY n.created", PDO::FETCH_ASSOC) as $term) {

      $this->getNewTerms->execute(array(':old_tid' => $term['tid']));
      $result = $this->getNewTerms->fetch(PDO::FETCH_ASSOC);
      if (!empty($result)) {
//        $this->termMap[$term['vid']][$term['tid']] = $result['new_tid'];
        $this->termMap[$term['tid']] = $result['new_tid'];
      }
    }
  }

  public function deleteAll() {
    // Delete from Drupal
    $query = db_select('node', 'n');
    $query
      ->condition('n.type', 'cikk')
      ->fields('n', array('nid'));
    $result = $query->execute()->fetchAll();

    $counter = 0;
    foreach ($result as $node) {
      node_delete($node->nid);
      echo $counter++ . PHP_EOL;
    }

    // Delete from connection DB.
    $this->dbhConnection->exec('TRUNCATE TABLE nodes');

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
      $node->created = $oldContent['created']; // Not working

      // Fields
      $node->body[LANGUAGE_NONE][0]['value'] = $oldContent['field_trzs_value'];
      $node->body[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $node->field_hun_summory[LANGUAGE_NONE][0]['value'] = $oldContent['field_bevezet_value'];
      $node->field_hun_summory[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $node->field_eng_summory[LANGUAGE_NONE][0]['value'] = $oldContent['field_eng_abstract_value'];
      $node->field_eng_summory[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      // Taxonomy terms
      $this->getOldTerms->execute(array(':nid' => $oldContent['nid']));
      $terms = $this->getOldTerms->fetchAll(PDO::FETCH_ASSOC);

      foreach ($terms as $term) {
        switch ($term['vid']) {
          case 14:
            // Szerzők
            $node->field_authors_term[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;

          case 7:
            // Dosszié
            $node->field_dossier[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;
        }
      }

      node_save($node);

      $this->saveNodeToConnectionTable($oldContent['nid'], $node->nid);

      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }
  }
}