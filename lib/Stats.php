<?php
/**
 * $Id: Stats.php,v 1.4.2.2 2003/11/02 16:12:35 seth Exp $
 * Statistics utility class (Stats::)
 */

require_once($_base . 'lib/DBUtils.php');
require_once($_base . 'lib/User.php');

/**
 * Statistics functions
 */
class Stats {
  /**
   * void Stats::addHit()
   * Increment the number of site-wide hits
   */
  static function addHit () {
    $dbh = DBUtils::_connect();

    $dbh->query('UPDATE LOW_PRIORITY globalstats SET totalhits=totalhits + 1');
  }

  static function executeCountQuery($query) {
    $dbh = DBUtils::_connect();
    $result = $dbh->query($query);
    if ($result) {
      $row = $result->fetch();
      return (int) $row['count'];
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * int Stats::getNumHits ()
   * Returns the number of sitewide page views
   */
  static function getNumHits () {
    return Stats::executeCountQuery("SELECT totalhits as count FROM globalstats");
  }
  
  /**
   * int Stats::getNumUsers ()
   * Returns the number of users that this system knows about
   */
  static function getNumUsers () {
    return Stats::executeCountQuery("SELECT COUNT(*) as count FROM users");
  }

  /**
   * int Stats::getTotalPlanViews ()
   * Returns the number of site-wide plan views
   */
  static function getTotalPlanViews () {
    return Stats::executeCountQuery("SELECT SUM(views) AS count FROM users");
  }

  /**
   * int Stats::getNumViews ($user)
   * Returns the number of views that $user has had
   */
  static function getNumViews (&$user) {
    $dbh = DBUtils::_connect();

    $query = $dbh->prepare("SELECT views FROM users WHERE id=:id");
    $queryArray = array("id" => $user->getUserID());
    $query->execute($queryArray);
    $row = $query->fetch();
    if ($row) {
      return (int) $row['views'];
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * int Stats::getNumLoggedIn ($since)
   * Returns the number of people who have logged in (since $since)
   */
  static function getNumLoggedIn ($since=null) {
    if (isset($since)) {
      $query = "SELECT COUNT(*) as count FROM users WHERE remote='N' AND last_login > (" . (time() - $since) . ")";
    } else {
      $query = "SELECT COUNT(*) as count FROM users WHERE remote='N' AND last_login > 0";
    }
    return Stats::executeCountQuery($query);
  }

  /**
   * int Stats::getNumPlans ()
   * Returns the number of plans (update in the past $since seconds)
   */
  static function getNumPlans ($since=null) {
    if (isset($since)) {
      $query = "SELECT COUNT(*) as count FROM users WHERE remote='N' AND last_update > (" . (time() - $since) . ")";
    } else {
      $query = "SELECT COUNT(*) as count FROM plans";
    }
    return Stats::executeCountQuery($query);
  }

  /**
   * int Stats::getNumSnitchRegistered ()
   * Returns the number of snitch-registered users
   */
  static function getNumSnitchRegistered () {
    return Stats::executeCountQuery("SELECT COUNT(*) as count FROM users WHERE remote='N' AND snitch='Y'");
  }

  /**
   * int Stats::getNumCookies ($contrib)
   * Returns the number of cookies (user-contributed, if $contrib)
   */
  static function getNumCookies ($contrib=false) {
    if ($contrib) {
      $query = "SELECT count(*) as count FROM cookies WHERE s_uid != 0 AND approved='Y'";
    } else {
      $query = "SELECT count(*) as count FROM cookies WHERE approved='Y'";
    }
    return Stats::executeCountQuery($query);
  }

  /**
   * int Stats::getNumArchiveEntries ()
   * Returns the number of archive entries.
   */
  static function getNumArchiveEntries () {
    return Stats::executeCountQuery("SELECT COUNT(*) AS count FROM archive");
  }
}

?>
