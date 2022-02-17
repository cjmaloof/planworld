// Javascript code to make "Look" respond to J and K keys.
// Donated by jwdavidson, and copied over by jlodom00
// to be placed in $planworldbase/layout/1/look.js
// 4-9-09
// basic variable setup
var nonChar = false;
var vpH = 0;
var planTops = new Array();
var i = 0; var k = 0;

// connect important events
document.onkeydown        = function(e) {handleKeys(e)};
document.onkeypress       = function(e) {handleKeys(e)};
window.onload             = function(e) {getViewportHeight(); getPlanTops();};
window.onresize           = function(e) {getViewportHeight(); getPlanTops();};

// update handleScroll below and uncomment to mark as read while scrolling
//document.onscroll       = function(e) {handleScroll()};

// finds the tops of every plan
function getPlanTops()
{
	var planId = 'e0';
	while(document.getElementById(planId))
	{
		planTops[i] = document.getElementById(planId).offsetTop;
		i++;
		planId = 'e' + i;
	}
}

// gets called whenever you press a key
function handleKeys(e) {
    var char;
    var evt = (e) ? e : window.event;       //IE reports window.event not arg
    if (evt.type == 'keydown') {
        char = evt.keycode;
        if (char < 16 ||                    // non printables
            (char > 16 && char < 32) ||     // avoid shift
            (char > 32 && char < 41) ||     // navigation keys
            char == 46) {                   // Delete Key (Add to these if you need)
            handleNonChar(char);            // function to handle non Characters
            nonChar = true;
        } else
            nonChar = false;
    } else {                                // This is keypress
        if (nonChar) return;                // Already Handled on keydown
        char = (evt.charCode) ?
                   evt.charCode : evt.keyCode;
        if (char > 31 && char < 256)        // safari and opera
            handleChar(char);               //
    }
}


// did you press a modifier key? then ignore.
function handleNonChar(){return 1;}
// did you press j or J or k or K? then do something.
function handleChar(char)
{
	if(char==106 || char == 74) prevPlan();
	if(char==107 || char == 75) nextPlan();
}

// jumps to the previous plan, or the top of the page if there is no previous plan
function nextPlan()
{
	k=currentPlan();
	if(planTops[k + 1]) next=k + 1;
	location.hash = 'e' + next;	
}

// jumps to the next plan, or the bottom of the page if there is no next plan
function prevPlan()
{
	k=currentPlan();
	if(planTops[k - 1]) next=k - 1;
	location.hash = 'e' + next;	
}

// determines the content area height of the browser window
function getViewportHeight()
{
	if (typeof window.innerWidth != 'undefined')
		vpH = window.innerHeight
	else if (typeof document.documentElement != 'undefined'
	 && typeof document.documentElement.clientWidth !=
	 'undefined' && document.documentElement.clientWidth != 0)
		vpH = document.documentElement.clientHeight
}

// returns the current plan -- defined as the plan that takes up
// more than half of the current screen
function currentPlan()
{
	if (typeof k == 'undefined') var k=1;
	var currentTop = (document.documentElement.scrollTop ?
            document.documentElement.scrollTop :
            document.body.scrollTop);
	var currentBottom = parseInt(vpH + currentTop);
	while(currentBottom > planTops[k+1] +(vpH * .5))
	{
		k++;
	}
	return k;
}



// this is the basic starting point for not marking plans as read until
// you've actually scrolled them onto the screen. you want to hook this
// up to window.onscroll and then make an ajax call to mark as read 
// where indicated.

// change vpH * .5 to vpH * 1 to require the plan to be at the top of
// the window before marking as read.

function handleScroll()
{
	if (typeof k == 'undefined') var k=1;
	var currentTop = (document.documentElement.scrollTop ?
            document.documentElement.scrollTop :
            document.body.scrollTop);
	var currentBottom = parseInt(vpH + currentTop);
	while(currentBottom > planTops[k+1] +(vpH * .5))
	{
		k++;
		// ajax call to mark as read
	}
}