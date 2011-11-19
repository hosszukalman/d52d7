<?php
/**
 * Import terms
 *
 * @todo attachment -> field
 */
class Articles extends Nodes {

  private $termMap;

  /**
   * @var PDOStatement
   */
  private $getNewTerms;

  /**
   * @var PDOStatement
   */
  private $getOldTerms;

  /**
   * @var PDOStatement
   */
  private $getRelatedGallery;

  /**
   * @var PDOStatement
   */
  private $getNewImage;

  /**
   * @var PDOStatement
   */
  private $getImageData;

  function __construct() {
    parent::__construct();

    $this->getNewTerms = $this->dbhConnection->prepare("SELECT t.new_tid FROM terms t WHERE t.old_tid = :old_tid");
    $this->getRelatedGallery = $this->dbhConnection->prepare("SELECT g.gallery_nid FROM galleries g WHERE g.old_nid = :old_nid");
    $this->getNewImage = $this->dbhConnection->prepare("SELECT gi.* FROM gallery_images gi WHERE gi.old_image_id = :old_image_id");
    $this->getImageData = $this->dbhImport->prepare("SELECT i.* FROM imagelist i WHERE img_id = :img_id");
    $this->getOldTerms = $this->dbhImport->prepare("SELECT td.* FROM term_node tn
      INNER JOIN term_data td USING(tid)
      WHERE tn.nid = :nid AND td.vid IN (13, 2, 8, 7, 14)");

    foreach ($this->dbhImport->query("SELECT td.* FROM node n
      RIGHT JOIN term_node tn USING(nid)
      RIGHT JOIN term_data td ON (tn.tid = td.tid)
      WHERE n.type = 'normal_tartalom' AND td.vid IN (13, 2, 8, 7, 14)
      GROUP BY td.tid ORDER BY n.created", PDO::FETCH_ASSOC) as $term) {

      $this->getNewTerms->execute(array(':old_tid' => $term['tid']));
      $result = $this->getNewTerms->fetch(PDO::FETCH_ASSOC);
      if (!empty($result)) {
//        $this->termMap[$term['vid']][$term['tid']] = $result['new_tid'];
        $this->termMap[$term['tid']] = $result['new_tid'];
      }
    }
  }

  public function deleteAll() {
    // Delete from Drupal
    $query = db_select('node', 'n');
    $query
      ->condition('n.type', 'cikk')
      ->fields('n', array('nid'));
    $result = $query->execute()->fetchAll();

    $counter = 0;
    foreach ($result as $node) {
      node_delete($node->nid);
      echo $counter++ . PHP_EOL;
    }

    // Delete from connection DB.
    $this->dbhConnection->exec('TRUNCATE TABLE nodes');

  }

  public function execute() {
    $counter = 0;
    foreach ($this->dbhImport->query("SELECT n.*, cft.field_trzs_value, cfb.field_bevezet_value, cfea.field_eng_abstract_value FROM node n
      LEFT JOIN content_field_trzs cft USING(vid)
      LEFT JOIN content_field_bevezet cfb USING(vid)
      LEFT JOIN content_field_eng_abstract cfea USING(vid)
      WHERE n.type = 'normal_tartalom' ORDER BY n.created", PDO::FETCH_ASSOC) as $oldContent) {

      // New node objec
      $node = new stdClass();

      // Base information
      $node->title = $oldContent['title'];
      $node->type = 'cikk';
      $node->language = LANGUAGE_NONE;
      $node->status = $oldContent['status'];
      $node->comment = $oldContent['comment'];
      $node->promote = $oldContent['promote'];
      $node->sticky = $oldContent['sticky'];
      $node->created = $oldContent['created']; // Not working

      // Fields
      $node->body[LANGUAGE_NONE][0]['value'] = $this->galleryFilter($oldContent['field_trzs_value']);
      $node->body[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $summary = $oldContent['field_bevezet_value'];
      $mainImageUrl = $this->mainImage($summary);

      $node->field_hun_summory[LANGUAGE_NONE][0]['value'] = $summary;
      $node->field_hun_summory[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $node->field_eng_summory[LANGUAGE_NONE][0]['value'] = $oldContent['field_eng_abstract_value'];
      $node->field_eng_summory[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      if ($mainImageUrl) {
        $mainImageFile = media_parse_to_file($mainImageUrl);
        $node->field_leading_picture[LANGUAGE_NONE][0] = (array)($mainImageFile);
      }

      // Related gallery
      $this->getRelatedGallery->execute(array(':old_nid' => $oldContent['nid']));
      $related_gallery = $this->getRelatedGallery->fetch(PDO::FETCH_ASSOC);
      if ($related_gallery) {
        $node->field_related_gallery[LANGUAGE_NONE][0]['nid'] = $related_gallery['gallery_nid'];
      }

      // Taxonomy terms
      $this->getOldTerms->execute(array(':nid' => $oldContent['nid']));
      $terms = $this->getOldTerms->fetchAll(PDO::FETCH_ASSOC);

      foreach ($terms as $term) {
        switch ($term['vid']) {
          case 14:
            // Szerzők
            $node->field_authors_term[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;

          case 7:
            // Dosszié
            $node->field_dossier[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;

          case 2:
            // Főtéma
            $node->field_primary_article[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;

          case 8:
            // Fókusztéma
            $node->field_focus_article[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;

          case 13:
            // Építészek
            $node->field_lead_architect_tag[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;
        }
      }

      node_save($node);

      $this->saveNodeToConnectionTable($oldContent['nid'], $node->nid);

      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }
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
//        return 'sites/default/' . $imageData['image'];
        return 'sites/default/files/tmpimages/' . rand(1, 16) . '.jpeg';
      }
    }

    return FALSE;
  }
}