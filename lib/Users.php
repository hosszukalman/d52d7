<?php
/**
 * @todo profile fields
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
    module_load_include('inc', 'entity', 'includes/entity.controller');
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
      foreach ($profileValues as $value) {
        if ($value['value']) {
          switch ($value['fid']) {
            case '9':
              // Telejs nev
              $user->field_full_name[LANGUAGE_NONE][0]['value'] = $value['value'];
              break;

            case '6':
              // Telefonszam

              $user->field_telephone_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_telephone_set', 'field_telephone', $value['value'], $user);
              break;

            case '3':
              // Skype

              $user->field_skype_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_skype_set', 'field_skype', $value['value'], $user);
              break;

            case '5':
              // ICQ

              $user->field_icq_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_icq_set', 'field_icq', $value['value'], $user);
              break;

            case '4':
              // MSN
              $user->field_msn_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_msn_set', 'field_msn', $value['value'], $user);
              break;

            case '2':
              // Weboldal
              $user->field_website_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_website_set', 'field_website', $value['value'], $user);
              break;

            case '8':
              // Foglalkozas
              $user->field_profession_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_profession_set', 'field_profession', $value['value'], $user);
              break;

            case '12':
              // Munkahely
              $user->field_jobs_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_jobs_set', 'field_jobs', $value['value'], $user);
              break;

            case '7':
              // Tartozkodasi hely
              $user->field_residence_set[LANGUAGE_NONE][0]['value'] = $this->saveFieldCollectionEntity('field_residence_set', 'field_residence', $value['value'], $user);
              break;
          }
        }
      }


      $newAccount = user_save($user, array());

      // Image
      if ($oldUser['picture']) {
        echo $oldUser['name'] . ' - ' . $oldUser['picture'] . PHP_EOL;
        $profileImage = media_parse_to_file('sites/default/' . $oldUser['picture']);
//        $profileImage = media_parse_to_file('sites/default/files/tmpimages/' . rand(1, 16) . '.jpeg');
        $user->picture = $profileImage;
        user_save($user, array());
      }


      // Users can use them old password.
      $this->storeOldPassword($newAccount->uid, $oldUser['pass']);

      $this->dbhConnection->query("INSERT INTO users VALUES ({$oldUser['uid']}, {$newAccount->uid})");

      echo 'Old UID:' . $oldUser['uid'] . ' New UID:' . $newAccount->uid . ' - ';
      echo $counter++ . PHP_EOL;

      if ($counter >= 20) {
        exit;
      }
    }
  }

  protected function saveFieldCollectionEntity($fieldName, $bundle, $value, $user) {
    $values = array(
      'field_name' => $fieldName,
      $bundle => array(
        LANGUAGE_NONE => array(array('value' => $value)),
      ),
      'field_public' => array(
        LANGUAGE_NONE => array(array('value' => 1)),
      ),
    );
    $entity = entity_create('field_collection_item', $values);
    $entity->setHostEntity('user', $user);
    $entity->save();

    return $entity->item_id;
  }

  private function storeOldPassword($newUid, $oldHash) {
    require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');
    //  Hash again all current hashed passwords.
    $has_rows = FALSE;
    $hash_count_log2 = 11;
    $has_rows = TRUE;
    $newHash = user_hash_password($oldHash, $hash_count_log2);
    if ($newHash) {
      // Indicate an updated password.
      $newHash  = 'U' . $newHash;
      db_update('users')
        ->fields(array('pass' => $newHash))
        ->condition('uid', $newUid)
        ->execute();
    }
  }
}