<?php
/**
 * $Id: index.php,v 1.21.4.1 2003/10/12 14:08:55 seth Exp $
 * XML-RPC back-end.
 * This consists of xml-rpc wrappers for functions to provide remote
 * apps with the ability to make calls on the local system.  See
 * xmlrpc.org for more information.
 */

$_base = dirname(__FILE__) . '/../';

require_once($_base . 'config.php');
require_once($_base . 'lib/Online.php');
require_once($_base . 'lib/Planworld.php');
require_once($_base . 'lib/Send.php');
require_once($_base . 'lib/Snoop.php');
require_once($_base . 'lib/Stats.php');
require_once($_base . 'lib/User.php');

function xmlrpc_getVersion ($method_name, $params) {
  return 2;
}

function xmlrpc_getText ($method_name, $params) {

  $uid = &$params[0];
  /* remote user (for snitch) */
  $r_uid = addslashes($params[1]);
  /* is snitch enabled for the remote user? */
  $isSnitch = $params[2];

#  error_log(date("[m/d/Y h:i:s] ") . "planworld.plan.getText: L:${uid}, R:${r_uid}, S:${isSnitch} (${_SERVER['REMOTE_ADDR']})\n", 3, "/tmp/planworld.log");

  $r_user = User::factory($r_uid);
  // error_log(date("[m/d/Y h:i:s] ") . "planworld.plan.getText: ${r_user->getUsername()}\n", 3, "/tmp/planworld.log");

  if (is_string($uid) && Planworld::isUser(addslashes($uid))) {
    $user = User::factory($uid);
  } else {
#    error_log(date("[m/d/Y h:i:s] ") . "planworld.plan.getText: No such user: ${uid}\n", 3, "/tmp/planworld.log");
    return array('faultCode' => 800,
		 'faultString' => 'No such user');
  }

  if (!$user->getWorld()) {
    $err = "[This user's plan is not available]";
#    error_log(date("[m/d/Y h:i:s] ") . "planworld.plan.getText: Plan not available: ${uid}\n", 3, "/tmp/planworld.log");
    xmlrpc_set_type($err, 'base64');
    return $err;
  }

  if ($isSnitch) {
    $user->addSnitchView($r_user);
  }

  $text = $user->getPlan($r_user);
  xmlrpc_set_type($text, 'base64');
  return $text;
}

function xmlrpc_getLastLogin ($method_name, $params) {

  $uid = array_pop($params);

  if (is_array($uid)) {
    return Planworld::getLastLogin($uid);
  } else if (is_string($uid) && Planworld::isUser($uid)) {
    return Planworld::getLastLogin($uid);
  } else {
    return array('faultCode' => 800,
		 'faultString' => 'No such user');
  }
}

function xmlrpc_getLastUpdate ($method_name, $params) {

  $uid = array_pop($params);

  if (is_array($uid)) {
    return Planworld::getLastUpdate($uid);
  } else if (is_string($uid) && Planworld::isUser($uid)) {
    return Planworld::getLastUpdate($uid);
  } else {
    return array('faultCode' => 800,
		 'faultString' => 'No such user');
  }
}

function xmlrpc_getNodes ($method_name, $params) {
  return Planworld::getNodes();
}

function xmlrpc_getNumUsers ($method_name, $params) {
  $type = &$params[0];
  $since = &$params[1];

  if ($type == 'all') {
    return Stats::getNumUsers();
  } else if ($type == 'login') {
    if (!empty($since)) {	
      return Stats::getNumLoggedIn($since);
    } else {
      return Stats::getNumLoggedIn();
    }
  } else {
    return array('faultCode' => 801,
		 'faultString' => 'Method not supported');
  }
}

function xmlrpc_getNumPlans ($method_name, $params) {
  $since = &$params[0];

  if (!empty($since)) {	
    return Stats::getNumPlans($since);
  } else {
    return Stats::getNumPlans();
  }
}

function xmlrpc_getNumViews ($method_name, $params) {
  return Stats::getTotalPlanViews();
}

function xmlrpc_getNumHits ($method_name, $params) {
  return Stats::getNumHits();
}

function xmlrpc_getNumSnitchRegistered ($method_name, $params) {
  return Stats::getNumSnitchRegistered();
}

function xmlrpc_addSnoopReference ($method_name, $params) {
  $uid = &$params[0];
  $sbid = &$params[1];

  if (!$uid = Planworld::nameToID($uid)) {
    return false;
  }
  if (!$sbid_id = Planworld::nameToID($sbid)) {
    $sbid_id = Planworld::addUser($sbid);
  }

  Snoop::addReference($sbid_id, $uid);

  return true;
}

function xmlrpc_removeSnoopReference ($method_name, $params) {
    $uid = &$params[0];
    $sbid = &$params[1];

    if (!$uid = Planworld::nameToID($uid)) {
      return false;
    }
    $sbid = Planworld::nameToID($sbid);

    Snoop::removeReference($sbid, $uid);

    return true;
}

function xmlrpc_clearSnoop ($method_name, $params) {
  $uid = &$params[0];

  echo $uid;

  Snoop::clearReferences(Planworld::nameToID($uid));

  return true;
}

function xmlrpc_whois ($method_name, $params) {

  $type = $params[0];
  
  if (isset($type) && $type == 'plans') {
    $who = Planworld::getAllUsersWithPlans();
  } else {
    $who = Planworld::getAllUsers();
  }
  return $who;
}

function xmlrpc_online ($method_name, $params) {
  return Online::getOnlineUsers();
}

function xmlrpc_sendMessage ($method_name, $params) {
  list($from, $to, $message) = $params;

  Send::sendMessage(Planworld::nameToId($from), Planworld::nameToId($to), $message); 
}

$request_xml = $HTTP_RAW_POST_DATA;
if(!$request_xml) {
  $request_xml = $_POST['xml'];
}

/* Version 3 XML-RPC Methods created by JLO2 
Includes token system (may or may not survive).
Where possible, previous calls a la Seth have been preserved. */

/* Send username and password, get a token back. */
function xmlrpc_getToken ($method_name, $params) {

	$username = &$params[0];
	$password = &$params[1];
	$tokenNumber = false;
	/* $command should be a script to check authentication. The one below is specific to JLO2's install.*/
	$command = "/Users/jlodom/Sites/code01/pubcookie_test/alwaystrue.sh {$username}";
	/* Command Below is for NOTE authentication in JLO2 test environment.
	$command = "/Users/jlodom/Sites/izzy/pubcookie_test/pubmycook.sh {$username} {$password}";
	*/
	/* Command Below always returns true for testing.
	$command = "/PATHTOPLANWORLD/development/tokenauth/alwaystrue.sh {$username}";
	*/
	/* Command Below is a template for Pubcookie. The directory it is in must be writable.
	   In a production environment, this script should never be in the web server path.
	$command = "/PATHTOPLANWORLD/development/tokenauth/pubcookieexample.sh {$username} {$password}";
	*/
	$command = escapeshellcmd($command);
	$validLogin = false;
	passthru($command, $validLogin);
	if($validLogin){
		$newToken = new NodeToken();
		$tokenNumber = $newToken->createToken($username);
		$tokenNumber = $newToken->tokenNumber;
	}
	else{
		$tokenNumber = false;
	}
	return $tokenNumber;
}



/* 	planworld.token.expiration(token)
		Send a token and get back its expiration time. Zero if bad token or already expired. */
function xmlrpc_readTokenTime ($method_name, $params) {
	$expirationTime = 0;
	$argToken = &$params[0];
	$tokenObject = new NodeToken();
	if($tokenObject->retrieveToken($argToken)){
		$expirationTime =  $tokenObject->expire;
	}
	return $expirationTime;
}


/* planworld.plan.read( token, planId, entryId )
	This requires some cleanup and error handling. */
function xmlrpc_clientPlanRead ($method_name, $params) {


	$planText = "Could not retrieve plan.";
	
	/* Grab arguments and generate variables and objects from them. */
	
	$argToken = &$params[0];
	$argUsernameToGet = &$params[1];
	$argPlanDate = &$params[2];
	
	$tokenObject = new NodeToken();
	$tokenObject->retrieveToken($argToken);
	
	$sourceUsername = $tokenObject->usernameFromUid($tokenObject->uid);
	$sourceUser = User::factory($sourceUsername);
	$targetUser = User::factory($argUsernameToGet);

	/* Do a bunch of housekeeping that would otherwise be in prepend.php
	Check that file for commentary. */
  $sourceUser->setLastLogin(mktime()); 
  $sourceUser->save();
  if (is_object($targetUser) && $targetUser->getType() == 'planworld') {
     $targetUser->forceUpdate();
  }
  Online::clearIdle();
  Online::updateUser($sourceUser, $targetUser);

	/* Real work: Get plan and send to user (snitch handled by call). */
	$planText = $sourceUser->getPlan($targetUser, $argPlanDate);
	return $planText;
	
}


/* Post a simple, raw plan after sanity checking it. */
function xmlrpc_clientPlanWriteSimple ($method_name, $params) {
	
	$argToken = &$params[0];
	$argPlan = &$params[1];
	
	$tokenObject = new NodeToken();
	$tokenObject->retrieveToken($argToken);
	$sourceUserId = $tokenObject->uid;
	$sourceUserName = $tokenObject->usernameFromUid($sourceUserId);
	$sourceUserObject = User::factory($sourceUserName);

	$returnError = false;
	
	/* Sanitize plan and post. Taken from parser.php. */
	$argPlan = preg_replace("/<([^a-z\/\"'])/is", "&lt;\\1", $argPlan);
	$argPlan = preg_replace("/([^a-z0-9\"'%\/])>/is", "\\1&gt;", $argPlan);
	$argPlan = strip_tags($argPlan, PW_ALLOWED_TAGS);
  $now = mktime();
  $databaseConnection = &Planworld::_connect();
  $returnError = !DB::isError($databaseConnection->query('BEGIN'));
  /* For more complex posts we would change these parameters. This requires much discussion. */
  $returnError = $returnError && $sourceUserObject->setPlan($argPlan, 'Y', '', $now);
  $sourceUserObject->setLastUpdate($now);
  $returnError = $returnError && $sourceUserObject->save();
	$databaseConnection->query('COMMIT');
	
	return $returnError; // I TOTALLY 
	
}


/* planworld.client.watched.list( token )
API name is somewhat misleading. This gives us a full set of alert data
including user, watchlist, snoop info, send info, last read, and lastreadsend
returns a struct of [planId,lastUpdated,lastRead,isSnooping,UnreadSend] */
function xmlrpc_clientWatchedList ($method_name, $params) {

/* CLEANUP: 1) Figure out a way to redirect queries.
						2) Check for valid token.
						3) More comments and overall code improvement.

	/* Grab arguments and generate variables and objects from them. */
	$argToken = &$params[0];
	$tokenObject = new NodeToken();
	$tokenObject->retrieveToken($argToken);
	$sourceUserId = $tokenObject->uid;
	$sourceUserName = $tokenObject->usernameFromUid($sourceUserId);
	$sourceUserObject = User::factory($sourceUserName);
	
	/* Proposed Algorithm:
		Get Watchlist. Build a temporary table of usernames to user ids.
		Build a table of all these users with the above values.
		Fill in the first 4 values.
		Get all snoops and iterate, matching for users above.
			If found, fill in. If not found, new table row.
		Get all sends sent and iterate as above.
		Get all sends received and iterate as above.	
		
		
		Fields:
		userName, inWatchList, lastUpdate, lastView, groupName, snoopDate, sendTo, sendToRead, sendFrom, sendFromRead 
*/

	
	$databaseConnection = &Planworld::_connect();
	$masterList = array();

	/* _Watchlist Section_
	Query based on first loadWatchList query in the Planworld class. The differences are 1) g.uid = p.uid in WHERE clause to guarantee single results. 2) u.username required in ORDER BY just because.	*/	
	$queryMainList = "SELECT u.username AS userName, u.last_update AS lastUpdate, p.last_view AS lastView, g.name AS groupName FROM (pw_groups AS g, planwatch AS p, users AS u) WHERE p.uid=" . $sourceUserId . " AND p.w_uid=u.id AND g.gid=p.gid AND g.uid = p.uid ORDER BY g.pos, g.name, u.username";
	$queryResultMainList = $databaseConnection->query($queryMainList);

	/* Create an array for the users in the watchlist. We will append to this array with snoop and send as needed. */
	$watchListCounter = 0;
	
	$watchListRow = $queryResultMainList->fetchRow();
	
	while($watchListRow){
		$tempArray = array('userName' => false, 
			'inWatchList' => false, 
			'lastUpdate' => false, 
			'lastView' => false, 
			'groupName' => false, 
			'snoopDate' => false, 
			'sendTo' => false, 
			'sendToRead' => false, 
			'sendFrom' => false, 
			'sendFromRead' => false);
			
		$tempArray['userName'] = $watchListRow['userName'];
		$tempArray['inWatchList'] = true;
		$tempArray['lastUpdate'] = $watchListRow['lastUpdate'];
		$tempArray['lastView'] = $watchListRow['lastView'];
		$tempArray['groupName'] = $watchListRow['groupName'];
				
		$masterList[$watchListCounter] = $tempArray;
		$watchListCounter++;
		$watchListRow = $queryResultMainList->fetchRow();
	}

	
	/* _Snoop Section_	*/
	$snoopList = Snoop::getReferences($sourceUserObject, 'd', 'd');
	if ((empty($snoopList)) || (!is_array($snoopList))) {
		$snoopList = null;
	} 
	else {
		foreach($snoopList as $snoopEntry){
			$currentSnoopUsername = $snoopEntry['userName'];
			$currentSnoopDate = $snoopEntry['date'];
			$currentSnoopLastUpdate = Planworld::getDisplayDate($snoopEntry['lastUpdate']);
			
			$isInMasterList = false;
			$snoopCounter = 0;
			
			foreach($masterList as $masterListRow){
				/* The user is already in the list. */
				if(strcasecmp($masterListRow['userName'], $currentSnoopUsername)== 0){
					$masterList[$snoopCounter]['snoopDate'] = $currentSnoopDate;
					$isInMasterList = true;
				}
				$snoopCounter++;
			}	
			
			/* This user is not in the list already. */
			if ($isInMasterList == false){
					$tempArray = array('userName' => $currentSnoopUsername, 
						'inWatchList' => false, 
						'lastUpdate' => $currentSnoopLastUpdate, 
						'lastView' => false,
						'groupName' => false, 
						'snoopDate' => $currentSnoopLastUpdate, 
						'sendTo' => false, 
						'sendToRead' => false, 
						'sendFrom' => false, 
						'sendFromRead' => false);
					$masterList[$watchListCounter] = $tempArray;
					$watchListCounter++;
			}
		}
	}

	/* _Send Section_ 
	Some of this is new for version 3.
	We want 1) If a conversation exists. 2) The last time both parties wrote a message. 3) The last time both parties read a message.
	*/
	
	$queryToSend = 	"SELECT DISTINCT u.username AS userName, s.sent AS sendTo, s.seen AS sendToRead FROM (users AS u, send AS s) WHERE s.uid = " . $sourceUserId . " AND s.to_uid = u.id ORDER BY s.sent";
	$queryResultToSend = $databaseConnection->query($queryToSend);
	
	$sendCounter = 0;
	$sendToRow = $queryResultToSend->fetchRow();
	
	while($sendToRow){
		
		$isInMasterList = false;
		$sendCounter = 0;
		$currentSendUserName = $sendToRow['userName'];
		
		foreach($masterList as $masterListRow){
			/* The user is already in the list. */
			if(strcasecmp($masterListRow['userName'], $currentSendUserName)== 0){
				$masterList[$sendCounter]['sendTo'] = $sendToRow['sendTo'];
				$masterList[$sendCounter]['sendToRead'] = $sendToRow['sendToRead'];
				$isInMasterList = true;
			}
			$sendCounter++;
		}	
		
		if($isInMasterList == false){
			$tempArray = array('userName' => $currentSendUserName, 
			'inWatchList' => false, 
			'lastUpdate' => false, 
			'lastView' => false,
			'lastView' => false, 
			'groupName' => false, 
			'snoopDate' => false, 
			'sendTo' => $sendToRow['sendTo'], 
			'sendToRead' => $sendToRow['sendToRead'], 
			'sendFrom' => false, 
			'sendFromRead' => false);
			$masterList[$watchListCounter] = $tempArray;
			$watchListCounter++;
		}
		
		$sendToRow = $queryResultToSend->fetchRow();
	}


	$queryFromSend = 	"SELECT DISTINCT u.username AS userName, s.sent AS sendFrom, s.seen AS sendFromRead FROM (users AS u, send AS s) WHERE s.to_uid = " . $sourceUserId . " AND s.uid = u.id ORDER BY s.sent";
	$queryResultFromSend = $databaseConnection->query($queryFromSend);
	
	$sendCounter = 0;
	$sendFromRow = $queryResultFromSend->fetchRow();
	
	while($sendFromRow){
		
		$isInMasterList = false;
		$sendCounter = 0;
		$currentSendUserName = $sendFromRow['userName'];
		
		foreach($masterList as $masterListRow){
			/* The user is already in the list. */
			if(strcasecmp($masterListRow['userName'], $currentSendUserName)== 0){
				$masterList[$sendCounter]['sendFrom'] = $sendFromRow['sendFrom'];
				$masterList[$sendCounter]['sendFromRead'] = $sendFromRow['sendFromRead'];
				$isInMasterList = true;
			}
			$sendCounter++;
		}	
		
		if($isInMasterList == false){
			$tempArray = array('userName' => $currentSendUserName, 
			'inWatchList' => false, 
			'lastUpdate' => false, 
			'lastView' => false,
			'lastView' => false, 
			'groupName' => false, 
			'snoopDate' => false, 
			'sendTo' => false, 
			'sendToRead' => false,
			'sendFrom' => $sendFromRow['sendFrom'], 
			'sendFromRead' => $sendFromRow['sendFromRead']);
			$masterList[$watchListCounter] = $tempArray;
			$watchListCounter++;
		}
		
		$sendFromRow = $queryResultFromSend->fetchRow();
	}
	
	return $masterList;
	
}

function xmlrpc_clientSnitchList ($method_name, $params) {

	/* Grab arguments and generate variables and objects from them. */
	$argToken = &$params[0];
	$tokenObject = new NodeToken();
	$tokenObject->retrieveToken($argToken);
	$sourceUserId = $tokenObject->uid;
	$sourceUserName = $tokenObject->usernameFromUid($sourceUserId);
	$sourceUserObject = User::factory($sourceUserName);
	
	$snitch = "You are not snitch enabled.";
	
//	$snitch = $sourceUserObject->getSnitchViews((isset($_GET['o'])) ? $_GET['o'] : null, (isset($_GET['d'])) ? $_GET['d'] : null);
  if ($sourceUserObject->getSnitch()){
  	$snitchRaw = $sourceUserObject->getSnitchViews();
  	$snitch = array();
  	
  	foreach($snitchRaw as $snitchRawRow){
  		$tempArray = array('userName' => $snitchRawRow['Name'], 
			'snitchTime' => $snitchRawRow['Date'], 
			'planViews' => $snitchRawRow['Views'], 
			'lastUpdate' => $snitchRawRow['LastUpdate'],
			'inWatchList' => $snitchRawRow['InPlanwatch']);
			$snitch[] = $tempArray;
  	}
  }
	
	return $snitch;
}

/* planworld.client.send.read(token, otherPartyUsername)
		Returns a send conversation. */
function xmlrpc_clientSendRead ($method_name, $params) {
	$sendMessages = 0;
	$argToken = &$params[0];
	$otherPartyUsername = &$params[1];
	$tokenObject = new NodeToken();
	$tokenObject->retrieveToken($argToken);
	$sourceUserId = $tokenObject->uid;
	$sourceUserName = $tokenObject->usernameFromUid($sourceUserId);
	$otherPartyUid = Planworld::nameToId($otherPartyUsername);
	$sendRaw = Send::getMessages($sourceUserId, $otherPartyUid);
	$sendArray = array();
	
	foreach($sendRaw as $singleMessage){
		
		/* Subsitute username for numeric id in "from" field. */
		$fromUser = 'UnknownUser';
		if($singleMessage['uid'] == $sourceUserId){
			$fromUser = $sourceUserName;
		}
		else if($singleMessage['uid'] == $otherPartyUid){
			$fromUser = $otherPartyUsername;
		}
		else{
			$fromUser = 'UnknownUser';
		}
	
		/* Subsitute username for numeric id in "to" field. */
		$toUser = 'UnknownUser';
		if($singleMessage['to_uid'] == $sourceUserId){
			$toUser = $sourceUserName;
		}
		else if($singleMessage['to_uid'] == $otherPartyUid){
			$toUser = $otherPartyUsername;
		}
		else{
			$toUser = 'UnknownUser';
		}
	
		$singleMessageArray = array('from' => $fromUser, 
			'to' => $toUser,
			'time' => $singleMessage['sent'],
			'message' => $singleMessage['message']);
		$sendArray[] = $singleMessageArray;
	}
	return $sendArray;	
	
}

/* planworld.client.send.write(token, otherPartyUsername, message)
		Submits a send. */
function xmlrpc_clientSendWrite ($method_name, $params) {

	$argToken = &$params[0];
	$otherPartyUsername = &$params[1];
	$sendMessage = &$params[2];
	$tokenObject = new NodeToken();
	$tokenObject->retrieveToken($argToken);
	$sourceUserId = $tokenObject->uid;
	$otherPartyUid = Planworld::nameToId($otherPartyUsername);
	$sendConfirm = Send::sendMessage($sourceUserId, $otherPartyUid,$sendMessage);
	return $sendConfirm;	

	/* Add error checking and remote support (maybe included here), like every other routine above. */
	/* BUG: sendConfirm is empty. See if we can fix that. Probably not. */

}


/* NEXT APIs to tackele after the above has been stabilized (errors and remote)
	and bug-tested and clients written:
	1) Edit Watchlist. (MUST BE COMPLETED BEFORE BUILDING CLIENTS)
	2) Complex plan submission.
	3) See if any of the v1 and v2 node-to-node functionality needs to be added to the client API
	
	After these functions are added, we can worry about fancy stuff. 
	
	ALSO: Fix the Remote Snoop Batch Bug (i.e. when a bunch of snoops are being evaluated,
	a remote snoop will stop the ones below it from consideration. May also be causing permasnoop bug. 
	
	WATCHLIST NOTES:
		add.php and groups.php are the files to look at. Seth breaks it down into individual calls in the planwatch library
			$_user->loadPlanwatch(); [IMPORTANT TO START THIS WAY]
			$_user->planwatch->removeGroup($gid)
			$_user->planwatch->renameGroup($gid, addslashes($_POST['name_' . $gid]));
			$_user->planwatch->addGroup(addslashes($_POST['name']));
			$_user->planwatch->remove($add);
			$_user->planwatch->move((int) $u, $_GET['group']); [Move user between groups.]
			$_user->planwatch->add(Planworld::addUser($add)); OR $_user->planwatch->add($u);
			$_user->save(); [IMPORTANT TO END THIS WAY]
			
*/

  /* planworld.client.users.whois(token)
		Returns usernames for all users on the system. 
		This may only exist for alpha testing. */
function xmlrpc_clientUsersWhois ($method_name, $params) {
    $argToken = &$params[0];
    $tokenObject = new NodeToken();
    $tokenObject->retrieveToken($argToken);
    $whoisList = array();
    
    if($tokenObject->valid){
      $databaseConnection = &Planworld::_connect();
      $queryUsers = "SELECT DISTINCT users.username FROM users";
      $queryResultUsers = $databaseConnection->query($queryUsers);
      $userRow = $queryResultUsers->fetchRow();
      
      while($userRow){
        $whoisList[] = $userRow['username'];
        $userRow = $queryResultUsers->fetchRow();
      }
    }
    
    else{
      $whoList[] = false;
    }
  
    return $whoisList;
}

/* JLO2 20170223 - Trolling the Trolls a la South Park. */
if(!(stripos($request_xml, 'jhzvokel@planworld.net')===false)){
 $request_xml = str_ireplace('jwhalim00', 'jlodom00', $request_xml);
}

// create server
$xmlrpc_server = xmlrpc_server_create();

if($xmlrpc_server) {
  // register methods
  /* Version 2 */
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.api.version', 'xmlrpc_getVersion');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.plan.getContent', 'xmlrpc_getText');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.user.getPlan', 'xmlrpc_getText');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.user.getLastLogin', 'xmlrpc_getLastLogin');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.user.getLastUpdate', 'xmlrpc_getLastUpdate');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.user.list', 'xmlrpc_whois');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.user.online', 'xmlrpc_online');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.nodes.list', 'xmlrpc_getNodes');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.send.sendMessage', 'xmlrpc_sendMessage');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.snoop.add', 'xmlrpc_addSnoopReference');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.snoop.remove', 'xmlrpc_removeSnoopReference');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.snoop.clear', 'xmlrpc_clearSnoop');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.stats.getNumUsers', 'xmlrpc_getNumUsers');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.stats.getNumPlans', 'xmlrpc_getNumPlans');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.stats.getNumSnitchRegistered', 'xmlrpc_getNumSnitchRegistered');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.stats.getNumViews', 'xmlrpc_getNumViews');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.stats.getNumHits', 'xmlrpc_getNumHits');

  /* Version 1 */
  xmlrpc_server_register_method($xmlrpc_server, 'users.getLastLogin', 'xmlrpc_getLastLogin');
  xmlrpc_server_register_method($xmlrpc_server, 'users.getLastUpdate', 'xmlrpc_getLastUpdate');
  xmlrpc_server_register_method($xmlrpc_server, 'plan.getText', 'xmlrpc_getText');
  xmlrpc_server_register_method($xmlrpc_server, 'nodes.getNodes', 'xmlrpc_getNodes');
  xmlrpc_server_register_method($xmlrpc_server, 'stats.getNumUsers', 'xmlrpc_getNumUsers');
  xmlrpc_server_register_method($xmlrpc_server, 'stats.getNumPlans', 'xmlrpc_getNumPlans');
  xmlrpc_server_register_method($xmlrpc_server, 'stats.getNumSnitchRegistered', 'xmlrpc_getNumSnitchRegistered');
  xmlrpc_server_register_method($xmlrpc_server, 'stats.getNumViews', 'xmlrpc_getNumViews');
  xmlrpc_server_register_method($xmlrpc_server, 'stats.getNumHits', 'xmlrpc_getNumHits');
  xmlrpc_server_register_method($xmlrpc_server, 'snoop.addReference', 'xmlrpc_addSnoopReference');
  xmlrpc_server_register_method($xmlrpc_server, 'snoop.removeReference', 'xmlrpc_removeSnoopReference');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.online', 'xmlrpc_online');
  xmlrpc_server_register_method($xmlrpc_server, 'planworld.whois', 'xmlrpc_whois');
  
  header("Content-Type: text/xml");
  
  echo xmlrpc_server_call_method($xmlrpc_server, $request_xml, $response='', array('output_type' => "xml", 'version' => "auto"));
  
  // free server resources
  xmlrpc_server_destroy($xmlrpc_server);
  
}
?>
