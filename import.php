<?php
/**
 *
 */
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

try {
  // DB connections
  $dsn = 'mysql:dbname=epiteszforum_old;host=localhost';
  $user = 'epiteszforum';
  $password = 'ccMeLHhnTrSBbtrw';
  $dbhImport = new PDO($dsn, $user, $password);

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