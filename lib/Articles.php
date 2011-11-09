<?php
/**
 * Import terms
 */
class Articles extends Nodes {

  function __construct() {
    parent::__construct();

  }

  public function deleteAll() {

  }

  public function execute() {
    foreach ($this->dbhImport->query("SELECT n.*, cft.field_trzs_value, cfb.field_bevezet_value, cfea.field_eng_abstract_value FROM node n
      LEFT JOIN content_field_trzs cft USING(vid)
      LEFT JOIN content_field_bevezet cfb USING(vid)
      LEFT JOIN content_field_eng_abstract cfea USING(vid)
      WHERE n.type = 'normal_tartalom' ORDER BY n.created", PDO::FETCH_ASSOC) as $oldContent) {

    }
  }
}