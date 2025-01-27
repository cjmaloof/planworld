<?php
/**
 * $Id: Planworld.php,v 1.29.2.20 2003/11/02 16:12:35 seth Exp $
 * General utility class (Planworld::)
 */

$_base = dirname(__FILE__) . '/../';
require_once($_base . 'config.php');
require_once($_base . 'lib/DBUtils.php');
/** TEMPORARY */
require_once($_base . 'backend/epi-utils.php');

// Return codes
/** @constant PLANWORLD_OK Operation succeeded. */
define('PLANWORLD_OK', 0);

/** @constant PLANWORLD_ERROR Operation failed. */
define('PLANWORLD_ERROR', -1);

/**
 * General utility class for things that don't belong anywhere else
 */
class Planworld {


  /**
   * Calls a remote method via xml-rpc.
   * @param nodeinfo Information on the remote node.
   * @param method Method to call.
   * @param params Parameters to use.
   * @private
   */
  static function _call ($nodeinfo, $method, $params=null) {
    return xu_rpc_http_concise(array('method' => $method,
                                     'args'   => $params, 
                                     'host'   => $nodeinfo['Hostname'], 
                                     'uri'    => $nodeinfo['Path'], 
                                     'port'   => $nodeinfo['Port'], 
                                     'debug'  => 0)); // 0=none, 1=some, 2=more
  }

  /**
   * bool Planworld::isError ($result)
   * return whether a result (code) is an error
   */
  static function isError ($res) {
    if ($res < 0)
      return true;
    else
      return false;
  }
  
  /**
   * int Planworld::addUser ($uid)
   * adds user with name $uid; returns user id
   */
  static function addUser ($uid) {
    if ($uid == '') {
      return false;
    }

    $dbh = DBUtils::_connect();

    if (strstr($uid, '@')) {
      $remote = 'Y';
    } else {
      $remote = 'N';
    }

    $id = DBUtils::insertUser($dbh, $uid, $remote);
    return $id;
  }
  
  /**
   * int Planworld::nameToID ($uid)
   * converts textual $uid to numeric representation
   */
  static function nameToID ($uid) {
    // persistent lookup table
    static $table;

    if (is_string($uid)) {
      $dbh = DBUtils::_connect();
      
      if (isset($table[$uid])) {
        return (int) $table[$uid];
      } else {
        $query = "SELECT id FROM users WHERE username='" . addslashes($uid) . "'";
        
        /* execute the query */
        $result = $dbh->query($query);
        if ($result) {
          $row = $result->fetch();
          if (!$row && strstr($uid, '@')) {
            // remote user (that hasn't been added yet)
            return Planworld::addUser($uid);
          } else if (!$row) {
            return PLANWORLD_ERROR;
          } else {
            $table[$uid] = (int) $row['id'];
            return $table[$uid];
          }
        } else {
          return PLANWORLD_ERROR;
        }
      }
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * string Planworld::idToName ($uid)
   * converts numeric $uid to string representation
   */
  static function idToName ($uid) {
    // persistent lookup table
    static $table;

    if (is_int($uid)) {
      $dbh = DBUtils::_connect();
      
      if (isset($table[$uid])) {
        return $table[$uid];
      } else {
        $query = "SELECT username FROM users WHERE id={$uid}";

        /* execute the query */
        $result = $dbh->query($query);
        if ($result) {
          $row = $result->fetch();
          if (!$row) return PLANWORLD_ERROR;
          $table[$uid] = $row['username'];
          return $table[$uid];
        } else {
          return PLANWORLD_ERROR;
        }
      }
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * bool Planworld::isUser ($uid)
   * returns whether $uid is an actual user
   */
  static function isUser ($uid, $force=false) {
    $dbh = DBUtils::_connect();
    static $table;

    if (isset($table[$uid]) && !$force) {
      return $table[$uid];
    }

    if (!DBUtils::userExists($dbh, $uid)) {
      $table[$uid] = false;
      return false;
    }
    $table[$uid] = true;
    return true;
  }

  /**
   * bool Planworld::isValidUser ($uid)
   * Returns whether $uid is a valid user
   */
  static function isValidUser ($uid) {
    return !strstr(PW_RESERVED, '|' . $uid . '|') || strstr($uid, '@');
  }

  /**
   * bool Planworld::isRemoteUser ($uid)
   * returns whether $uid is a remote user (assuming that $uid is a valid user)
   */
  static function isRemoteUser ($uid) {
    $dbh = DBUtils::_connect();
    if (!DBUtils::userExists($dbh, $uid)) {
      return PLANWORLD_ERROR;
    }
    return DBUtils::isRemoteUser($dbh, $uid);
  }

  /**
   * bool Planworld::isWorldViewable ($uid)
   * returns whether $uid has a world-viewable plan
   */
  static function isWorldViewable ($uid) {
    $dbh = DBUtils::_connect();

    if (is_int($uid)) {
      $query = "SELECT world FROM users WHERE id={$uid}";
    } else if (is_string($uid)) {
      $query = "SELECT world FROM users WHERE username='" . addslashes($uid) . "'";
    }

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $row = $result->fetch();
      return ($row['world'] == 'Y') ? true : false;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * bool Planworld::isAdmin ($uid)
   * returns whether $uid is an admin
   */
  static function isAdmin ($uid) {
    $admin = Planworld::getPreference($uid, 'admin');
    return ($admin == 'true') ? true : false;
  }

  /**
   * void Planworld::query ($query)
   * execute an arbitrary query (potentially bad)
   */
  static function query ($query, $col=null) {
    $dbh = DBUtils::_connect();

    /* execute the query */
    $result = $dbh->query($query);
    if ($result && is_object($result)) {
      if (isset($col) && !empty($col)) {
        $row = $result->fetch();
        return $row[$col];
      } else {
        return $result->rowCount();
      }
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * int Planworld::getRandomUser ()
   * pick a user (with a plan) at random
   */
  static function getRandomUser() {
    $dbh = DBUtils::_connect();

    $query = "SELECT uid FROM plans ORDER BY " . PW_RANDOM_FN . " LIMIT 1";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $row = $result->fetch();
      return (int) $row['uid'];
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * array Planworld::getNodeInfo ($host)
   * Returns node information for $host.
   */
  static function getNodeInfo ($host) {
    $dbh = DBUtils::_connect();

    $query = "SELECT name, hostname, path, port, version FROM nodes WHERE name='{$host}'";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return = array('Name' => $row['name'],
                        'Hostname' => $row['hostname'],
                        'Path' => $row['path'],
                        'Port' => (int) $row['port'],
                        'Version' => (int) $row['version']);
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * array Planworld::getNodes ()
   * Return the node list.
   */
  static function getNodes () {
    $dbh = DBUtils::_connect();

    $query = "SELECT name, hostname, path, port, version FROM nodes ORDER BY name";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return[] = array('Name' => $row['name'],
                          'Hostname' => $row['hostname'],
                          'Path' => $row['path'],
                          'Port' => (int) $row['port'],
                          'Version' => (int) $row['version']);
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * array Planworld::getTimezones ()
   * Return the list of available timezones.
   */
  static function getTimezones () {
    $dbh = DBUtils::_connect();

    $query = "SELECT name FROM timezones ORDER BY name";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return[] = $row['name'];
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * string Planworld::getDisplayDate ($ts)
   * formats timestamp $ts into a nicely readable date.
   */
  static function getDisplayDate ($ts = null, $short = false) {
    if ($short) {
      if (!isset($ts))
        return false;
      else if ($ts == 0)
        return "Never";
      else if (date('n-j-y') == date('n-j-y', $ts))
        return date('g:ia', $ts);
      else
        return date('n/j/y', $ts);
    } else {
      if (!isset($ts)) {
        return false;
      } else if ($ts == 0) {
        return "Never";
      } else if (date('n-j-y') == date('n-j-y', $ts)) {
        return 'Today, ' . date('g:i a', $ts);
      } else if (date('n-j-y') == date('n-j-y', $ts + 86400)) {
        return 'Yesterday, ' . date('g:i a', $ts);
      } else {
        return date('n-j-y, g:i a', $ts);
      }
    }
  }

  static function addLinks ($plan, $viewer, $host=null) {
    /* auto-link links */
    $plan = preg_replace("/(^|[[:space:]\>])((https?|ftp|telnet|mailto):\/?\/?[^[:space:]]+)([[:space:]]|$)/", "\\1<a href=\"\\2\">\\2</a>\\4", $plan);

    $plan = preg_replace("/!link:(.+):(.+)!/i", "<a href=\"http://\\1\">\\2</a>", $plan);
    $plan = preg_replace("/!e?mail:([^!:]+):([^!:]+)!/i", "<a href=\"mailto:\\1\">\\2</a>", $plan);
    $plan = preg_replace("/!((https?|ftp|telnet|mailto):[^:]+):([^!]+)!/i", "<a href=\"\\1\">\\3</a>", $plan);

    /* macros */
    $plan = str_replace('%user%', $viewer, $plan);
    $plan = str_replace('%date%', date('n-j-Y'), $plan);
    $plan = str_replace('%time%', date('g:ia'), $plan);
    $plan = str_replace('%version%', PW_VERSION, $plan);
    $plan = str_replace('%node%', PW_NAME, $plan);
    
    /* !user! (and !user:description!) notation (with logic for external plans) */
    if (!isset($host)) {
      /* local plan being parsed */
      $plan = preg_replace('/!([a-z0-9\-\.\']+):([^!]+)!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\1\">\\2</a>", $plan);
      $plan = preg_replace('/!([a-z0-9\-\.\']+)!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\1\">\\1</a>", $plan);
    } else {
      /* remote plan being parsed */
      $plan = preg_replace('/!([a-z0-9\-\.\']+):([^!]+)!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\1@{$host}\">\\2</a>", $plan);
      $plan = preg_replace('/!([a-z0-9\-\.\']+)!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\1@{$host}\">\\1</a>", $plan);
    }
    
    /* links to plans on third-party systems */
    $plan = preg_replace('/!([a-z0-9\-\.]+)@' . PW_NAME . ':([^!]+)!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\1\">\\2</a>", $plan);
    $plan = preg_replace('/!(([a-z0-9\-\.]+)@' . PW_NAME . ')!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\2\">\\1</a>", $plan);
    $plan = preg_replace('/!([a-z0-9\-\.]+@[a-z0-9\-\.]+):([^!]+)!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\1\">\\2</a>", $plan);
    $plan = preg_replace('/!([a-z0-9\-\.]+@[a-z0-9\-\.]+)!/i', "<a href=\"" . PW_URL_INDEX . "?id=\\1\">\\1</a>", $plan);

    return $plan;
  }

  static function getAllUsers () {
    $dbh = DBUtils::_connect();

    $query = "SELECT username FROM users WHERE last_login!=0 AND remote='N' ORDER BY username";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return[] = $row['username'];
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  static function getAllUsersWithPlans ($start = null) {
    $dbh = DBUtils::_connect();

    $query = "SELECT username FROM users, plans WHERE users.id=plans.uid";
    if (isset($start)) {
      if ($start == '#')
        $query .= " AND users.username REGEXP \"^[0-9].*\"";
      else
        $query .= " AND users.username LIKE '${start}%'";
    }
    $query .= " ORDER BY username";

    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return[] = $row['username'];
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * Fetch the $num most recent updates.
   */
  static function getLastUpdates ($num) {
    $dbh = DBUtils::_connect();

    $query = "SELECT username, last_update FROM users WHERE remote='N' ORDER BY last_update DESC LIMIT $num";

    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return[] = $row;
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * Fetch the $num newest users.
   */
  static function getNewUsers ($num) {
    $dbh = DBUtils::_connect();

    $query = "SELECT username, first_login, last_update FROM users WHERE remote='N' AND last_login > 0 ORDER BY first_login DESC LIMIT $num";

    $result = $dbh->query($query);
    if ($result) {
      $return = array();
      while ($row = $result->fetch()) {
        $return[] = $row;
      }
      return $return;
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * int | array(int) Planworld::getLastLogin ($uid, $host)
   * Gets the last login time for $uid (from $host, if applicable)
   */
  static function getLastLogin ($uid, $host=null) {
    $dbh = DBUtils::_connect();

    if (is_array($uid) && !isset($host)) {
      
      /* local fetch-by-array */
      
      /* query construction */
      if (is_int($uid[0])) {
        /* fetch by userid */
        $query = "SELECT id, last_login FROM users WHERE id='{$uid[0]}'";
        for ($i=1;$i<sizeof($uid);$i++) {
          $query .= " OR id='{$uid[$i]}'";
        }
      } else {
        /* fetch by username */
        $query = "SELECT username as id, last_login FROM users WHERE username='{$uid[0]}'";
        for ($i=1;$i<sizeof($uid);$i++) {
          $query .= " OR username='{$uid[$i]}'";
        }
      }
     
      /* execute the query */
      $result = $dbh->query($query);
      if ($result) {
        $return = array();
        while ($row = $result->fetch()) {
          $user = $row['id'];
          $return[$user] = (int) $row['last_login'];
        }
        return $return;
      } else {
        return PLANWORLD_ERROR;
      }
 
    } else if (is_int($uid) && !isset($host)) {
      
      /* local fetch-by-userid */
      
      $query = "SELECT last_login FROM users WHERE id={$uid}";

      /* execute the query */
      $result = $dbh->query($query);
      if ($result) {
        $row = $result->fetch();
        if ($row == false) {
          return PLANWORLD_ERROR;
        }
        return (int) $row['last_login'];
      } else {
        return PLANWORLD_ERROR;
      }

    } else if (is_string($uid) && !isset($host)) {
      
      /* local fetch-by-username */
      
      $query = "SELECT last_login FROM users WHERE username='{$uid}'";

      /* execute the query */
      $result = $dbh->query($query);
      if ($result) {
        $row = $result->fetch();
        return (int) $row['last_login'];
      } else {
        return PLANWORLD_ERROR;
      }

    } else if ($node = Planworld::getNodeInfo($host)) {
      
      /* remote fetch-by-username (forced) */

      if ($node['Version'] < 2) {
        $result = Planworld::_call($node, 'users.getLastLogin', array($uid));
      } else {
        $result = Planworld::_call($node, 'planworld.user.getLastLogin', array($uid));
      }
      
      if (is_array($result)) {
        /* freshen the cache */
        foreach ($result as $u=>$t) {
          Planworld::query("UPDATE users SET last_login={$t} WHERE username='{$u}@{$host}'"); 
        }
        
        return $result;
      } else {
        /* received a single value */
        
        /* freshen the cache */
        Planworld::query("UPDATE users SET last_login={$result} WHERE username='{$uid}@{$host}'");
        
        return $result;
      }
      
    } else {
      /* remote attempt for a node not listed in the nodelist */
      return false;
    }
  }

  /**
   * int | array(int) Planworld::getLastUpdate ($uid, $host)
   * Gets the last update time for $uid (from $host, if applicable)
   */
  static function getLastUpdate ($uid, $host=null) {
    $dbh = DBUtils::_connect();

    if (is_array($uid) && !isset($host)) {
      
      /* local fetch-by-array */
      
      /* query construction */
      if (is_int($uid[0])) {
        /* fetch by userid */
        $query = "SELECT id, last_update FROM users WHERE id='{$uid[0]}'";
        for ($i=1;$i<sizeof($uid);$i++) {
          $query .= " OR id='{$uid[$i]}'";
        }
      } else {
        /* fetch by username */
        $query = "SELECT username as id, last_update FROM users WHERE username='{$uid[0]}'";
        for ($i=1;$i<sizeof($uid);$i++) {
          $query .= " OR username='{$uid[$i]}'";
        }
      }
     
      /* execute the query */
      $result = $dbh->query($query);
      if ($result) {
        $return = array();
        while ($row = $result->fetch()) {
          $user = $row['id'];
          $return[$user] = (int) $row['last_update'];
        }
        return $return;
      } else {
        return PLANWORLD_ERROR;
      }
 
    } else if (is_int($uid) && !isset($host)) {
      
      /* local fetch-by-userid */
      
      $query = "SELECT last_update FROM users WHERE id={$uid}";

      /* execute the query */
      $result = $dbh->query($query);
      if ($result) {
        $return = array();
        $row = $result->fetch();
        return (int) $row['last_update'];
      } else {
        return PLANWORLD_ERROR;
      }

    } else if (is_string($uid) && !isset($host)) {
      
      /* local fetch-by-username */
      
      $query = "SELECT last_update FROM users WHERE username='{$uid}'";

      /* execute the query */
      $result = $dbh->query($query);
      if ($result) {
        $return = array();
        $row = $result->fetch();
        return (int) $row['last_update'];
      } else {
        return PLANWORLD_ERROR;
      }

    } else if ($node = Planworld::getNodeInfo($host)) {
      
      /* remote fetch-by-username (forced) */
      if ($node['Version'] < 2) {
        $result = Planworld::_call($node, 'users.getLastUpdate', array($uid));
      } else {
        $result = Planworld::_call($node, 'planworld.user.getLastUpdate', array($uid));
      }
      
      if (is_array($result)) {
        /* freshen the cache */
        foreach ($result as $u=>$t) {
          Planworld::query("UPDATE users SET last_update={$t} WHERE username='{$u}@{$host}'"); 
        }
        
        return $result;
      } else {
        /* received a single value */
        
        /* freshen the cache */
        Planworld::query("UPDATE users SET last_update={$result} WHERE username='{$uid}@{$host}'");
        
        return $result;
      }
      
    } else {
      /* remote attempt for a node not listed in the nodelist */
      return false;
    }
  }

  /**
   * string Planworld::unwrap ($text)
   * Remove <pre> tags from $text.
   */
  static function unwrap ($text) {
    if (preg_match('/^\<pre\>(.*)\<\/pre\>\s*$/misAD', $text, $matches)) {
      return $matches[1];
    } else {
      return $text;
    }
  }

  /**
   * Fetches a preference for this user.
   */
  static function getPreference ($uid, $name) {
    $dbh = DBUtils::_connect();
    $query = "SELECT value FROM preferences WHERE uid={$uid} AND name='{$name}'";
    
    /* execute the query */
    $result = $dbh->query($query);
    if ($result) {
      $row = $result->fetch();
      return (isset($row['value']) ? $row['value'] : false);
    } else {
      return PLANWORLD_ERROR;
    }
  }

  /**
   * Returns the displayable form of the passed divider.
   */
  static function getDisplayDivider ($divider, $ts = null) {
    if (isset($ts)) {
      $result = preg_replace_callback('/[Dd][Aa][Tt][Ee]\[([^\'\]]+)\]/',
      function ($matches) use ($ts) { return date($matches[1], $ts); },
      $divider);
    } else {
      $result = preg_replace_callback('/[Dd][Aa][Tt][Ee]\[([^\'\]]+)\]/',
      function ($matches) { return date($matches[1]); },
      $divider);
    }
    return $result;
  }

  static function getType ($text) {
    if (preg_match('/^\<pre\>(.*)\<\/pre\>$/misAD', $text)) {
      return 'text';
    } else {
      return 'html';
    }
  }

  static function isText ($text) {
    if (Planworld::getType($text) == 'text')
      return true;
    else
      return false;
  }

  static function isHTML ($text) {
    return !Planworld::isText($text);
  }

  static function teaser ($text) {
    if (strlen($text) > 19)
      return substr($text, 0, min(strpos($text, " ", 16), 32)) . "...";
    else
      return $text;
  }

}

?>
