<?php
/**
 *
 */
// Add remote addr to skip errors
$_SERVER['REMOTE_ADDR'] = 'd52d7';

chdir('/Users/kalmanhosszu/work/clients/[epi]epiteszforum/facelift/www/epiteszforum');

// Drupal start
define('DRUPAL_ROOT', getcwd());

require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

abstract class Drupal {

}