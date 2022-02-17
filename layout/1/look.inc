<?php

	/**
	 * $Id: look.inc,v 0.2 2008/04/23 16:05:22 JLO2 Exp $
	 * Planwatch Look
	 */
	
	/* 	
			A special message from Johnnie Odom (jlodom00):
			All you need to understand how Seth Fitsimmons's code works is essentially contained
			in this page as I had to reverse engineer a wide variety of calls.
			If you are uncertain what a variable does, just do a var_dump($variable) on it.
			
			Historical Note: The "Planwatch Look" command appeared in the original Vax version of
			planwatch written by John Manly (jwmanly). The functionality is as it is here --
			it prints a list of all plans that are new since the last time you viewed them.
			Josh Davidson's (jwdavidson@planwatch.org) planwatch.org has had a "Planwatch Look" 
			feature since its inception. The Amherst version, much to my shame, is only now 
			being implemented about 7 years later.

		Here's how this code works.
	
		PART A:
		This is actually the code on the second half on the page (the final else block).
		If there are no post variables, then we know this is the first time we are entering this page.
		1. Pull the requesting user's watchlist.
		3. Determine which plans are new.
		4. Mark each plan read _now_ so that the new marks will be removed when we do the display.
		5. Create a serialized list of the new usernames.
		6. Create and post a form that contains two variables: The serialized username list and 
					a bit to indicate that we are loading the page for the second time.
					The posting happens via Javascript, which may be a pitfall in the future.
					We could use session() variables, but as these have been used elsewhere by more
					important code in the chain that forms a planworld page, I am skittish.
					
		PART B:
		This is the first part of the code, the part statring with if($_POST['lookBit']){
		The purpose is to actually display the "look" information -- long page of every new plan
		along with a convenient index to navigate around the page. We cannot use the back button because
		we posted the data.
		1. Turn the serialized list of usernames into an array and get an index of how many entries we have.
		2. Use the usernames to get user objects that return plan information.
		3. Put the plans into an array in the same order as the username array.
		4. Remove stylesheet information from the plans. It causes unspeakable things to happen to layout.
		5. Print out each plan, along with an anchor to its position.
		6. For each plan, print out an index that links to each anchor and also the top.
		7. There's lots of layout stuff interspersed with this code. DO NOT create too many nested tables.
				It is easy to accidentally mess up the master layout tables that each plan page builds.
				Also, users like to put badly-formed tables in their plans, which also causes problems.
				Instead, we just create one new table per loop run and then rows and cells inside the existing tables.
		
		FILES TO CHANGE:
		The following files need to be aware of look or look.inc. It is usually fairly obvious where changes
		need to be made (and searching for "snitch" or "snitch.inc" is usually a good locator).
		Sometimes more than one change needs to be made to a file.
			config.php
			functions.php
			layout/1/navbar.inc
			lib/Skin/1.php
		The version of planworld you are using may or may not already incorporate these changes.
				
	 */
	
	/* Only one include this time. */
	require_once($_base . 'lib/Planwatch.php');
	
	/* PART B: Actually display the plans. */
	if($_POST['lookBit']){
		
		/* Title. */		
		echo '<span class="subtitle"><a name="looktop">Planwatch Look</a></span><br /><P>&nbsp;</P>';
				
		/* Test to see if there are actually any unread plans to look at. */
		if($_POST['serializedList']){
		
			/* Create some basic variables. */
			$userLookKeys = explode(',', ($_POST['serializedList']));
			$index = count($userLookKeys);
			$i = 0;
			$subTableWidth = 8; /* To prevent magic numbers in number of columns. */
			$userLookPlans[$index];
		
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
				$i++;
			}
		
		$i=0;	/* Reset $i for reuse. */
		$td_cutoff = ($index / $subTableWidth); /* Initialize here so that it is available post-loop.
		
		/* One table per plan to look at. Lots of tricky formatting -- look carefully. */
		echo '<table width="160" border="0" cellpadding="0" cellspacing="0" valign="top">';
		for($i = 0; $i < ($index - 1); $i++){ /* This loop displays each plan. */
			$j = 0;
			if($td_cutoff == 0) $td_cutoff = 1;
			echo '<tr><td align="center" colspan="' . $subTableWidth . '"></td></tr>';
			echo '<tr><th colspan="' . $subTableWidth . '">';
			echo '<a name="look_' . $userLookKeys[$i] . '"></a><a href="#looktop"><b>Look Index</b> : Return to Top of Page</a><br>';
			echo '<i>Please navigate using this index rather than the "forward" and "back" browser buttons.</th></tr>';
			echo '<tr>';
			for($j = 0; $j < ($index - 1); $j++){ /* This loop creates the index that is displayed above each plan. */
				if((($j%$td_cutoff)==0) && ($j!=0)) echo '</td><td valign="top" colspan="1">';
				else if($j==0) echo '<td valign="top" colspan="1">';
				echo '&nbsp;&nbsp;<a href="#look_' . $userLookKeys[$j] . '">' . $userLookKeys[$j] . '&nbsp;&nbsp;</a><br>';
				if($j==($index-1)) echo '</td>';
			}
			echo '</tr>';
			echo '<tr><td colspan="' . $subTableWidth . '"><hr width="82%"></td></tr>';
			echo '<tr><td colspan="' . $subTableWidth . '">&nbsp;</td></tr>';
			echo '<tr><td colspan="' . $subTableWidth . '">' . $userLookPlans[$i] . '</td></tr>';
			echo '<tr><td colspan="' . $subTableWidth . '">&nbsp;</td></tr>';
			echo '<tr><td colspan="' . $subTableWidth . '"><hr width="82%"></td></tr>';
			$td_cutoff = ($index / 8);
			
		}
		echo '<tr><th colspan="' . $td_cutoff . '"><a href="#looktop">Return to Top of Page</a></th></tr>';
		echo '</table>';
		
		}
		
		/* But what if all the plans have already been read? */  
		else {
			echo "<P>Nothing to look at.<br />\n";
		}
		
		/* Whatever we do, we need to unset our lookBit. */
		unset ($_POST['lookBit']);
	} /* End of second pass. */
	
	
	
	/* First pass on look to get new usernames and mark their plans read. */
	else{
	
		/* Declare our serialized list and load the user's planwatch. */
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
		
		/* Submit a form back to ourselves with the serialized list
			and a bit set to let us know that this will be the second pass. */
		echo '<form name="lookform" action="index.php?id=look" method="post">';
		echo '<input type="hidden"" name="serializedList"" value=' . $serializedList . '>';
		echo '<input type="hidden"" name="lookBit"" value="1">';
		echo '</form>';
		echo '<SCRIPT language="JavaScript">';
		echo 'document.lookform.submit();';
		echo '</SCRIPT>';
	}
	
	?>