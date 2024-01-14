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
$DEFAULT_SORT = 'win_percent, team_rdiff';

// collect url parameters ...
$validfields = array('season','sort','order','start','limit','xml');
$cms->theme->assign_request_vars($validfields, true);

// Set global season variable to default if undeclared.
if (!isset($season) or !is_numeric($season) or strlen($season) != 4) $season = $ps->get_season_c();
$season_c ??= $ps->get_season_c();

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

// Check to see if there is any data in the database before we continue.
$cmd = "SELECT season FROM $ps->t_team_adv LIMIT 1";

$r = array();
$r = $ps->db->fetch_rows(1, $cmd);

// if $r is empty then we have no data in the database
if (empty($r)) {
	$cms->full_page_err('awards', array(
		'message_title'	=> $cms->trans("No Stats Found"),
		'message'	=> $cms->trans("psss.py must be run before any stats will be shown."),
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

// Check to see if the season is in the database before we continue.
$cmd = "SELECT season FROM $ps->t_team_adv WHERE season=$season LIMIT 1";
$r = $ps->db->fetch_rows(1, $cmd);

// if $r is empty then the season is not in the database and someone is misbehaving
if (empty($r)) {
	$cms->full_page_err('awards', array(
		'message_title'	=> $cms->trans("Season Parameter Invalid"),
		'message'	=> $cms->trans("There is no data in the database for the season passed to the script. The season parameter should not be passed directly to the script."),
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
unset ($r);

// SET DEFAULTS—santized
$sort = (isset($sort) and strlen($sort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $sort) : $DEFAULT_SORT;
$order = trim(strtolower($order ?? ''));
if (!in_array($order, array('asc','desc'))) $order = 'desc';
if (!is_numeric($start) || $start < 0) $start = 0;
if (!is_numeric($limit) || $limit < 0 || $limit > 100) $limit = 6;

// sanitize sorts
$sort = ($ps->db->column_exists(array($ps->t_team_adv, $ps->t_team_def, $ps->t_team_off), $sort)) ? $sort : $DEFAULT_SORT;

# secondary sorts
if ($sort != 'win_percent, team_rdiff') {
	($sort == 'win_percent') ? $sort = $sort . ", team_rdiff" : $sort = $sort . ", win_percent";
}

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

// reset $sort variable to first sort column
$sort_arr = explode(", ", $sort);
$sort = $sort_arr[0];
unset($sort_arr);

// build a dynamic table that plugins can use to add custom columns of data
$table = $cms->new_table($divisions);
$table->if_no_data($cms->trans("No Divisions Found"));
$table->attr('class', 'ps-table ps-division-table');
$table->start_and_sort($start, $sort, $order);
$table->columns(array(
	'divisionname'		=> array( 'label' => $cms->trans("Division Name"), 'callback' => 'psss_table_division_link' ),
	'win_percent'			=> array( 'label' => $cms->trans("Win %"), 'tooltip' => $cms->trans("Division Average"), 'callback' => 'negpos500' ),
	'team_rdiff'			=> array( 'label' => $cms->trans("Run Differential"), 'tooltip' => $cms->trans("Division Average (Total Runs Scored - Total Runs Against) / 9 Innings"), 'callback' => 'negpos' ),
	'team_ra'		=> array( 'label' => $cms->trans("RA"), 'tooltip' => $cms->trans("Division Average Runs Against per 9 Innings"), 'callback' => 'one_decimal_zerozero' ),
	'run_support'			=> array( 'label' => $cms->trans("RS"), 'tooltip' => $cms->trans("Division Average Total Runs Scored per Game"), 'callback' => 'one_decimal_zero' ),
	'team_whip'			=> array( 'label' => $cms->trans("WHIP"), 'tooltip' => $cms->trans("Division Average (Hits + Walks)/Inning Pitched"), 'callback' => 'one_decimal_zerozero' ),
	'ops'		=> array( 'label' => $cms->trans("OPS"), 'tooltip' => $cms->trans("Division Average On Base Plus Slugging"), 'callback' => 'remove_zero_point_3' ),
	'team_drat'			=> array( 'label' => $cms->trans("DRAT"), 'tooltip' => $cms->trans("Division Average Team Defensive Rating:\n—all defensive stats combined into a single number, not including wild pitches\n—roughly equivalent to defensive runs saved per 9 innings"), 'callback' => 'remove_zero_point_2' ),
	'team_srat'			=> array( 'label' => $cms->trans("SRAT"), 'tooltip' => $cms->trans("Division Average Team Speed Rating:\n—all offensive stats affected by baserunning combined into a single number\n—roughly equivalent to runs scored per 9 innings affected by team speed"), 'callback' => 'remove_zero_point_2' ),
));
$table->column_attr('divisionname', 'class', 'left');
$table->column_attr('team_srat', 'class', 'right');
$table->column_attr('win_percent', 'class', 'primary');
$table->column_attr('team_rdiff', 'class', 'secondary');
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

function negpos($val) {
	$val = sprintf("%.2f", $val);
	return neg_pos($val);
}

function negpos500($val) {
	return neg_pos_500($val);
}

function remove_zero_point_2($val) {
	$val = sprintf("%.2f", $val);
	return preg_replace('/^0\./', '.', $val);
}

function remove_zero_point_3($val) {
	$val = sprintf("%.3f", $val);
	return preg_replace('/^0\./', '.', $val);
}

function one_decimal_zero($val) {
	return sprintf("%.1f", $val);
}

function one_decimal_zerozero($val) {
	return sprintf("%.2f", $val);
}


?>
