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

  private function saveTermsFromVocab($vid) {
    $this->getOldTerms->execute(array(':vid' => $vid));
    $result = $this->getOldTerms->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
      var_dump($row);
      exit;
    }
  }

  public function deleteAll() {
    echo 'Delete terms from DB';
  }
}