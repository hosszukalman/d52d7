<?php
/**
 * Node import methods.
 */
class Nodes extends Importer {

  public function deleteAll() {

  }

  public function execute() {

  }

  protected function saveNodeToConnectionTable($oldNid, $newNid) {
    $this->dbhConnection->query("INSERT INTO nodes VALUES ($oldNid, $newNid)");
  }
}