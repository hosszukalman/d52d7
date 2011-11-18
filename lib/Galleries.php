<?php

class Galleries extends Importer {

  function __construct() {
    parent::__construct();
  }

  public function deleteAll() {

  }

  public function execute() {
    $this->storeGalleryNodes();
  }

  private function storeGalleryNodes() {
    $counter = 0;
    foreach ($this->dbhImport->query("SELECT n.* FROM imagelist_gallery_cache igc
      INNER JOIN node n USING(nid) GROUP BY igc.nid", PDO::FETCH_ASSOC) as $gallery) {

      // New node objec
      $node = new stdClass();

      // Base information
      $node->title = $gallery['title'];
      $node->type = 'media_gallery';
      $node->language = LANGUAGE_NONE;
      $node->status = 1;
      $node->comment = 1;
      $node->promote = 0;
      $node->sticky = 0;
      $node->created = $gallery['created']; // Not working

      // Fields
      $node->media_gallery_format[LANGUAGE_NONE][0]['value'] = 'node';
      $node->media_gallery_lightbox_extras[LANGUAGE_NONE][0]['value'] = 0;
      $node->media_gallery_columns[LANGUAGE_NONE][0]['value'] = 4;
      $node->media_gallery_rows[LANGUAGE_NONE][0]['value'] = 3;
      $node->media_gallery_image_info_where[LANGUAGE_NONE][0]['value'] = 'nothing';
      $node->media_gallery_allow_download[LANGUAGE_NONE][0]['value'] = 1;


      node_save($node);

      $this->dbhConnection->query("INSERT INTO galleries VALUES ({$gallery['nid']}, {$node->nid})");

      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }
  }


}