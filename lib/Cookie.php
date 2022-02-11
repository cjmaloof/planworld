<?php
/**
 * $Id: Cookie.php,v 1.1.2.2 2003/11/02 16:12:35 seth Exp $
 * Cookie functions.
 */

require_once($_base . 'lib/Planworld.php');

class Cookie {

  /**
   * Adds cookies to the jar.
   */
  static function addCookie ($content, $author, &$submittor, $approved=false) {
    $id = Cookie::getNextCookieId();

    $query = "INSERT INTO cookies (id, quote, author, s_uid, approved) VALUES ({$id}, '" . addslashes($content) . "', '" . addslashes($author) . "', ";

    if (is_object($submittor)) {
      $query .= $submittor->getUserID();
    } else if (is_int($submittor)) {
      $query .= $submittor;
    } else if (is_string($submittor)) {
      $query .= Planworld::nameToID($submittor);
    }

    if ($approved) {
      $query .= ", 'Y')";
    } else {
      $query .= ", 'N')";
    }

    Planworld::query($query);
  }
  
  static function getNextCookieId() {
    $dbh = DBUtils::_connect();
    $query = "SELECT 1 + ifnull(max(id), 0) AS id from cookies";
    $result = $dbh->query($query);
    return (int) $result->fetch()['id'];
  }

  /**
   * Modifies cookies already in the jar.
   */
  static function edit ($id, $content, $author, &$submittor, $approved=false) {
    $dbh = DBUtils::_connect();

    $query = "UPDATE cookies SET quote='" . addslashes($content) . "', author='" . addslashes($author) . "', s_uid=";

    if (empty($submittor)) {
      $query .= '0';
    } else if (is_object($submittor)) {
      $query .= $submittor->getUserID();
    } else if (is_int($submittor)) {
      $query .= $submittor;
    } else if (is_string($submittor)) {
      $query .= Planworld::nameToID($submittor);
    }

    if ($approved) {
      $query .= ", approved='Y'";
    } else {
      $query .= ", approved='N'";
    }

    $query .= " WHERE id={$id}";

    Planworld::query($query);
  }

  /**
   * array Cookie::getRandomCookie ()
   * Returns a random cookie from the (approved) selection
   */
  static function getRandomCookie () {
    $dbh = DBUtils::_connect();

    $query = "SELECT cookies.id, quote, author, username FROM cookies LEFT JOIN users ON cookies.s_uid=users.id WHERE approved='Y' ORDER BY " . PW_RANDOM_FN;

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $row = $result->fetch();
      return array('id' => (int) $row['id'],
                   'quote' => $row['quote'],
                   'author' => $row['author'],
                   'credit' => $row['username']);
    } else {
      return PLANWORLD_ERROR;
    } 
  }

  /**
   * array Cookie::getPendingCookies ()
   * Returns all cookies that have not yet been approved.
   */
  static function getPendingCookies () {
    $dbh = DBUtils::_connect();

    $query = "SELECT cookies.id, quote, author, username FROM cookies LEFT JOIN users ON cookies.s_uid=users.id WHERE approved='N' ORDER BY author";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $return = Array();
      while ($row = $result->fetch()) {
        $return[] = array('id' => $row['id'],
                          'quote' => $row['quote'],
                          'author' => $row['author'],
                          'credit' => $row['username']);
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * void Cookie::approve ()
   * Approves cookies whose ids have been passed.
   */
  static function approve ($list) {
    if (empty($list)) {
      return;
    } else {
      if (is_array($list)) {
        $query = "UPDATE cookies SET approved='Y' WHERE";
        $query .= " id=" . $list[0]; 
        for ($i=1;$i<count($list);$i++) {
          $query .= " OR id=" . $list[$i];
        }
      } else {
        $query = "UPDATE cookies SET approved='Y' WHERE id={$list}";
      }
      Planworld::query($query);
    }
  }

  /**
   * void Cookie::remove ()
   * Remove cookies whose ids have been passed.
   */
  static function remove ($list) {
    if (empty($list)) {
      return;
    } else {
      if (is_array($list)) {
        $query = "DELETE FROM cookies WHERE";
        $query .= " id=" . $list[0]; 
        for ($i=1;$i<count($list);$i++) {
          $query .= " OR id=" . $list[$i];
        }
      } else {
        $query = "DELETE FROM cookies WHERE id={$list}";
      }
      Planworld::query($query);
    }
  }

  static function get ($id) {
    $dbh = DBUtils::_connect();

    $query = "SELECT cookies.id, quote, author, username, approved FROM cookies LEFT JOIN users ON cookies.s_uid=users.id WHERE cookies.id={$id}";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      if ($row = $result->fetch()) {
        return array('id' => $row['id'],
                     'quote' => $row['quote'],
                     'author' => $row['author'],
                     'credit' => $row['username'],
                     'approved' => ($row['approved'] == 'Y') ? true : false);
      } else {
        return false;
      }
    } else {
      return PLANWORLD_ERROR;
    } 
  }
}
?>
