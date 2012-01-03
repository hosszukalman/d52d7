<?php
/**
 * Node import methods.
 */
class Nodes extends Importer {

  protected $termMap;

  /**
   * @var PDOStatement
   */
  protected $getNewTerms;

  /**
   * @var PDOStatement
   */
  protected $getOldTerms;

  /**
   * @var PDOStatement
   */
  private $getAttachemtns;

  public function deleteAll() {

  }

  public function execute() {

  }

  protected function saveNodeToConnectionTable($oldNid, $newNid) {
    $this->dbhConnection->query("INSERT INTO nodes VALUES ($oldNid, $newNid)");
  }
}