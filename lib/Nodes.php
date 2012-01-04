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
  protected $getAttachemtns;

  /**
   * @var PDOStatement
   */
  protected $getNewImage;

  /**
   * @var PDOStatement
   */
  protected $getImageData;

  public function __construct() {
    parent::__construct();

    $this->getNewImage = $this->dbhConnection->prepare("SELECT gi.* FROM gallery_images gi WHERE gi.old_image_id = :old_image_id");
    $this->getImageData = $this->dbhImport->prepare("SELECT i.* FROM imagelist i WHERE img_id = :img_id");
  }

  public function deleteAll() {

  }

  public function execute() {

  }

  protected function saveNodeToConnectionTable($oldNid, $newNid) {
    $this->dbhConnection->query("INSERT INTO nodes VALUES ($oldNid, $newNid)");
  }

  protected function prepareTerms($type) {
    $this->getNewTerms = $this->dbhConnection->prepare("SELECT t.new_tid FROM terms t WHERE t.old_tid = :old_tid");
    $this->getOldTerms = $this->dbhImport->prepare("SELECT td.* FROM term_node tn
      INNER JOIN term_data td USING(tid)
      WHERE tn.nid = :nid");
//      WHERE tn.nid = :nid AND td.vid IN (13, 2, 8, 7, 14)");

    foreach ($this->dbhImport->query("SELECT td.* FROM node n
      RIGHT JOIN term_node tn USING(nid)
      RIGHT JOIN term_data td ON (tn.tid = td.tid)
      WHERE n.type = '$type'
      GROUP BY td.tid ORDER BY n.created", PDO::FETCH_ASSOC) as $term) {
//      WHERE n.type = '$type' AND td.vid IN (13, 2, 8, 7, 14)

      $this->getNewTerms->execute(array(':old_tid' => $term['tid']));
      $result = $this->getNewTerms->fetch(PDO::FETCH_ASSOC);
      if (!empty($result)) {
        $this->termMap[$term['tid']] = $result['new_tid'];
      }
    }
  }

  protected function prepareAttachmentQuery() {
    $this->getAttachemtns = $this->dbhImport->prepare("SELECT f.* FROM files f WHERE f.nid = :old_nid");
  }

  protected function galleryFilter($text) {
    // Find and replace arrays.
    $search = array();
    $replace = array();

    // Regular expression
    $finds = preg_match_all('/\[imagelist\|imgid=([0-9]*)[^\]]*\]/', $text, $matches);

    if ($finds) {
      foreach ($matches[0] as $id => $fullPattern) {
        $newPattern = '';

        // Select the the image data
        $this->getNewImage->execute(array(':old_image_id' => $matches[1][$id]));
        $newImage = $this->getNewImage->fetch(PDO::FETCH_ASSOC);

        if ($newImage) {
          $newPattern = "[mg_picker:{$newImage['gallery_nid']} fid:{$newImage['fid']}]";
        }

        // Replace with data (clear if the old image is not exist in the new DB)
        $search[] = $fullPattern;
        $replace[] = $newPattern;
      }
    }

    // Replace the finded patterns with the links
    return str_replace($search, $replace, $text);
  }

  protected function mainImage(&$text) {
    // Regular expression
    $finds = preg_match('/\[imagelist\|imgid=([0-9]*)[^\]]*\]/', $text, $matches);

    if ($finds) {
      $text = str_replace($matches[0], '', $text);

      // Select the the image data
      $this->getImageData->execute(array(':img_id' => $matches[1]));
      $imageData = $this->getImageData->fetch(PDO::FETCH_ASSOC);

      if ($imageData) {
        return 'sites/default/' . $imageData['image'];
//        return 'sites/default/files/tmpimages/' . rand(1, 16) . '.jpeg';
      }
    }

    return FALSE;
  }
}