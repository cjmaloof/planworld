
<?php

	/**
	 * $Id: look.php,v 0.5 2009/07/15 JLO2 Exp $
	 * Planwatch Look
	 */
	
	/* 	
	
		Planwatch look prints out all new plans on a single page.
		This page may be navigated via hyperlinks or by using the j and k keys to navigate up
		and down from plan to plan.
			
		All you need to understand how Seth Fitsimmons's code works is essentially contained
		in this page as I had to reverse engineer a wide variety of calls.
		If you are uncertain what a variable does, just do a var_dump($variable) on it.
			
		Historical Note: The "Planwatch Look" command appeared in the original Vax version of
		planwatch written by John Manly (jwmanly). The functionality is as it is here --
		it prints a list of all plans that are new since the last time you viewed them.
		Josh Davidson's (jwdavidson@planwatch.org) planwatch.org has had a "Planwatch Look" 
		feature since its inception (and the Javascript here is borrowed from him). 
		The Amherst version, much to my shame, is only now 
		being implemented about 7 years later.

		Here's how this code works:
		1. Pull the requesting user's watchlist.
		2. Determine which plans are new.
		3. Loop through those plans, displaying each one appropriately.
		4. Mark each plan as an anchor for hyperlinks and as a div so that the Javascript can allow the j and k keys for scrolling.
			

		FILES TO CHANGE:
		The following files need to be aware of look.php. It is usually fairly obvious where changes
		need to be made (and searching for "snitch" or "snitch.inc" is usually a good locator).
		Sometimes more than one change needs to be made to a file.
			config.php
			functions.php
			layout/1/navbar.inc
			lib/Skin/1.php
		The version of planworld you are using may or may not already incorporate these changes.
					
	 */
	
	/* All the includes that are usually provided. */
	$pageName = "Planwatch Look for " . $_user->getUsername() . " at " . date("l, F jS, Y  g:ia");;
	
	$_base = dirname(__FILE__) . '/';
	require_once($_base . 'prepend.php');
	require_once($_base . 'lib/Planworld.php');
	require_once($_base . 'lib/Stats.php');
	require_once($_base . 'lib/User.php');
	putenv('TZ=' . $_user->getTimezone());

		$serializedList = "";
		$_user->loadPlanwatch();
		$pw = $_user->planwatch->getList();
		
		/* For each new plan, save the username to a list and mark the plan read. */
		foreach ($pw as $name => $group) {
			foreach ($group as $u => $entry) {
				if ($entry[1] > $entry[2]){ 
					$lookUser = new User($entry[0]);
					$_user->planwatch->markSeen($lookUser);
					$serializedList = $serializedList . $lookUser->username . ',';
				}
			}
		}
		
/* The css theme bits have been eliminated from the code, because we want the Look function to print in black and white now.  --JLO2 4/9/09 */



echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'; 
echo '<html><head><meta content="text/html; charset=iso-8859-1"http-equiv="Content-Type">';
echo '<title>' . $pageName . '</title>';
echo '<body>';
echo'<style type="text/css">';
echo'body {';
echo'			color: black;';
echo'			background-color: white;';
echo'			}';
echo'h1 {';
echo'			color: black;';
echo'			background-color: white;';
echo'			}';
echo'h2 {';
echo'			color: black;';
echo'			background-color: white;';
echo'			}';
echo'p {';
echo'			color: black;';
echo'			background-color: white;';
echo'			}';
echo'</style>';

/* Javascript to enable J and K keys. */
//echo "<script type='text/javascript' src='". $_base . "layout/1/look.js'></script>";
//echo "<script type='application/javascript' src='". $_base . "layout/1/look.js'></script>";	

/* What on earth am I (JLO2) doing?
There are sometimes (and right now) MIME problems with Javascript .js files, 
so an external one might not work. Prepend.php doesn't like HTML headers before it appears.
Breaking PHP into multiple blocks can break variables. So, the least terrible alternative
here is simply to embed the needed Javascript in a giant print block.

*/
echo("
\n
<script type='text/javascript'>\n
// Javascript code to make 'Look' respond to J and K keys.\n
// Donated by jwdavidson, and copied over by jlodom00\n
// to be placed in planworldbase/layout/1/look.js\n
// 4-9-09\n
// basic variable setup\n
var nonChar = false;\n
var vpH = 0;\n
var planTops = new Array();\n
var i = 0; var k = 0;\n
\n
// connect important events\n
document.onkeydown        = function(e) {handleKeys(e)};\n
document.onkeypress       = function(e) {handleKeys(e)};\n
window.onload             = function(e) {getViewportHeight(); getPlanTops();};\n
window.onresize           = function(e) {getViewportHeight(); getPlanTops();};\n
\n
// update handleScroll below and uncomment to mark as read while scrolling\n
//document.onscroll       = function(e) {handleScroll()};\n
\n
// finds the tops of every plan\n
function getPlanTops()\n
{\n
	var planId = 'e0';\n
	while(document.getElementById(planId))\n
	{\n
		planTops[i] = document.getElementById(planId).offsetTop;\n
		i++;\n
		planId = 'e' + i;\n
	}\n
}\n
\n
// gets called whenever you press a key\n
function handleKeys(e) {\n
    var char;\n
    var evt = (e) ? e : window.event;       //IE reports window.event not arg\n
    if (evt.type == 'keydown') {\n
        char = evt.keycode;\n
        if (char < 16 ||                    // non printables\n
            (char > 16 && char < 32) ||     // avoid shift\n
            (char > 32 && char < 41) ||     // navigation keys\n
            char == 46) {                   // Delete Key (Add to these if you need)\n
            handleNonChar(char);            // function to handle non Characters\n
            nonChar = true;\n
        } else\n
            nonChar = false;\n
    } else {                                // This is keypress\n
        if (nonChar) return;                // Already Handled on keydown\n
        char = (evt.charCode) ?\n
                   evt.charCode : evt.keyCode;\n
        if (char > 31 && char < 256)        // safari and opera\n
            handleChar(char);               //\n
    }\n
}\n
\n
\n
// did you press a modifier key? then ignore.\n
function handleNonChar(){return 1;}\n
// did you press j or J or k or K? then do something.\n
function handleChar(char)\n
{\n
	if(char==106 || char == 74) prevPlan();\n
	if(char==107 || char == 75) nextPlan();\n
}\n
\n
// jumps to the previous plan, or the top of the page if there is no previous plan\n
function nextPlan()\n
{\n
	k=currentPlan();\n
	if(planTops[k + 1]) next=k + 1;\n
	location.hash = 'e' + next;\n
}\n
\n
// jumps to the next plan, or the bottom of the page if there is no next plan\n
function prevPlan()\n
{\n
	k=currentPlan();\n
	if(planTops[k - 1]) next=k - 1;\n
	location.hash = 'e' + next;\n
}\n
\n
// determines the content area height of the browser window\n
function getViewportHeight()\n
{\n
	if (typeof window.innerWidth != 'undefined')\n
		vpH = window.innerHeight\n
	else if (typeof document.documentElement != 'undefined'\n
	 && typeof document.documentElement.clientWidth !=\n
	 'undefined' && document.documentElement.clientWidth != 0)\n
		vpH = document.documentElement.clientHeight\n
}\n
\n
// returns the current plan -- defined as the plan that takes up\n
// more than half of the current screen\n
function currentPlan()\n
{\n
	if (typeof k == 'undefined') var k=1;\n
	var currentTop = (document.documentElement.scrollTop ?\n
            document.documentElement.scrollTop :\n
            document.body.scrollTop);\n
	var currentBottom = parseInt(vpH + currentTop);\n
	while(currentBottom > planTops[k+1] +(vpH * .5))\n
	{\n
		k++;\n
	}\n
	return k;\n
}\n
\n
\n
\n
// this is the basic starting point for not marking plans as read until\n
// you've actually scrolled them onto the screen. you want to hook this\n
// up to window.onscroll and then make an ajax call to mark as read \n
// where indicated.\n
\n
// change vpH * .5 to vpH * 1 to require the plan to be at the top of\n
// the window before marking as read.\n
\n
function handleScroll()\n
{\n
	if (typeof k == 'undefined') var k=1;\n
	var currentTop = (document.documentElement.scrollTop ?\n
            document.documentElement.scrollTop :\n
            document.body.scrollTop);\n
	var currentBottom = parseInt(vpH + currentTop);\n
	while(currentBottom > planTops[k+1] +(vpH * .5))\n
	{\n
		k++;\n
		// ajax call to mark as read\n
	}\n
}\n
</script>\n
");
	
		
		/* Title. */		
		echo "<div id='e0'>";  /*Div tags for javascript to do page up and page down stuff .*/
		echo '<span class="subtitle"><a name="looktop"></a>' . $pageName . '</span><br />';
		echo '<i>To navigate from plan to plan, use the hyperlinks or press the j and k keys to go up and down respectively.</i><br />';
		echo '</div>';
		echo '<P></P>';
				
		/* Test to see if there are actually any unread plans to look at. */
		if($serializedList){
		
			/* Create some basic variables. */
			$userLookKeys = explode(',', ($serializedList));
			$index = count($userLookKeys);
			$i = 0;
			$userLookPlans[$index] = 0; /* To initialize array. Value is bogus. */
		
			/* Master loop to print each plan with an index. */
			foreach($userLookKeys as $singleKey){
			
				/*	This is the recommended way to look up a plan.
						If you just need the plan, replace displayPlan with getPlan($_user) 
						I share this with you because I tried a bunch of losing ways first.
				*/
				$userLookTemp = User::factory($singleKey);
				$userLookPlans[$i] = $userLookTemp->displayPlan($_user, null, null);
				
				/* Get rid of renegade CSS style sheets and line wrap text-only plans. 
						Thanks to Josh Davidson for most of this. It is a testament to the horror
						of user style sheets that this is the lengthiest section of the code.
				*/
				while(strstr($userLookPlans[$i],"<style"))	/*Get rid of anything in a style tag. */
				{
					$start=strpos($userLookPlans[$i],"<style");
					$end=strpos($userLookPlans[$i],"</style>");
					$userLookPlans[$i]=str_replace(substr($userLookPlans[$i],$start,($end-($start+8))),'',$userLookPlans[$i]);
				}
				while(strstr($userLookPlans[$i],"<STYLE"))	
				{
					$start=strpos($userLookPlans[$i],"<STYLE");
					$end=strpos($userLookPlans[$i],"</STYLE>");
					$userLookPlans[$i]=str_replace(substr($userLookPlans[$i],$start,($end-($start+8))),'',$userLookPlans[$i]);
				}
				/* Now get rid of any links to offsite style sheets. */
				$userLookPlans[$i]=preg_replace("/style=['\"][^'\"]*['\"]/",'',$userLookPlans[$i]);
				$userLookPlans[$i]=preg_replace("/STYLE=['\"][^'\"]*['\"]/",'',$userLookPlans[$i]);
				$userLookPlans[$i]=preg_replace("/<link [^\>]*stylesheet[^\>]*>/",'',$userLookPlans[$i]);
				$userLookPlans[$i]=preg_replace("/<link [^\>]*STYLESHEET[^\>]*>/",'',$userLookPlans[$i]);
				$userLookPlans[$i]=wordwrap($userLookPlans[$i], 160, "\n");	/* Wrap plans to a specific length. 160 may be too long. */
				/* Finally, get rid of the display header, since some of its functions don't work in Look mode. */
					while(strstr($userLookPlans[$i],'<tt><a href='))	/*Get rid of anything in a style tag. */
				{
					$startHeader=strpos($userLookPlans[$i],'<tt><a href=');
					$endHeader=strpos($userLookPlans[$i],'Plan:</tt>');
					$userLookPlans[$i]=str_replace(substr($userLookPlans[$i],$startHeader,($endHeader-($startHeader-10))),'',$userLookPlans[$i]);
				}
				$i++;
			}
		
		$i=0;	/* Reset $i for reuse. */
		
		/* One table per plan to look at. Lots of tricky formatting -- look carefully. */
		for($i = 0; $i < ($index - 1); $i++){ /* This loop displays each plan. */
			echo "<div id='e" . ($i+1) . "'>";
			$j = 0;
			echo '<a name="look_' . $userLookKeys[$i] . '"></a><a href="#looktop"><b><u>Return to Top of Look Page</u></b></a> | ';
			echo '<a href="index.php"><b><u>Return to Main Planworld Page</u></b></a><br />';
			for($j = 0; $j < ($index - 1); $j++){ /* This loop creates the index that is displayed above each plan. */
				if($j !=0) echo '&nbsp;&nbsp; |';
				echo '&nbsp;&nbsp;<a href="#look_' . $userLookKeys[$j] . '">' . $userLookKeys[$j] . '&nbsp;&nbsp;</a> ';
			}
			echo '<P></P>';
			echo '<br />' . $userLookKeys[$i] . '\'s plan<br />';
			echo $userLookPlans[$i] . '<br />';
			echo '<hr width="82%"><p></p>';
			echo'</div>';
		}
			/* Footer for pages with something on them. */
			echo "<div id='e" . ($i+1) . "'>";
			echo '<a name="look_' . $userLookKeys[$i] . '"></a><a href="#looktop"><b><u>Return to Top of Look Page</u></b></a><br />';
			echo '<a href="index.php"><b><u>Return to Main Planworld Page</u></b></a><br />';
			echo'</div>';
			echo '</body></html>';
		
		}
		
		/* But what if all the plans have already been read? */  
		else {
			echo '<P>Nothing to look at.</P><P>';
			echo '<a href="index.php">Return to Main Planworld Page</a></P>';
			echo '</body></html>';
		}

$_user->save();			
	?>