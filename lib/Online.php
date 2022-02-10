<?php
/**
 * $Id: Online.php,v 1.6.2.2 2003/11/02 16:12:35 seth Exp $
 * Utility class for determining online users.
 */

require_once($_base . 'lib/DBUtils.php');
require_once($_base . 'lib/User.php');
require_once($_base . 'lib/Planworld.php');

class Online {
  /**
   * void Online::clearIdle ()
   * Removes users who have been idle too long
   */
  static function clearIdle () {
    $dbh = DBUtils::_connect();
    $clearBefore = time() - PW_IDLE_TIMEOUT;
    
    $query = $dbh->prepare("DELETE FROM online WHERE last_access < ?");
    $queryArray = array($clearBefore);
    $query->execute($queryArray);
  }

  /**
   * void Online::updateUser (&$user, &$target)
   * Update's $user's status to $target
   */
  static function updateUser (&$user, &$target) {
    $status = is_object($target) ? $target->getUsername() : $target;
    
    $dbh = DBUtils::_connect();
    $query = $dbh->prepare("UPDATE online SET last_access=?, what=? WHERE uid=?");
    $queryArray = array(time(), $status, $user->getUserID());
    $updated = $query->execute($queryArray) && $query->rowCount() > 0;
    return $updated ? true : Online::addUser($user, $target);
  }

  /**
   * void Online::addUser($user, $target)
   * Adds $user to the list of online users (with status $target)
   */
  static function addUser (&$user, $target) {
    $status = is_object($target) ? $target->getUsername() : $target;
    
    $dbh = DBUtils::_connect();
    $query = $dbh->prepare("INSERT INTO online (uid, login, last_access, what) VALUES (?, ?, ?, ?)");
    $queryArray = array($user->getUserID(), time(), time(), $status);
    $query->execute($queryArray);
  }

  /**
   * void Online::removeUser ($user)
   * Removes $user from the list of online users
   */
  function removeUser (&$user) {
    $dbh = DBUtils::_connect();

    $query = $dbh->prepare("DELETE FROM online WHERE uid=?");
    $queryArray = array($user->getUserID());
    $query->execute($queryArray);
  }

  /**
   * array Online::getOnlineUsers ()
   * Returns a list of all users who are currently online.
   */
  function getOnlineUsers () {
    $dbh = DBUtils::_connect();

    $query = "SELECT users.username, online.last_access, online.login, online.what FROM users, online WHERE users.id = online.uid ORDER BY last_access DESC";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return[] = array('name' => $row['username'],
                          'lastAccess' => (int) $row['last_access'],
                          'login' => (int) $row['login'],
                          'what' => $row['what']);
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }
}
?>
