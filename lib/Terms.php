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

    $this->getOldTerms = $this->dbhImport->prepare("SELECT td.*, th.parent FROM term_data td LEFT JOIN term_hierarchy th USING(tid) WHERE td.vid = :vid");
  }

  public function execute() {
    ;
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
    $this->getOldTerms->execute(array(':vid' => $oldVid));
    $result = $this->getOldTerms->fetchAll(PDO::FETCH_ASSOC);

    $counter = 0;
    foreach ($result as $row) {
      unset($row['tid']);
      $row['vid'] = $newVid;
      $row = (object)($row);
      taxonomy_term_save($row);

      echo $counter++ . PHP_EOL;
    }
  }

  public function deleteAll() {
    echo 'Delete terms from DB';
  }
}