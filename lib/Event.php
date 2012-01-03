<?php

class Event extends Nodes {

  function __construct() {
    parent::__construct();

    $this->prepareTerms('esemeny');
    $this->prepareAttachmentQuery();
  }

  public function deleteAll() {

  }

  public function execute() {

  }

}