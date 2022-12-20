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
 *	Version: $Id: divisions.php 450 2008-05-20 11:34:52Z lifo $
 */

define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('PsychoStats for Scoresheet - Divisions');

// change this if you want the default sort of the division listing to be something else like 'wins'
$DEFAULT_SORT = 'win_percent';

// collect url parameters ...
$validfields = array('season','sort','order','start','limit','xml');
$cms->theme->assign_request_vars($validfields, true);

// Set global season variable to default if undeclared.
$season ??= $ps->get_season_c();
$season_c ??= $ps->get_season_c();

// If a season is passed from GET/POST update $season. 
if (isset($cms->input['season'])) {
	$season = $cms->input['season'];
}

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

$sort = trim(strtolower($sort));
$order = trim(strtolower($order));
if (!preg_match('/^\w+$/', $sort)) $sort = $DEFAULT_SORT;
if (!in_array($order, array('asc','desc'))) $order = 'desc';
if (!is_numeric($start) || $start < 0) $start = 0;
if (!is_numeric($limit) || $limit < 0 || $limit > 100) $limit = 6;

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

// fetch stats, etc...
$totaldivisions  = $ps->get_total_divisions();

$divisions = $ps->get_division_list(array(
	'season'	=> $season,
	'sort'		=> $sort,
	'order'		=> $order,
	'start'		=> $start,
	'limit'		=> $limit,
));

// build a dynamic table that plugins can use to add custom columns of data
$table = $cms->new_table($divisions);
$table->if_no_data($cms->trans("No Divisions Found"));
$table->attr('class', 'ps-table ps-division-table');
$table->start_and_sort($start, $sort, $order);
$table->columns(array(
	'divisionname'		=> array( 'label' => $cms->trans("Division Name"), 'callback' => 'psss_table_division_link' ),
	'win_percent'			=> array( 'label' => $cms->trans("Win %"), 'tooltip' => $cms->trans("Division Average") ),
	'team_rdiff'			=> array( 'label' => $cms->trans("Run Differential"), 'tooltip' => $cms->trans("Division Average (Total Runs Scored - Total Runs Against) / 9 Innings") ),
	'team_ra'		=> array( 'label' => $cms->trans("RA"), 'tooltip' => $cms->trans("Division Average Runs Against per 9 Innings") ),
	'run_support'			=> array( 'label' => $cms->trans("RS"), 'tooltip' => $cms->trans("Division Average Total Runs Scored per Game") ),
	'team_whip'			=> array( 'label' => $cms->trans("WHIP"), 'tooltip' => $cms->trans("Division Average (Hits + Walks)/Inning Pitched") ),
	'ops'		=> array( 'label' => $cms->trans("OPS"), 'tooltip' => $cms->trans("Division Average On Base Plus Slugging") ),
	'team_drat'			=> array( 'label' => $cms->trans("DRAT"), 'tooltip' => $cms->trans("Division Average Team Defensive Rating:\n—all defensive stats combined into a single number, not including wild pitches\n—roughly equivalent to defensive runs saved per 9 innings") ),
	'team_srat'			=> array( 'label' => $cms->trans("SRAT"), 'tooltip' => $cms->trans("Division Average Team Speed Rating:\n—all offensive stats affected by baserunning combined into a single number\n—roughly equivalent to runs scored per 9 innings affected by team speed") ),
));
$table->column_attr('divisionname', 'class', 'left');
$table->column_attr('team_srat', 'class', 'right');
$ps->divisions_table_mod($table);
$cms->filter('divisions_table_object', $table);

# Are there divisions or wilcards in this league?
$division = $ps->get_total_divisions() - 1;
$wildcard = $ps->get_total_wc();

$cms->theme->assign(array(
	'divisions'		=> $divisions,
	'divisions_table'	=> $table->render(),
	'totaldivisions'	=> $totaldivisions,
	'language_list'	=> $cms->theme->get_language_list(),
	'theme_list'	=> $cms->theme->get_theme_list(),
	'language'	=> $cms->theme->language,
	'lastupdate'		=> $ps->get_lastupdate(),
	'seasons_h'		=> $ps->get_seasons_h(),
	'season'		=> $season,
	'season_c'		=> $season_c,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

function psss_table_division_link2($name, $division) {
	return psss_table_division_link($name, $division, false, false);
}


?>
