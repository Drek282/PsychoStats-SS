<?php
/*
	en_US.php
	$Id: en_US.php 457 2008-05-21 16:23:14Z lifo $

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

class PsychoLanguage_acp_en_US extends PsychoLanguage {

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
	'%d options updated: %s' =>
		'',
	'%d teams were deleted successfully' =>
		'',
	'%d users were confirmed successfully' =>
		'',
	'%d users were deleted successfully' =>
		'',
	'\'%s\' is already defined to another unique ID' =>
		'',
	'A note about log streams' =>
		'',
	'A password must be entered for new users' =>
		'',
	'Access' =>
		'',
	'Access Level' =>
		'',
	'Administrator Login' =>
		'',
	'Advanced usage only' =>
		'',
	'AIM' =>
		'',
	'Alias' =>
		'',
	'Aliases' =>
		'',
	'Aliases have been updated' =>
		'',
	'all' =>
		'',
	'All informational fields are optional and used solely for display purposes.' =>
		'',
	'All Teams' =>
		'',
	'All Users' =>
		'',
	'Allowed Tags' =>
		'',
	'allow_url_fopen INI setting is disabled.' =>
		'',
	'Are you sure you want to delete all aliases for this unique id?' =>
		'',
	'Are you sure you want to delete the award?' =>
		'',
	'Are you sure you want to delete the team?' =>
		'',
	'Are you sure you want to delete the selected teams?' =>
		'',
	'Are you sure you want to delete the selected users?' =>
		'',
	'Are you sure you want to delete the user?' =>
		'',
	'Are you sure you want to install this theme?' =>
		'',
	'Ascending' =>
		'',
	'Authentication Failed' =>
		'',
	'Authentication Failed! Note: the remote server must have PasswordAuthentication set to \'yes\' in the sshd_config file.' =>
		'',
	'Author' =>
		'',
	'Award' =>
		'',
	'Award Class' =>
		'',
	'Award Name' =>
		'',
	'Award Phrase' =>
		'',
	'Award Type' =>
		'',
	'Awards' =>
		'',
	'Cancel' =>
		'',
	'Change Password?' =>
		'',
	'Changes will not be applied until the next page request.' =>
		'',
	'Checked if not ignored' =>
		'',
	'Division Management' =>
		'',
	'Division Profiles?' =>
		'',
	'Division Tag' =>
		'',
	'Division Names' =>
		'',
	'Class' =>
		'',
	'Click here to add a log source' =>
		'',
	'Click here to add an award' =>
		'',
	'Click here to clear your avatar.' =>
		'',
	'Click here to clear your flag.' =>
		'',
	'Click on a flag to select it.' =>
		'',
	'Click on an avatar to select it.' =>
		'',
	'Click to Disable Plugin' =>
		'',
	'Click to Disable Theme' =>
		'',
	'Click to Enable Plugin' =>
		'',
	'Click to Enable Theme' =>
		'',
	'Click to install plugin' =>
		'',
	'Click to make this the default theme' =>
		'',
	'Click to Uninstall Plugin' =>
		'',
	'Click to Uninstall Theme' =>
		'',
	'Code' =>
		'',
	'Code File' =>
		'',
	'Config' =>
		'',
	'Config Option Editor' =>
		'',
	'Config Type' =>
		'',
	'Configuration Menu' =>
		'',
	'Configuration Updated Successfully' =>
		'',
	'Confirm' =>
		'',
	'Confirm deletion?' =>
		'',
	'Confirmed' =>
		'',
	'Confirmed?' =>
		'',
	'Conn IP' =>
		'',
	'Connected to FTP server, however the path entered does not exist' =>
		'',
	'Connected to SFTP server, however the path entered does not exist' =>
		'',
	'Connection IP' =>
		'',
	'Controls' =>
		'',
	'Country Code' =>
		'',
	'Create Division' =>
		'',
	'Create user for this team' =>
		'',
	'Current Avatar Icons' =>
		'',
	'Database Error' =>
		'',
	'Database was reset!' =>
		'',
	'Delete' =>
		'',
	'Delete Selected' =>
		'',
	'Deleting' =>
		'',
	'Deleting a team does not prevent them from re-appearing in the stats.' =>
		'',
	'Deleting more than a few teams at a time may take too long and timeout the request.' =>
		'',
	'Descending' =>
		'',
	'Description' =>
		'',
	'Discord ID' =>
		'',
	'Download as Text' =>
		'',
	'E' =>
		'',
	'Edit' =>
		'',
	'Edit user for this team' =>
		'',
	'Email' =>
		'',
	'Enabled?' =>
		'',
	'Enactor' =>
		'',
	'Enactor Team' =>
		'',
	'Enter the URL location of the theme.xml for the theme you want to install.' =>
		'',
	'Erase' =>
		'',
	'Error copying new image to icon directory!' =>
		'',
	'Error deleting team: ' =>
		'',
	'Error deleting user: ' =>
		'',
	'Error installing plugin:' =>
		'',
	'Error loading icons' =>
		'',
	'Error loading plugin code!' =>
		'',
	'Error Logs' =>
		'',
	'Error retreiving user from database' =>
		'',
	'Error saving user: ' =>
		'',
	'Error writting to database' =>
		'',
	'Error writting to database: %s' =>
		'',
	'Example' =>
		'',
	'Expression' =>
		'',
	'Fatal Error' =>
		'',
	'Fetch' =>
		'',
	'File' =>
		'',
	'File \'%s\' uploaded successfully!' =>
		'',
	'File download is too large' =>
		'',
	'File Size' =>
		'',
	'File Type' =>
		'',
	'Filename must have no spaces or path' =>
		'',
	'Filter' =>
		'',
	'For more information see the ' =>
		'',
	'Format' =>
		'',
	'FTP support not available in this installation of PHP' =>
		'',
	'Game' =>
		'',
	'Game Type' =>
		'',
	'Gametype' =>
		'',
	'Global' =>
		'',
	'Goto to author\'s website' =>
		'',
	'Group Title' =>
		'',
	'Help Text' =>
		'',
	'Host' =>
		'',
	'HTML Logo' =>
		'',
	'Icon \'%s\' deleted successfully' =>
		'',
	'Icon \'%s\' does not exist' =>
		'',
	'Icons' =>
		'',
	'ICQ' =>
		'',
	'If you do not remove this directory anyone will be able to access your database!!' =>
		'',
	'If you know logs exist then try enabling \'Passive Mode\' and test again' =>
		'',
	'Ignore?' =>
		'',
	'Image dimensions are too big' =>
		'',
	'Image name can not start with a period' =>
		'',
	'Image size is too large' =>
		'',
	'Image type is invalid' =>
		'',
	'Image type must be one of the following:' =>
		'',
	'Image type of URL must be one of the following:' =>
		'',
	'Informational Fields' =>
		'',
	'Input Type' =>
		'',
	'Install' =>
		'',
	'Install Date' =>
		'',
	'Install new theme' =>
		'',
	'Installed Plugins' =>
		'',
	'Installed Themes' =>
		'',
	'Invalid access level specified' =>
		'',
	'Invalid award ID Specified' =>
		'',
	'Invalid divisionname ID Specified' =>
		'',
	'Invalid Conf ID Specified' =>
		'',
	'Invalid team ID Specified' =>
		'',
	'Invalid Plugin Specified' =>
		'',
	'Invalid plugin was specified! Only plugins in the pending list can be installed.' =>
		'',
	'Invalid protocol selected' =>
		'',
	'Invalid regex syntax; See http://php.net/pcre for details' =>
		'',
	'Invalid tags were removed.' =>
		'',
	'Invalid Theme Specified' =>
		'',
	'Invalid User ID Specified' =>
		'',
	'Invalid username or password' =>
		'',
	'Is award enabled?' =>
		'',
	'Keep' =>
		'',
	'Layout Settings' =>
		'',
	'Left' =>
		'',
	'Loading avatars, please wait' =>
		'',
	'Loading flags, please wait' =>
		'',
	'Login' =>
		'',
	'Logout' =>
		'',
	'Manage' =>
		'',
	'Manage Your Stats' =>
		'',
	'Management Menu' =>
		'',
	'Map' =>
		'',
	'Match Type' =>
		'',
	'Maximum Results' =>
		'',
	'Message' =>
		'',
	'Mini Avatar' =>
		'',
	'mini avatars' =>
		'',
	'Mod' =>
		'',
	'MOD Type' =>
		'',
	'Modtype' =>
		'',
	'MSN' =>
		'',
	'Must be an alphanumeric word with no spaces (a-z, 0-9, _ only)' =>
		'',
	'Name' =>
		'',
	'Negative Award?' =>
		'',
	'New' =>
		'',
	'New Alias' =>
		'',
	'New Award' =>
		'',
	'New Divisionname' =>
		'',
	'New Password' =>
		'',
	'New User' =>
		'',
	'New Variable' =>
		'',
	'Next' =>
		'',
	'No' =>
		'',
	'No Administrator session found' =>
		'',
	'No alias can be the same as the unique ID' =>
		'',
	'No Awards Defined' =>
		'',
	'No Changes' =>
		'',
	'No config changes were made' =>
		'',
	'No Error Logs Found' =>
		'',
	'No files exist! Please verify the path entered' =>
		'',
	'No Icons Found' =>
		'',
	'No matching config' =>
		'',
	'No Pending Plugins' =>
		'',
	'No Pending Plugins Found' =>
		'',
	'No Plugins Installed' =>
		'',
	'No Team Aliases' =>
		'',
	'No Teams Available' =>
		'',
	'No Themes Installed' =>
		'',
	'No Users Found' =>
		'',
	'Non-Admin Users?' =>
		'',
	'none' =>
		'',
	'Normal configuration options should not be edited from this form unless you know what you\'re doing.' =>
		'',
	'Not Confirmed' =>
		'',
	'Note' =>
		'',
	'Note: If a new password was entered you will have to re-enter it and save the log source now.' =>
		'',
	'Note: This test mechanism does not support public key authentication.' =>
		'',
	'on this server' =>
		'',
	'Only enter a password if you want to change it from the current password.' =>
		'',
	'Operation Failed!' =>
		'',
	'Operation was successful!' =>
		'',
	'Options' =>
		'',
	'Order' =>
		'',
	'Original file was overwritten' =>
		'',
	'Override' =>
		'',
	'Override Tag' =>
		'',
	'parent' =>
		'',
	'Passive Mode?' =>
		'',
	'Password' =>
		'',
	'Passwords do not match' =>
		'',
	'Passwords do not match; please try again' =>
		'',
	'Passwords do not match; please try again.' =>
		'',
	'Pattern Match' =>
		'',
	'Pending Plugins' =>
		'',
	'Pending Users' =>
		'',
	'Permission denied' =>
		'',
	'Permission Denied' =>
		'',
	'Permissions Error!' =>
		'',
	'Plain' =>
		'',
	'Plain text only' =>
		'',
	'Please confirm theme installation!' =>
		'',
	'Please fix permissions.' =>
		'',
	'Please select a valid type from the list' =>
		'',
	'Please use a value greater than or equal to 0.00' =>
		'',
	'Plugin' =>
		'',
	'Plugin failed to install but did not give a reason why. Contact the plugin author for help.' =>
		'',
	'Plugin Installation Error' =>
		'',
	'Plugin Uninstall Error' =>
		'',
	'Plugin was disabled' =>
		'',
	'Plugin was enabled' =>
		'',
	'Plugin was uninstalled' =>
		'',
	'Plugins' =>
		'',
	'Plugins Not Installed' =>
		'',
	'Port' =>
		'',
	'Preview' =>
		'',
	'Preview of ' =>
		'',
	'Previous' =>
		'',
	'Protocol' =>
		'',
	'PsychoStats ACP' =>
		'',
	'Query' =>
		'',
	'Query Type' =>
		'',
	'Ranked' =>
		'',
	'Rcon' =>
		'',
	'RCON Password' =>
		'',
	'Re-enter password' =>
		'',
	'Read Only' =>
		'',
	'Real Name' =>
		'',
	'Recursive Depth' =>
		'',
	'Recursively Find Logs?' =>
		'',
	'Regex' =>
		'',
	'Register a user for this team by entering a username and password below.' =>
		'',
	'Register new user' =>
		'',
	'Regular Expression' =>
		'',
	'Reinstall' =>
		'',
	'Reset' =>
		'',
	'Reset All Stats!' =>
		'',
	'Reset Database' =>
		'',
	'Resetting team statistics can not be undone!' =>
		'',
	'Resubmit to try again.' =>
		'',
	'Retype Password' =>
		'',
	'Right' =>
		'',
	'Save' =>
		'',
	'Search' =>
		'',
	'Search Config' =>
		'',
	'Section' =>
		'',
	'See %s for more information' =>
		'',
	'Select All' =>
		'',
	'Select Theme' =>
		'',
	'Select theme to reinstall' =>
		'',
	'Severity' =>
		'',
	'Short Name' =>
		'',
	'Stats' =>
		'',
	'stats' =>
		'',
	'Status' =>
		'',
	'T' =>
		'',
	'Team' =>
		'',
	'Team Aliases?' =>
		'',
	'Team Management' =>
		'',
	'Team Name' =>
		'',
	'Team Profiles?' =>
		'',
	'Teams' =>
		'',
	'Teams Deleted!' =>
		'',
	'Test' =>
		'',
	'Testing \'stream\' sources is not possible.' =>
		'',
	'Testing Results' =>
		'',
	'The database has been reset. Stats will be empty until your next stats update.' =>
		'',
	'The Discord ID is not in the correct format.' =>
		'',
	'The icons directory is not writable.' =>
		'',
	'The installation directory should be removed after installation is completed!' =>
		'',
	'The layout settings define how the config option is displayed in the main configuration form' =>
		'',
	'The URL does not point to an image' =>
		'',
	'The web address is unreachable.' =>
		'',
	'Theme' =>
		'',
	'Theme \'%s\' is now the default theme' =>
		'',
	'Theme \'%s\' was disabled' =>
		'',
	'Theme \'%s\' was enabled' =>
		'',
	'Theme \'%s\' was uninstalled successfully (note: directory was not deleted)' =>
		'',
	'Theme directory <em>{$conf.theme.template_dir|escape}</em> is not writable by web server.' =>
		'',
	'Themes' =>
		'',
	'Themes can not be installed!' =>
		'',
	'Themes should only be installed from trusted sources!' =>
		'',
	'There was an error uninstalling the plugin' =>
		'',
	'These themes are already in your themes directory but are not installed in your database.' =>
		'',
	'This field can not be blank' =>
		'',
	'This is the default theme' =>
		'',
	'This unique ID is already defined' =>
		'',
	'This will allow the user to login and modify their profile.' =>
		'',
	'Time' =>
		'',
	'Timestamp' =>
		'',
	'Title' =>
		'',
	'Toggle flags' =>
		'',
	'Toggle gallery' =>
		'',
	'Twitch Channel Name' =>
		'',
	'Twitch User Name' =>
		'',
	'Twitch user name not in correct format.' =>
		'',
	'Type' =>
		'',
	'Unique ID' =>
		'',
	'Unknown' =>
		'',
	'Unknown error while deleting file' =>
		'',
	'Unknown team alias specified' =>
		'',
	'Update Successfull' =>
		'',
	'Updated' =>
		'',
	'Upload from file' =>
		'',
	'Upload from URL' =>
		'',
	'Upload New Icon' =>
		'',
	'Uploaded icon is invalid' =>
		'',
	'Use Blank Password' =>
		'',
	'User' =>
		'',
	'User Management' =>
		'',
	'Username' =>
		'',
	'Username already exists under a different user' =>
		'',
	'Username already exists; please try another name' =>
		'',
	'Users Confirmed!' =>
		'',
	'Users Deleted!' =>
		'',
	'V' =>
		'',
	'Value' =>
		'',
	'Variable' =>
		'',
	'Variable Label' =>
		'',
	'Verify Codes' =>
		'',
	'Verify the host and port are correct' =>
		'',
	'Version' =>
		'',
	'View Error Logs' =>
		'',
	'Warning' =>
		'',
	'was disabled successfully!' =>
		'',
	'was enabled successfully!' =>
		'',
	'was successfully uninstalled. It will no longer load after this current page request.' =>
		'',
	'Website' =>
		'',
	'Weight' =>
		'',
	'Where Clause' =>
		'',
	'With selected' =>
		'',
	'XML URL Location' =>
		'',
	'Yes' =>
		'',
	'You can also reinstall a local theme in the list below.' =>
		'',
	'You can not disable the active theme' =>
		'',
	'You can not uninstall the default or currently active theme!' =>
		'',
	'You can not upload any new icons until the permissions are corrected.' =>
		'',
	'You must <strong style="color: red">re-login</strong> to access the Administrator Control Panel' =>
		'',
	'You must enter the game type' =>
		'',
	'Your information is never sold or given away to third parties.' =>
		'',
	'Your server environment will not allow new themes to be installed due to the following reasons.' =>
		'',
	'YouTube Channel Name' =>
		'',
	'YouTube User Name' =>
		'',
	'YouTube user name not in correct format.' =>
		'',

	) + $this->map;
}

function PsychoLanguage_acp_en_US() {
    self::__construct();
}

// if a translation keyword maps to a method below then the matching method should return the translated string.
// This is most useful for those large blocks of text in the theme. 


}

?>
