<?php

class Competition extends Nodes {

  function __construct() {
    parent::__construct();
    $this->type = 'palyazat';

    $this->prepareTerms();
    $this->prepareAttachmentQuery();
  }

  public function deleteAll() {
    parent::deleteAll();
  }

  public function execute() {

    $counter = 0;

    foreach ($this->dbhImport->query("SELECT n.*, cfl.field_lers_value, cfb.field_bevezet_value, ctp.field_beadsi_hatrid_value FROM node n
      LEFT JOIN content_field_lers cfl USING(vid)
      LEFT JOIN content_field_bevezet cfb USING(vid)
      LEFT JOIN content_type_palyazat ctp USING(vid)
      WHERE n.type = 'palyazat' ORDER BY n.created", PDO::FETCH_ASSOC) as $oldContent) {

      // New node objec
      $node = new stdClass();

      // Base information
      $node->title = $oldContent['title'];
      $node->type = 'palyazat';
      $node->language = LANGUAGE_NONE;
      $node->status = $oldContent['status'];
      $node->comment = $oldContent['comment'];
      $node->promote = $oldContent['promote'];
      $node->sticky = $oldContent['sticky'];
      $node->created = $oldContent['created']; // Not working

      // Fields
      $node->body[LANGUAGE_NONE][0]['value'] = $this->galleryFilter($oldContent['field_lers_value']);
      $node->body[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $summary = $oldContent['field_bevezet_value'];
      $mainImageUrl = $this->mainImage($summary);

      $node->field_hun_summory[LANGUAGE_NONE][0]['value'] = $summary;
      $node->field_hun_summory[LANGUAGE_NONE][0]['format'] = 'wysiwyg';

      $node->field_deadline[LANGUAGE_NONE][0]['value'] = $oldContent['field_beadsi_hatrid_value'];

      if ($mainImageUrl && file_exists($mainImageUrl)) {
        $mainImageFile = media_parse_to_file($mainImageUrl);
        $node->field_leading_picture[LANGUAGE_NONE][0] = (array)($mainImageFile);
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

          case 27:
            // Helyszín
            $node->field_term_location[LANGUAGE_NONE][] = array(
              'tid' => $this->termMap[$term['tid']],
            );
            break;
        }
      }

      node_save($node);

      $this->saveNodeToConnectionTable($oldContent['nid'], $node->nid);

      echo 'Old NID:' . $oldContent['nid'] . ' New NID:' . $node->nid . ' - ';
      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }
  }

}