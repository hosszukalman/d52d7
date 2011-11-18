<?php

class Galleries extends Importer {

  /**
   * @var PDOStatement
   */
  private $getImages;

  function __construct() {
    parent::__construct();

    $this->getImages = $this->dbhImport->prepare("SELECT igc.*, i.image FROM imagelist_gallery_cache igc
      INNER JOIN imagelist i USING(img_id)
      WHERE igc.nid = :gallery_nid");
  }

  public function deleteAll() {
    // Delete from Drupal
    $query = db_select('node', 'n');
    $query
      ->condition('n.type', 'media_gallery')
      ->fields('n', array('nid'));
    $result = $query->execute()->fetchAll();

    $counter = 0;
    foreach ($result as $node) {
      node_delete($node->nid);
      echo $counter++ . PHP_EOL;
    }

    // Delete from connection DB.
    $this->dbhConnection->exec('TRUNCATE TABLE galleries');
    $this->dbhConnection->exec('TRUNCATE TABLE gallery_images');
  }

  public function execute() {
    $this->storeGalleryNodes();
  }

  private function storeGalleryNodes() {
    $counter = 0;
    foreach ($this->dbhImport->query("SELECT n.* FROM imagelist_gallery_cache igc
      INNER JOIN node n USING(nid) GROUP BY igc.nid ORDER BY igc.nid", PDO::FETCH_ASSOC) as $gallery) {

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

      // Get images
      $this->getImages->execute(array(':gallery_nid' => $gallery['nid']));
      $images = $this->getImages->fetchAll(PDO::FETCH_ASSOC);

      // Store images to conenction DB
      $store_images = array();

      foreach ($images as $image) {
//        $file = media_parse_to_file('sites/default/' . $gallery['image']);
        $file = media_parse_to_file('sites/default/files/tmpimages/' . rand(1, 16) . '.jpeg');
        $node->media_gallery_media[LANGUAGE_NONE][] = array(
          'fid' => $file->fid,
          'title' => $image['title'],
          'data' => '',
        );
        $store_images[] = array(
          'old_image_id' => $image['img_id'],
          'fid' => $file->fid,
        );
      }

      node_save($node);

      // Store node
      $this->dbhConnection->query("INSERT INTO galleries VALUES ({$gallery['nid']}, {$node->nid})");

      // Sore images
      foreach ($store_images as $stored) {
        $this->dbhConnection->query("INSERT INTO gallery_images VALUES ({$stored['old_image_id']}, {$stored['fid']}, {$node->nid})");
      }

      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }
  }

}