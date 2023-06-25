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
 *	Version $Id: awards.php 495 2008-06-18 18:41:37Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('PsychoStats - Hall of Fame');

// create the form variable
$form = $cms->new_form();

// Get cookie consent status from the cookie if it exists.
$cms->session->options['cookieconsent'] ??= false;
$cookieconsent = $cms->session->options['cookieconsent'];
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
		$cms->session->delete_cookie('_opts');
	}
	previouspage($php_scnm);
}

// Check to see if there is any data in the database before we continue.
$cmd = "SELECT * FROM $ps->t_team_adv LIMIT 1";

$results = array();
$results = $ps->db->fetch_rows(1, $cmd);

// if $results is empty then we have no data in the database
if (empty($results)) {
	$cms->full_page_err('awards', array(
		'message_title'	=> $cms->trans("No Stats Found"),
		'message'	=> $cms->trans("psss.py must be run before any stats will be shown."),
		'lastupdate'	=> $ps->get_lastupdate(),
		'division'		=> null,
		'wildcard'		=> null,
		'season_c'		=> null,
		'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
		'cookieconsent'	=> $cookieconsent,
	));
	exit();
}
unset ($results);

// collect url parameters ...
$validfields = array('t');
$cms->theme->assign_request_vars($validfields, true);

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

if (!is_numeric($t)) $t = '';
$_t = $ps->db->escape($t, true);

// Defaults limit.
$DEFAULT_LIMIT = 5;

$order ??= 'desc';
$limit ??= $DEFAULT_LIMIT;

unset($list);

// Are there divisions or wilcards in this league?
$division = $ps->get_total_divisions() - 1;
$wildcard = $ps->get_total_wc();

// Get list of teams and the division titles and championships count.
$dt_count = $ps->get_tc_count('dt', $limit);
$lc_count = $ps->get_tc_count('lc', $limit);

// Grab list of awards.
$cmd = "SELECT * FROM $ps->t_config_awards";

$results = array();
$results = $ps->db->fetch_rows(1, $cmd);

// if $results is empty then we have no awards in the database
if (empty($results)) {
	$cms->full_page_err('awards', array(
		'message_title'	=> $cms->trans("No Awards Found"),
		'message'	=> $cms->trans("There are currently no awards to display."),
		'lastupdate'	=> $ps->get_lastupdate(),
		'division'		=> $division,
		'wildcard'		=> $wildcard,
		'season_c'		=> null,
		'cookieconsent'	=> $cookieconsent,
	));
	exit();
}

// Iterate through list of awards.
foreach ($results as $a => $var) {

	// Only process award if it is enabled.
	if ($results[$a]['enabled'] == 0) continue;

	// fetch award...
	$awards[$a] = $ps->get_award(array(
		'id'			=> $results[$a]['id'],
		'enabled'		=> $results[$a]['enabled'],
		'idx'			=> $results[$a]['idx'],
		'negative'		=> $results[$a]['negative'],
		'award_name'	=> $results[$a]['award_name'],
		'groupname'		=> $results[$a]['groupname'],
		'phrase'		=> $results[$a]['phrase'],
		'expr'			=> $results[$a]['expr'],
		'where'			=> $results[$a]['where'],
		'format'		=> $results[$a]['format'],
		'description'	=> $results[$a]['description'],
		'order'			=> $results[$a]['order'],
		'limit'			=> $results[$a]['limit'],
	));
}
unset ($results);

// Sort the array by idx.
$idx = array_column($awards, 'idx');
array_multisort($idx, SORT_ASC, $awards);
unset ($idx);
//print_r($awards);

// Declare shades array.
$shades = array(
	's_titles'		=> null,
	's_championships'		=> null,
);

// assign variables to the theme
$cms->theme->assign(array(
	'page'			=> basename(__FILE__,'.php'),
	'awards'		=> $awards,
	'language_list'	=> $cms->theme->get_language_list(),
	'theme_list'	=> $cms->theme->get_theme_list(),
	'language'		=> $cms->theme->language,
	'lastupdate'	=> $ps->get_lastupdate(),
	'season_c'		=> null,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'team_id'		=> $t,
	'dt_count'		=> $dt_count,
	'lc_count'		=> $lc_count,
	'shades'		=> $shades,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/2column.css');	// this page has a left column
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

?>
