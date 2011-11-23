<?php

class Users extends Importer {

  function __construct() {
    parent::__construct();
  }

  public function deleteAll() {

  }

  public function execute() {
    $counter = 0;
    foreach ($this->dbhImport->query("SELECT u.* FROM users u WHERE u.uid NOT IN (0, 1) ORDER BY u.uid", PDO::FETCH_ASSOC) as $oldUser) {

      

      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }

  }
}