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

      // User base informations
      $user = new stdClass();
      $user->name = $oldUser['name'];
      $user->mail = $oldUser['mail'];
      $user->signature = $oldUser['signature'];
      $user->signature_format = 'filtered_html';
      $user->created = $oldUser['created'];
      $user->access = $oldUser['access'];
      $user->login = $oldUser['login'];
      $user->status = $oldUser['status'];

      $newAccount = user_save($user, array());

      $this->dbhConnection->query("INSERT INTO users VALUES ({$oldUser['uid']}, {$newAccount->uid})");

      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }

  }
}