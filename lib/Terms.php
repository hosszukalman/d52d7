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

    $this->getOldTerms = $this->dbhImport->prepare("SELECT td.* FROM term_data td WHERE td.vid = :vid");
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