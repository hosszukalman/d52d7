<?php
/**
 * @todo profile fields
 * @todo use old password
 * @todo user picture
 */

class Users extends Importer {

  /**
   * @var PDOStatement
   */
  private $getProfileValues;

  function __construct() {
    parent::__construct();

    $this->getProfileValues = $this->dbhImport->prepare("SELECT pv.* FROM profile_values pv WHERE pv.uid = :old_uid");
  }

  public function deleteAll() {
    // Delete from Drupal
    $query = db_select('users', 'u');
    $query
      ->condition('u.uid', array(0, 1), 'NOT IN')
      ->fields('u', array('uid'));
    $result = $query->execute()->fetchAll();

    $counter = 0;
    foreach ($result as $user) {
      user_delete($user->uid);
      echo $counter++ . PHP_EOL;
    }

    // Delete from connection DB.
    $this->dbhConnection->exec('TRUNCATE TABLE users');
  }

  public function execute() {
    $counter = 0;
    foreach ($this->dbhImport->query("SELECT u.* FROM users u WHERE u.uid NOT IN (0, 1) ORDER BY u.uid", PDO::FETCH_ASSOC) as $oldUser) {

      // User base informations
      $user = new stdClass();
      $user->name = $oldUser['name'];
      $user->mail = $oldUser['mail'];
      $user->signature = $oldUser['signature'];
      $user->signature_format = 'filtered_html';
      $user->created = $oldUser['created'];
      $user->access = $oldUser['access'];
      $user->login = $oldUser['login'];
      $user->status = $oldUser['status'];

      // Profile fields
      $this->getProfileValues->execute(array(':old_uid' => $oldUser['uid']));
      $profileValues = $this->getProfileValues->fetchAll(PDO::FETCH_ASSOC);

      // Field colleption not working.
//      foreach ($profileValues as $value) {
//        if ($value['value']) {
//          switch ($value['fid']) {
//            case '9':
//              // Telejs nev
//              $user->field_full_name[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//
//            case '6':
//              // Telefonszam
//              $user->field_telephone_set[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//
//            case '3':
//              // Skype
//              $user->field_skype_set[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//
//            case '5':
//              // ICQ
//              $user->field_icq_set[LANGUAGE_NONE][0]['value'] = (int)$value['value'];
//              break;
//
//            case '4':
//              // MSN
//              $user->field_msn_set[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//
//            case '2':
//              // Weboldal
//              $user->field_website_set[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//
//            case '8':
//              // Foglalkozas
//              $user->field_profession_set[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//
//            case '12':
//              // Munkahely
//              $user->field_jobs_set[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//
//            case '7':
//              // Tartozkodasi hely
//              $user->field_residence_set[LANGUAGE_NONE][0]['value'] = $value['value'];
//              break;
//          }
//        }
//      }


      $newAccount = user_save($user, array());

      $this->dbhConnection->query("INSERT INTO users VALUES ({$oldUser['uid']}, {$newAccount->uid})");

      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }

  }
}