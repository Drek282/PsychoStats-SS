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
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Divisions—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance and !$cms->user->is_admin()) previouspage('index.php');

// change this if you want the default sort of the division listing to be something else like 'wins'
$DEFAULT_SORT = 'win_percent, team_rdiff';

// collect url parameters ...
$validfields = array('season','sort','order','start','limit','xml');
$cms->theme->assign_request_vars($validfields, true);

// Set global season variable to default if undeclared.
if (!isset($season) or !is_numeric($season) or strlen($season) != 4) $season = $ps->get_season_c();
$season_c ??= $ps->get_season_c();

// Check to see if the season is in the database before we continue.
$cmd = "SELECT season FROM $ps->t_team_adv WHERE season=$season LIMIT 1";
$nodata = array();
$nodata = $ps->db->fetch_rows(1, $cmd);

// if $nodata is empty then delete the seasons_h table entry if it exists and reload.
if (empty($nodata)) {
	$cmd = "SELECT season_h FROM $ps->t_seasons_h WHERE season_h=$season LIMIT 1";
	$nodata = array();
	$nodata = $ps->db->fetch_rows(1, $cmd);
	if (!empty($nodata)) $ps->db->delete($ps->t_seasons_h, 'season_h', $season);
	
	// Reload current page.
	previouspage($php_scnm);
}
unset ($nodata);

// SET DEFAULTS—santized
$sort = ($sort and strlen($sort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $sort) : $DEFAULT_SORT;
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

$cms->theme->assign(array(
	'divisions'			=> $divisions,
	'divisions_table'	=> $table->render(),
	'totaldivisions'	=> $totaldivisions,
	'language_list'		=> $cms->theme->get_language_list(),
	'theme_list'		=> $cms->theme->get_theme_list(),
	'language'			=> $cms->theme->language,
	'seasons_h'			=> $ps->get_seasons_h(),
	'season'			=> $season,
	'season_c'			=> $season_c,
	'division'			=> $division,
	'wildcard'			=> $wildcard,
	'form_key'			=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'		=> $cookieconsent,
));

// display the output
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
	return preg_replace('/^(-|)0\./', '$1.', $val);
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
