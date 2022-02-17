<?php
/**
 * $Id: snoopfix.php,v 0.2 2009/07/16 20:50:14 JLO2 Exp $
 * Check snoop validity, correct invalid snoops, and delete snoops as requested by user.
 * Lots of bits stolen from Seth.*/

/* Includes. Not all may be required. */
$_base = dirname(__FILE__) . '/';
require_once('prepend.php');
require_once('lib/Planworld.php');
require_once('lib/Planwatch.php');
require_once('lib/Snoop.php');
require_once('lib/User.php');
require_once('lib/RemoteUser.php');
require_once('config.php');
require_once('functions.php');

$userCompare = $_user->getUsername();	/* Our username for snoop checking. */

/* First, get the snoops to check. */
$snoop = Snoop::getReferences($_user, (isset($_GET['o'])) ? $_GET['o'] : null, (isset($_GET['d'])) ? $_GET['d'] : null);

/* Set some diagnostic variables. */
$allSnoops = sizeof($snoop);
$goodSnoops = 0;
$badSnoops = 0;

/* Second, loop through each snoop. */
foreach($snoop as $entry) {

	$snoopHit = 0;	/* Boolean to determine whether we have a valid snoop. */
	$nonfinUser = new User($entry['userName']);
	
	if($nonfinUser->remoteUser){
		$finUser = new RemoteUser($entry['userName']);
		$curPlan = $finUser->getPlan($finUser);
	}
	else{
		$curPlan = $nonfinUser->getPlan($nonfinUser);
	}
	
	
	/* Check whether the snoop is valid. Delete invalid snoops. */
	$hitormiss = Snoop::_getReferences($curPlan);
	
	/* Loop through each snoop username and compare it to our username. */
	foreach($hitormiss[1] as $word) {
		if(strcmp($userCompare, $word) == 0){
			$snoopHit++;
		}
	}
	
	/* This section removes snoops based on whether the snoop is good or whether the user-submitted
	form contains the current username (i.e. the user has requested the snoop to be deleted).
	 This function may be working incorrectly for remote plans. Check how userid is handled. */
	if((!$snoopHit) || (in_array($entry['userName'],$_POST['remArray']))){
		Snoop::removeReference($entry['userID'],$_user->userID);
		$badSnoops++;
	}
	else{
		$goodSnoops++;
	}
}

/* Redirect user back to the snoop page as if nothing ever happened. */
header("Location: " . PW_URL_INDEX . "?id=snoop");
exit();

?>