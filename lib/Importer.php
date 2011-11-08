<?php
abstract class Importer extends Drupal {
  /**
   * @var PDO
   */
  protected $dbhImport;

  public function __construct() {
    // DBH
    $this->dbhImport = Registry::get('dbhImport');
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