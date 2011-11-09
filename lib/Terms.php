<?php
/**
 * Import terms
 */
class Terms extends Importer {

  /**
   * @var PDOStatement
   */
  private $getOldTerms;

  function __construct() {
    parent::__construct();

    $this->getOldTerms = $this->dbhImport->prepare("SELECT td.*, th.parent FROM term_data td LEFT JOIN term_hierarchy th USING(tid) WHERE td.vid = :vid ORDER BY th.parent, td.weight");
  }

  public function execute() {
    // Save Építészek -> vezető tervezők
    $this->saveTermsFromVocab(13, 12);

    // Save Helyszín
    $this->saveTermsFromVocab(27, 6);

    // Save Főtéma
    $this->saveTermsFromVocab(2, 2);

    // Save Fókusztéma
    $this->saveTermsFromVocab(8, 3);

    // Save Dosszié
    $this->saveTermsFromVocab(7, 5);

    // Save Szerzők
    $this->saveTermsFromVocab(14, 15);
  }

  /**
   * Save term from the old DB to the new.
   *
   * @param int $oldVid
   *   The vid from the old DB.
   * @param int $newVid
   *   The vid in the new DB.
   */
  private function saveTermsFromVocab($oldVid, $newVid) {
    $termMap = array();
    $this->getOldTerms->execute(array(':vid' => $oldVid));
    $result = $this->getOldTerms->fetchAll(PDO::FETCH_ASSOC);

    $counter = 0;
    foreach ($result as $row) {
      $tidBackup = $row['tid'];

      unset($row['tid']);
      $row['vid'] = $newVid;
      $row['parent'] = isset($termMap[$row['parent']]) ? $termMap[$row['parent']] : 0;
      $row = (object)($row);
      taxonomy_term_save($row);

      $termMap[$tidBackup] = $row->tid;

      $this->dbhConnection->query("INSERT INTO terms VALUES ($tidBackup, $row->tid)");

      echo $counter++ . PHP_EOL;
    }
  }

  public function deleteAll() {
    // Delete from Drupal
    $query = db_select('taxonomy_term_data', 'td');
    $query
      ->condition('td.vid', array(12, 6, 2, 3, 5, 15), 'IN')
      ->fields('td', array('tid'));
    $result = $query->execute()->fetchAll();

    $counter = 0;
    foreach ($result as $term) {
      taxonomy_term_delete($term->tid);
      echo $counter++ . PHP_EOL;
    }

    // Delete from connection DB.
    $this->dbhConnection->exec('TRUNCATE TABLE terms');

  }
}