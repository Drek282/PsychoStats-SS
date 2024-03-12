<?php
/**
 *	This file is part of PsychoStats.
 *
 *	Written by Jason Morriss
 *	Copyright 2008 Jason Morriss
 *
 *	PsychoStats is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	PsychoStats is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with PsychoStats.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	Version: $Id: common.php 565 2008-10-10 12:27:02Z lifo $
 *
 *	Common entry point for all pages within PsychoStats.
 *      This file will setup the environment and initialize all objects needed.
 *      All pages must include this file first and foremost.
**/

// verify the page was viewed from a valid entry point.
if (!defined("PSYCHOSTATS_PAGE")) die("Unauthorized access to " . basename(__FILE__));

//define("PS_DEBUG", true);
//define("PS_THEME_DEV", true);

// Global PsychoStats version and release date. 
// These are updated automatically by the release packaging script 'rel.pl'.
define("PS_VERSION", '0.0.4b');
define("PS_RELEASE_DATE", 'today');

// define the directory where we live. Since this file is always 1 directory deeper
// we know the parent directory is the actual root. DOCUMENT_ROOT.
define("PS_ROOTDIR", rtrim(dirname(__DIR__), '/\\'));

// enable some sane error reporting (ignore notice errors) and turn off the magic. 
// we also want to to disable E_STRICT.
//error_reporting(E_ALL); 
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED); 
//set_magic_quotes_runtime(0);
/**/
ini_set('display_errors', 'On');
ini_set('log_errors', 'On');
/**/

// disable automatic compression since we allow the admin specify this with
// our own handler.
ini_set('zlib.output_compression', 'Off');

// setup global timer so we can show the 0.0000 benchmark on pages.
$TIMER = null;
if (!defined("NO_TIMER")) {
	require_once(PS_ROOTDIR . "/includes/class_timer.php");
	$TIMER = new Timer();
}

// IIS does not have REQUEST_URI defined (apache specific).
// This URI is handy in certain pages so we create it if needed.
if (empty($_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
	if (!empty($_SERVER['QUERY_STRING'])) {
		$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
}

// read in all of our required libraries for basic functionality!
require_once(PS_ROOTDIR . "/includes/functions.php");
require_once(PS_ROOTDIR . "/includes/class_DB.php");
require_once(PS_ROOTDIR . "/includes/class_PS.php");
require_once(PS_ROOTDIR . "/includes/class_CMS.php");

// load the basic config
$dbtype = $dbhost = $dbport = $dbname = $dbuser = $dbpass = $dbtblprefix = '';
if (file_exists(PS_ROOTDIR . "/config.php")) {
    require_once(PS_ROOTDIR . "/config.php");
} else {
    echo "You must install game support before you can use PsychoStats, please see INSTALL.md for details.";
    exit;
}

// don't proceed if the install directory still exists
if (is_dir(PS_ROOTDIR . "/install")) {
        echo "PsychoStats hasn't been properly installed, please see INSTALL.md for details.";
        exit;
}

// Initialize our global variables for PsychoStats. 
// Lets be nice to the global Name Space.
$ps		= null;				// global PsychoStats object
$cms 		= null;				// global PsychoCMS object
$php_scnm = $_SERVER['SCRIPT_NAME'];		// this is used so much we make sure it's global
list($oscript) = array_reverse(explode('/', $php_scnm));	// originating script variable
// Sanitize PHP_SELF and avoid XSS attacks.
// We use the constant in places we know we'll be outputting $PHP_SELF to the user
define("SAFE_PHP_SCNM", htmlentities($_SERVER['SCRIPT_NAME'], ENT_QUOTES, "UTF-8"));

// start PS object; all $dbxxxx variables are loaded from config.php
#$ps = new PS(array(
$ps = PsychoStats::create(array(
	'fatal'		=> 0,
	'dbtype'	=> $dbtype,
	'dbhost'	=> $dbhost,
	'dbport'	=> $dbport,
	'dbname'	=> $dbname,
	'dbuser'	=> $dbuser,
	'dbpass'	=> $dbpass,
	'dbtblprefix'	=> $dbtblprefix
));

// initialize some defaults if no pre-set values are present for required directories and urls
$t =& $ps->conf['theme']; //shortcut
if (empty($t['script_url'])) {
	$t['script_url'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); //dirname($PHP_SELF);
	if (defined("PSYCHOSTATS_ADMIN_PAGE") or defined("PSYCHOSTATS_SUBPAGE")) {
		$t['script_url'] = rtrim(dirname($t['script_url']), '/\\');
	}
}
// template directory is figured out here now, instead of leaving it null for theme class so that the admin
// pages can properly detect the main theme directory.
if (empty($t['template_dir'])) {
	$t['template_dir'] = catfile(PS_ROOTDIR, 'themes');
}
if (empty($t['root_img_dir'])) $t['root_img_dir'] = catfile(PS_ROOTDIR, 'img');
if (empty($t['root_img_url'])) $t['root_img_url'] = catfile(rtrim($t['script_url'], '/\\'), 'img');
if (empty($t['flags_dir'])) $t['flags_dir'] = catfile($t['root_img_dir'], 'flags');
if (empty($t['flags_url'])) $t['flags_url'] = catfile($t['root_img_url'], 'flags');
if (empty($t['icons_dir'])) $t['himgs_dir'] = catfile($t['root_img_dir'], 'help');
if (empty($t['icons_url'])) $t['himgs_url'] = catfile($t['root_img_url'], 'help');
if (empty($t['icons_dir'])) $t['icons_dir'] = catfile($t['root_img_dir'], 'icons');
if (empty($t['icons_url'])) $t['icons_url'] = catfile($t['root_img_url'], 'icons');

// verify the compile_dir is valid. create it if possible.
// If the dir is not valid try to find a valid directory or at least print out why.
// TODO ...

unset($t);

// start the PS CMS object
$cms = new PsychoCMS(array(
	'dbhandle'	=> &$ps->db,	// reuse db connection
	'plugin_dir'	=> PS_ROOTDIR . '/plugins',
	'site_url'	=> $site_url,	// from config.php
));

$cms->init();

///////////////////////////////////////////////////////////////
///////////    Code that applies to every page.    ////////////
///////////////////////////////////////////////////////////////

// create the form variable
$form = $cms->new_form();

// Get cookie consent status from the cookie if it exists.
$cms->session->options['cookieconsent'] ??= false;
($ps->conf['main']['security']['enable_cookieconsent']) ? $cookieconsent = $cms->session->options['cookieconsent'] : $cookieconsent = 1;
if (isset($cms->input['cookieconsent'])) {
	$cookieconsent = $cms->input['cookieconsent'];

	// Update cookie consent status in the cookie if they are accepted.
	// Delete coolies if they are rejected.
	if ($cookieconsent) {
		$cms->session->opt('cookieconsent', $cms->input['cookieconsent']);
		$cms->session->save_session_options();

		// save a new form key in the users session cookie
		// this will also be put into a 'hidden' field in the form
		if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());
		
	} else {
		$cms->session->delete_cookie();
		$cms->session->delete_cookie('_id');
		$cms->session->delete_cookie('_opts');
		$cms->session->delete_cookie('_login');
	}
	previouspage($php_scnm);
}

// Is PsychoStats in maintenance mode?
$maintenance = $ps->conf['main']['maintenance_mode']['enable'];

// Check to see if there is any data in the database before we continue.
$cmd = "SELECT season FROM $ps->t_team_adv LIMIT 1";
$nodata = array();
$nodata = $ps->db->fetch_rows(1, $cmd);

// if $nodata is empty then we have no data in the database
if (empty($nodata)) {
	$cms->full_page_err('awards', array(
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'message_title'	=> $cms->trans("No Stats Found"),
		'message'		=> $cms->trans("psss.py must be run before any stats will be shown."),
		'lastupdate'	=> $ps->get_lastupdate(),
		'division'		=> null,
		'wildcard'		=> null,
		'season'		=> null,
		'season_c'		=> null,
		'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
		'cookieconsent'	=> $cookieconsent,
	));
	exit();
}
unset ($nodata);

// If a language is passed from GET/POST update the user's cookie. 
if (isset($cms->input['language'])) {
	if ($cms->theme->is_language($cms->input['language'])) {
		$cms->session->opt('language', $cms->input['language']);
		$cms->session->save_session_options();

		// save a new form key in the users session cookie
		// this will also be put into a 'hidden' field in the form
		if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());
		
	} else {
		// report an error?
		// na... just silently ignore the language
//		trigger_error("Invalid theme specified!", E_USER_WARNING);
	}
	previouspage($php_scnm);
}

# Are there divisions or wilcards in this league?
$division = $ps->get_total_divisions() - 1;
$wildcard = $ps->get_total_wc();
$lastupdate	= $ps->get_lastupdate();

?>
