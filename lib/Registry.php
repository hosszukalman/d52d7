<?php
class Registry {
  private static $vars = array();

  public static function set($name, $value) {
    if  (isset(self::$vars[$name])) {
      throw new Exception('Property ' . $name . ' is already exist.');
    }

    self::$vars[$name] = $value;
  }

  public static function get($name) {
    if (!isset(self::$vars[$name])) {
      throw new Exception('Property ' . $name . ' is not exists.');
    }

    return self::$vars[$name];
  }

  public static function remove($name) {
    if (!isset(self::$vars['name'])) {
      throw new Exception('Property ' . $name . ' is not exists.');
    }

    unset(self::$vars[$name]);
  }
}