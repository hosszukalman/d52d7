<?php
abstract class Importer extends Drupal {
  /**
   * @var PDO
   */
  protected $dbhImport;

  /**
   * @var PDO
   */
  protected $dbhConnection;

  public function __construct() {
    // DBH
    $this->dbhImport = Registry::get('dbhImport');
    $this->dbhConnection = Registry::get('dbhConnection');
  }

  /**
   * Run full import
   */
  public abstract function execute();

  /**
   * Delete all imported data
   */
  public abstract function deleteAll();
}