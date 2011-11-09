<?php
/**
 *
 */
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

/**
 * Autoloader function to load classes from lib folder.
 *
 * @param type $class_name
 */
function __autoload($class_name) {
  include 'lib/' . $class_name . '.php';
}

try {
  // DB connections
  $dsn = 'mysql:dbname=epiteszforum_old;host=localhost';
  $user = 'epiteszforum';
  $password = 'ccMeLHhnTrSBbtrw';
  $dbhImport = new PDO($dsn, $user, $password);

  $dsn = 'mysql:dbname=epiteszforum_old2new_conection;host=localhost';
  $user = 'epiteszforum';
  $password = 'ccMeLHhnTrSBbtrw';
  $dbhConnection = new PDO($dsn, $user, $password);

  // Store DB connections to Registry
  Registry::set('dbhImport', $dbhImport);
  Registry::set('dbhConnection', $dbhConnection);

  $dbhImport->exec('SET NAMES "UTF8"');

  if ($_SERVER['argc'] !== 3) {
    throw new Exception('use php import.php [class] [execute|deleteAll]');
  }

  $class = $_SERVER['argv'][1];
  if (!class_exists($class)) {
    throw new Exception($class . ' is not exists!');
  }

  $classRef = new ReflectionClass($class);
  $method = $_SERVER['argv'][2];
  if (!$classRef->hasMethod($method)) {
    throw new Exception($class . '::' . $method . ' is not exists!');
  }

  $importer = new $class;
  $importer->$method();
} catch (Exception $e) {
  echo $e->getMessage() . PHP_EOL;
}