<?php
/**
 * Import terms
 *
 * @todo finalize filepaths
 */
class Articles extends Nodes {

  /**
   * @var PDOStatement
   */
  private $getRelatedGallery;

  function __construct() {
    parent::__construct();

    $this->prepareAttachmentQuery();

    $this->getRelatedGallery = $this->dbhConnection->prepare("SELECT g.gallery_nid FROM galleries g WHERE g.old_nid = :old_nid");

    $this->prepareTerms('normal_tartalom');
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
    // FUcked: 6443
    foreach ($this->dbhImport->query("SELECT n.*, cft.field_trzs_value, cfb.field_bevezet_value, cfea.field_eng_abstract_value FROM node n
      LEFT JOIN content_field_trzs cft USING(vid)
      LEFT JOIN content_field_bevezet cfb USING(vid)
      LEFT JOIN content_field_eng_abstract cfea USING(vid)
      WHERE n.type = 'normal_tartalom' ORDER BY n.created LIMIT 4296 , 10000", PDO::FETCH_ASSOC) as $oldContent) {

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

      if ($mainImageUrl && file_exists($mainImageUrl)) {
        $mainImageFile = media_parse_to_file($mainImageUrl);
        $node->field_leading_picture[LANGUAGE_NONE][0] = (array)($mainImageFile);
      }

      // Related gallery
      $this->getRelatedGallery->execute(array(':old_nid' => $oldContent['nid']));
      $related_gallery = $this->getRelatedGallery->fetch(PDO::FETCH_ASSOC);
      if ($related_gallery) {
        $node->field_related_gallery[LANGUAGE_NONE][0]['nid'] = $related_gallery['gallery_nid'];
      }

      // Attachments
      $this->getAttachemtns->execute(array(':old_nid' => $oldContent['nid']));
      $attachments = $this->getAttachemtns->fetchAll(PDO::FETCH_ASSOC);
      foreach ($attachments as $attachment) {
        if ($attachment['filepath']) {
//          $attachmentFile = media_parse_to_file('sites/default/' . $attachment['filepath']);
          $attachmentFile = media_parse_to_file('sites/default/files/tmpfiles/' . rand(1, 15) . '.txt');
          $attachmentFile->display = 1;
          $node->field_documents[LANGUAGE_NONE][] = (array)($attachmentFile);
        }
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

      echo 'Old NID:' . $oldContent['nid'] . ' New NID:' . $node->nid . ' - ';
      echo $counter++ . PHP_EOL;

//      if ($counter >= 20) {
//        exit;
//      }
    }
  }
}