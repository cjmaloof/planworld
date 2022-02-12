<?php
$_base = dirname(__FILE__) . '/../';
require_once($_base . 'config.php');

class DBUtils {
/**
 * Establish a database connection.
 */
  static function _connect() {
    static $dbh;
    if (!isset($dbh)) {
      try {
        $dbh = new PDO(PW_DB_TYPE . ':host=' . PW_DB_HOST . ';dbname=' . PW_DB_NAME,  PW_DB_USER, PW_DB_PASS);
        $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      } catch(PDOException $nodbh) {
        echo "Connection problem: $nodbh";
        return false;
      }
    }
    return $dbh;
  }

  static function getNextUserId($dbh) {
    $query = "SELECT 1 + ifnull(max(id), 0) AS id from users";
    $result = $dbh->query($query);
    return $result->fetch()['id'];
  }

  static function insertUser($dbh, $uid, $remote) {
    $id = DBUtils::getNextUserId($dbh);
    $query = $dbh->prepare("INSERT INTO users (id, username, remote, first_login) VALUES (:nextId, :uid, :remote, :time)");
    $queryArray = array('nextId' => $id, 'uid' => $uid, 'remote' => $remote, 'time' => time());
    $query->execute($queryArray);
    return (int) $id;
  }
  
  static function userExists($dbh, $uid) {
    if (is_int($uid)) {
      $query = $dbh->prepare("SELECT COUNT(id) AS count FROM users WHERE id=:uid");
    } else {
      $query = $dbh->prepare("SELECT COUNT(id) AS count FROM users WHERE username=:uid");
    }
    $queryArray = array('uid' => $uid);
    $query->execute($queryArray);
    return $query->fetch()['count'] == 1;
  }
  
  static function isRemoteUser($dbh, $uid) {
    if (is_int($uid)) {
      $query = $dbh->prepare("SELECT remote FROM users WHERE id=:uid");
    } else if (is_string($uid)) {
      $query = $dbh->prepare("SELECT remote FROM users WHERE username=:uid");
    }
    $queryArray = array('uid' => $uid);
    $query->execute($queryArray);
    return $query->fetch()['remote'] == 'Y';
  }
  
  static function getUserRow($dbh, $user) {
    if (isset($user->username)) {
      $query = $dbh->prepare("SELECT * FROM users WHERE username=:uid");
      $queryArray = array('uid' => $user->username);
    } else if (isset($user->userID)) {
      $query = $dbh->prepare("SELECT * FROM users WHERE id=:uid");
      $queryArray = array('uid' => $user->userID);
    } else {
      return false;
    }
    
    $query->execute($queryArray);
    return $query->fetch();
  }
  
}
?>