<?php
/*
	en_US.php
	$Id: en_US.php 466 2008-05-28 14:30:14Z lifo $

	Language mapping for 'en_US' auto-generated from pslang.pl.

	To start a new language set, copy this file to a new name using the locale or a simple name 
	representing the language (ie: chinese) as its name. A locale string is normally a 2 character
	code for the language, ie: en for english, fr for french, etc... Followed by an underscore (_) 
	then a 2 character country code for the language, ie: US, DE, PT, etc...
	For example, for french you might use "fr_FR", or for spanish use "es_US".

	Use the pslang.pl script included with PsychoStats to auto-generate a new language file like this.
*/
if (!defined("PSYCHOSTATS_PAGE")) die("Unauthorized access to " . basename(__FILE__));

// If the language translation extends another translation set then you should include
// that class file once here. This is useful for updating a translation set w/o having to define 
// every single language map if some translations are no different from the extended language.
//include_once($this->language_dir('en_US') . '/en_US.php');

class PsychoLanguage_default_en_US extends PsychoLanguage {

function __construct() {
	$this->PsychoLanguage();
	// You can set a locale if you want (which will affect certain system calls)
	// however, setting a locale is not 100% portable between systems and setlocale is not
	// thread-safe. Setting the locale on a multi-threaded server (ie: apache2 using mm_worker model) 
	// will affect other threads that are running at the same time.
//	setlocale(LC_ALL, 'en_US.UTF-8');

	// Every english phrase that can be translated is located here.
	// Becareful to properly escape strings so quotes are displayed properly.
	// Most strings are simple phrases or words. For more complex or larger translations, see the methods below.
	$this->map = array(
	'%s (%s) was added to the division.' =>
		'',
	'%s (%s) was removed from the division.' =>
		'',
	'A file location must be defined to download the theme from' =>
		'',
	'A name must be defined' =>
		'',
	'A password must be entered for new users' =>
		'',
	'A source location must be defined to download the theme from' =>
		'',
	'A user can only be associated with a single team.' =>
		'',
	'Access Level' =>
		'',
	'Add Friend' =>
		'',
	'Add New Member' =>
		'',
	'Add Selected Members' =>
		'',
	'Admin' =>
		'',
	'AIM' =>
		'',
	'All informational fields are optional and used solely for display purposes.' =>
		'',
	'Allowed Tags' =>
		'',
	'and have done' =>
		'',
	'Are you sure you want to delete the team?' =>
		'',
	'Auto Login' =>
		'',
	'Awards' =>
		'',
	'BS' =>
		'',
	'BS%' =>
		'',
	'Cancel' =>
		'',
	'Change Password?' =>
		'',
	'Change Theme' =>
		'',
	'Division' =>
		'',
	'Division member' =>
		'',
	'Division Members' =>
		'',
	'Division Name' =>
		'',
	'Division Rundown' =>
		'',
	'Division Statistics' =>
		'',
	'Divisions' =>
		'',
	'divisions rank out of' =>
		'',
	'Click here to clear your avatar.' =>
		'',
	'Click here to login again.' =>
		'',
	'Click here to logout!' =>
		'',
	'Click here to refresh' =>
		'',
	'Click on an avatar to select it.' =>
		'',
	'Click to connect' =>
		'',
	'Click to select theme' =>
		'',
	'Connections' =>
		'',
	'Connections: %d' =>
		'',
	'Counter-Terrorist Wins' =>
		'',
	'Country Breakdown' =>
		'',
	'Country Code' =>
		'',
	'CP' =>
		'',
	'Create user for this team' =>
		'',
	'D' =>
		'',
	'Date' =>
		'',
	'Day' =>
		'',
	'Delete' =>
		'',
	'Deleting a team does not prevent them from re-appearing in the stats.' =>
		'',
	'Details' =>
		'',
	'Diff' =>
		'',
	'Discord Channel Invitation Link' =>
		'',
	'Discord ID' =>
		'',
	'Discord invitation not in correct format.' =>
		'',
	'Discord Profile' =>
		'',
	'Edit' =>
		'',
	'Edit Division' =>
		'',
	'Edit Team' =>
		'',
	'Edit user for this team' =>
		'',
	'Email' =>
		'',
	'Email Address' =>
		'',
	'Error creating user: ' =>
		'',
	'Error deleting team: ' =>
		'',
	'Error loading icons' =>
		'',
	'Error retreiving user from database' =>
		'',
	'Error saving user: ' =>
		'',
	'Error updating team profile: ' =>
		'',
	'exploded' =>
		'',
	'Fatal Error' =>
		'',
	'for' =>
		'',
	'Forgot password?' =>
		'',
	'Forgot your password?' =>
		'',
	'G' =>
		'',
	'Game' =>
		'',
	'Games' =>
		'',
	'Go to' =>
		'',
	'Guest' =>
		'',
	'GUID' =>
		'',
	'GUIDs' =>
		'',
	'Hall of Fame' =>
		'',
	'has' =>
		'',
	'Home' =>
		'',
	'HTML Logo' =>
		'',
	'ICQ' =>
		'',
	'ICQ Number' =>
		'',
	'Image must start with http:// or https://' =>
		'',
	'Informational Fields' =>
		'',
	'Insufficient privileges to edit division!' =>
		'',
	'Insufficient privileges to edit team!' =>
		'',
	'Invalid access level specified' =>
		'',
	'Invalid characters found in name' =>
		'',
	'Invalid characters found in parent' =>
		'',
	'Invalid division ID Specified' =>
		'',
	'Invalid image defined' =>
		'',
	'Invalid name defined' =>
		'',
	'Invalid parent defined' =>
		'',
	'Invalid team ID Specified' =>
		'',
	'Invalid username or password' =>
		'',
	'Invalid website defined' =>
		'',
	'is not ranked' =>
		'',
	'is ranked' =>
		'',
	'Last' =>
		'',
	'Last 24 Hours' =>
		'',
	'Last Played' =>
		'',
	'Last Seen' =>
		'',
	'Loading ...' =>
		'',
	'Loading avatars, please wait' =>
		'',
	'Loading flags, please wait' =>
		'',
	'Lock Member List?' =>
		'',
	'Locked?' =>
		'',
	'Logged in as' =>
		'',
	'Login' =>
		'',
	'Login Help' =>
		'',
	'Login to admin control panel' =>
		'',
	'Login to edit this team\'s profile!' =>
		'',
	'Longitude' =>
		'',
	'Manage Division Members' =>
		'',
	'Managing Members' =>
		'',
	'matched' =>
		'',
	'Maximum Results' =>
		'',
	'Member Added!' =>
		'',
	'Member Removed!' =>
		'',
	'Members' =>
		'',
	'members with an average win % of' =>
		'',
	'Mini Avatar' =>
		'',
	'Most Division Titles' =>
		'',
	'Most League Championships' =>
		'',
	'Most League Titles' =>
		'',
	'ms' =>
		'',
	'MSN' =>
		'',
	'MSN Messenger' =>
		'',
	'Name' =>
		'',
	'New' =>
		'',
	'New Password' =>
		'',
	'Newbie?' =>
		'',
	'Next' =>
		'',
	'next' =>
		'',
	'No Awards Found' =>
		'',
	'no change' =>
		'',
	'No Division Found!' =>
		'',
	'No Divisions Found' =>
		'',
	'No divisions to display' =>
		'',
	'No file defined' =>
		'',
	'No Members Found!' =>
		'',
	'No name defined' =>
		'',
	'No team awards on this date' =>
		'',
	'No Team Found!' =>
		'',
	'No Teams Found' =>
		'',
	'No Sessions Found' =>
		'',
	'No source defined' =>
		'',
	'No Stats Available' =>
		'',
	'No teams qualify for this award' =>
		'',
	'none' =>
		'',
	'Not registered?' =>
		'',
	'Not Set' =>
		'',
	'on' =>
		'',
	'Online' =>
		'',
	'Online Time' =>
		'',
	'Only enter a password if you want to change it from the current password.' =>
		'',
	'Only the first team on a lat,lng location is displayed' =>
		'',
	'Only the top' =>
		'',
	'Optional' =>
		'',
	'Or click here to continue' =>
		'',
	'Other' =>
		'',
	'out of' =>
		'',
	'Overview' =>
		'',
	'Page loaded in' =>
		'',
	'Password' =>
		'',
	'Passwords do not match' =>
		'',
	'Passwords do not match; please try again' =>
		'',
	'ping' =>
		'',
	'Please feel free to browse the <a href="{url _base=\'index.php\'}">team statistics</a> now.' =>
		'',
	'Please go back and try again.' =>
		'',
	'Please wait' =>
		'',
	'potential divisions' =>
		'',
	'Powered by' =>
		'',
	'Previous' =>
		'',
	'Previous Rank' =>
		'',
	'PsychoStats Overview' =>
		'',
	'Quick Login Popup' =>
		'',
	'Quick Search Popup' =>
		'',
	'R' =>
		'',
	'Rank' =>
		'',
	'ranked teams out of' =>
		'',
	'Register' =>
		'',
	'Register new user' =>
		'',
	'Register now!' =>
		'',
	'Register!' =>
		'',
	'Registering a user will allow you to edit your team\'s profile information.' =>
		'',
	'Registration is closed! No teams may be registered at this time.' =>
		'',
	'Registration is OPEN. New users will have instant access to their team profiles.' =>
		'',
	'Registration requires confirmation from the admin. You won\'t be able to login until confirmed.' =>
		'',
	'Remember me!' =>
		'',
	'Rescued Hostages' =>
		'',
	'Reset your password' =>
		'',
	'Resubmit to try again.' =>
		'',
	'Retype Password' =>
		'',
	'Rule' =>
		'',
	'Rules' =>
		'',
	'runners up' =>
		'',
	'Runners Up' =>
		'',
	'Save' =>
		'',
	'Search' =>
		'',
	'Search criteria' =>
		'',
	'Search criteria "<em>%s</em>" matched %d ranked teams out of %d total' =>
		'',
	'seconds with' =>
		'',
	'Secure' =>
		'',
	'Select a Language' =>
		'',
	'Select a theme from this gallery.' =>
		'',
	'Select Members' =>
		'',
	'Server' =>
		'',
	'Session Time' =>
		'',
	'SQL queries' =>
		'',
	'Start Time' =>
		'',
	'Team' =>
		'',
	'Team / Action Profile' =>
		'',
	'Team Awards' =>
		'',
	'team IDs are shown' =>
		'',
	'Team Names' =>
		'',
	'Team Registration Completed!' =>
		'',
	'Team registration is currently disabled!' =>
		'',
	'Team Rundown' =>
		'',
	'Team Statistics' =>
		'',
	'teams' =>
		'',
	'Teams' =>
		'',
	'teams rank out of' =>
		'',
	'Thank you for registering your team' =>
		'',
	'The %s does not exist!' =>
		'',
	'The Discord ID is not in the correct format.' =>
		'',
	'The team number given for your team must already exist or registration will fail.' =>
		'',
	'The unique ID entered above should match the team you play as.' =>
		'',
	'The web address is unreachable.' =>
		'',
	'Theme download file not found or invalid type (' =>
		'',
	'Theme Gallery' =>
		'',
	'Themes will be applied to your session even if you\'re not logged in.' =>
		'',
	'There are currently no awards in the database to display.' =>
		'',
	'This field can not be blank' =>
		'',
	'This team is already registered!' =>
		'',
	'This user has not been confirmed yet and can not login at this time.' =>
		'',
	'This window will close in a few seconds.' =>
		'',
	'Time' =>
		'',
	'Time Left' =>
		'',
	'Today' =>
		'',
	'Toggle flags' =>
		'',
	'Toggle gallery' =>
		'',
	'top 1 percentile' =>
		'',
	'Top 100 Highest Ranked Teams' =>
		'',
	'Top Teams' =>
		'',
	'total' =>
		'',
	'Total Awards' =>
		'',
	'Total Games' =>
		'',
	'Twitch Channel' =>
		'',
	'Twitch Channel Name' =>
		'',
	'Twitch User Name' =>
		'',
	'Twitch user name not in correct format.' =>
		'',
	'Type' =>
		'',
	'Unable to download theme file from ' =>
		'',
	'Unclassified' =>
		'',
	'Unique ID' =>
		'',
	'Unknown' =>
		'',
	'unknown' =>
		'',
	'Used' =>
		'',
	'User does not have permission to login' =>
		'',
	'User Login' =>
		'',
	'User Logout' =>
		'',
	'User Registration' =>
		'',
	'Username' =>
		'',
	'Username already exists!' =>
		'',
	'Username already exists; please try another name' =>
		'',
	'Users with "admin" privileges will be able to access the Admin Control Panel (ACP).' =>
		'',
	'Users with "division admin" privileges can edit their own division profiles (if you\'re in a division).' =>
		'',
	'Value' =>
		'',
	'View History' =>
		'',
	'View Statistics' =>
		'',
	'Website' =>
		'',
	'Website must start with http:// or https://' =>
		'',
	'Welcome' =>
		'',
	'Why and How to Register?' =>
		'',
	'Windows' =>
		'',
	'Wins' =>
		'',
	'Yesterday' =>
		'',
	'You are using this theme' =>
		'',
	'You can now access and modify your own <b><a href="{url _base=\'editteam.php\' id=$team.team_id}">team profile</a></b>.' =>
		'',
	'You have been logged in.' =>
		'',
	'You have been logged out.' =>
		'',
	'You must have logged into the game server at least once before attempting to register.' =>
		'',
	'You will be redirected in a few seconds' =>
		'',
	'Your information is never sold or given away to third parties.' =>
		'',
	'YouTube Channel' =>
		'',
	'YouTube Channel Name' =>
		'',
	'YouTube User Name' =>
		'',
	'YouTube user name not in correct format.' =>
		'',

	) + $this->map;
}

function PsychoLanguage_default_en_US() {
    self::__construct();
}

// if a translation keyword maps to a method below then the matching method should return the translated string.
// This is most useful for those large blocks of text in the theme. 
function LOGIN_AUTOLOGIN() {
	$text  = '';
	$text .= 'If auto login is enabled a cookie will be saved in your browser and the next time you' . "\n";
	$text .= 'visit this site you will automatically be logged in again, even if you close your browser.' . "\n";
	return $text;
}

function MANAGE_DIVISION_MEMBERS() {
	$text  = '';
	$text .= 'If you manually edit the member list you should check the "lock" button.' . "\n";
	$text .= 'Otherwise there is no guarantee the listing will remain the way you set it.' . "\n";
	$text .= 'Changes to the member list below are instant!' . "\n";
	return $text;
}

function REGISTER_NEW_USER() {
	$text  = '';
	$text .= 'Register a user for this team by entering a username and password below.' . "\n";
	$text .= 'This will allow the user to login and modify their profile.' . "\n";
	return $text;
}

function REGISTRATION_COMPLETE() {
	$text  = '';
	$text .= 'In the future, to <a href="{url _base=\'login.php\'}">login</a> please use your username ' . "\n";
	$text .= '\'<b><a href="{url _base=\'edituser.php\' id=$reg.userid}">{$reg.username|escape}</a></b>\' with the password you supplied ' . "\n";
	$text .= 'in your registration.' . "\n";
	return $text;
}

function REGISTRATION_CONFIRM() {
	$text  = '';
	$text .= 'You will not be able to login and access your profile until an administrator confirms your account. ' . "\n";
	$text .= 'If you entered an email address in your registration you will be notified when the account is confirmed.' . "\n";
	return $text;
}



}

?>
