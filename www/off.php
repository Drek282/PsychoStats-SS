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
 *	Version: $Id: off.php $
 */
define("PSYCHOSTATS_PAGE", true);
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Offence—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance and !$cms->user->is_admin()) previouspage('index.php');

// change this if you want the default sort of the team listing to be something else like 'batting_average'
$DEFAULT_SORT = 'run_support, woba';
$DEFAULT_LIMIT = 24;

// collect url parameters ...
$validfields = array('season','sort','order','start','limit','q','search');
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
if (!is_numeric($limit) || $limit < 0 || $limit > 100) $limit = $DEFAULT_LIMIT;
$q = trim($q ?? '');

// sanitize sorts
$sort = ($ps->db->column_exists(array($ps->t_team, $ps->t_team_adv, $ps->t_team_def, $ps->t_team_off, $ps->t_team_ids_names), $sort)) ? $sort : $DEFAULT_SORT;

# secondary sorts
if ($sort != 'run_support, woba') {
	($sort == 'run_support') ? $sort = $sort . ", woba" : $sort = $sort . ", run_support";
}

// determine the total teams found
$total = array();
$results = array();
if ($q != '') {
	// a new search was requested (a query string was given)
	$search = $ps->init_search();
	$matched = $ps->search_teams($search, array(
		'season'	=> $season,
		'phrase'	=> $q,
		'mode'		=> 'contains',
		'status'	=> 'ranked',
	));
	$results = $ps->get_search($search);
	
} else if ($ps->is_search($search)) {
	// an existing search was requested (new page or sort)
	$results = $ps->get_search($search);
	
} else {
	// no search, just fetch a list teams
	$search = '';
}
$total['all'] = $ps->get_total_teams(array('allowall' => 1));
if ($results) {
	$total['ranked'] = $results['result_total'];
	$total['absolute'] = $results['abs_total'];
} else {
	$total['ranked']   = $ps->get_total_teams(array('allowall' => 0));
	$total['absolute'] = $total['all'];
}

// fetch stats, etc...
$teams = $ps->get_team_list(array(
	'season'	=> $season,
	'results'	=> $results,
	'sort'		=> $sort,
	'order'		=> $order,
	'start'		=> $start,
	'limit'		=> $limit,
));

// Generate league average run support
$la_team_rs = 0;
foreach ($teams as $tm => $val) {
	$teams[$tm]['run_support'] ??= 0;
    $la_team_rs = $la_team_rs + $teams[$tm]['run_support'];
}
$total['ranked'] ??= 0;
if ($total['ranked'] > 0) {
	$la_team_rs = round($la_team_rs / $total['ranked'], 1);
}

// reset $sort variable to first sort column
$sort_arr = explode(", ", $sort);
$sort = $sort_arr[0];
unset($sort_arr);

$baseurl = array('sort' => $sort, 'order' => $order, 'limit' => $limit);
if ($search) {
	$baseurl['search'] = $search;
} else if ($q != '') {
	$baseurl['q'] = $q;
}

// build a dynamic table that plugins can use to add custom columns of data
$table = $cms->new_table($teams);
$table->if_no_data($cms->trans("No Search Results"));
$table->attr('class', 'ps-table ps-team-table');
$table->sort_baseurl($search ? array( 'search' => $search ) : array( 'q' => $q ));
$table->start_and_sort($start, $sort, $order);
$table->columns(array(
	'rank'					=> array( 'label' => $cms->trans("Rank"), 'tooltip' => $cms->trans("Ranked by Team Winning Percentage") ),
	'team_n'				=> array( 'label' => $cms->trans("Team #") ),
	'team_name'				=> array( 'label' => $cms->trans("Team Name"), 'callback' => 'psss_table_team_link' ),
	'run_support'			=> array( 'label' => $cms->trans("RS"), 'tooltip' => $cms->trans("Team Total Runs Scored per Game"), 'callback' => 'negposavg' ),
	'runs'					=> array( 'label' => $cms->trans("R"), 'tooltip' => $cms->trans("Team Total Runs Scored") ),
	'hits'					=> array( 'label' => $cms->trans("H"), 'tooltip' => $cms->trans("Team Total Hits") ),
	'doubles'				=> array( 'label' => $cms->trans("2B"), 'tooltip' => $cms->trans("Team Total Doubles") ),
	'triples'				=> array( 'label' => $cms->trans("3B"), 'tooltip' => $cms->trans("Team Total Triples") ),
	'home_runs'				=> array( 'label' => $cms->trans("HR"), 'tooltip' => $cms->trans("Team Total Home Runs") ),
	'team_rbis'				=> array( 'label' => $cms->trans("RBI"), 'tooltip' => $cms->trans("Team Total RBI&#39;s") ),
	'walks'					=> array( 'label' => $cms->trans("BB"), 'tooltip' => $cms->trans("Team Total Base on Balls") ),
	'strikeouts'			=> array( 'label' => $cms->trans("K"), 'tooltip' => $cms->trans("Team Total Strikeouts") ),
	'batting_average'		=> array( 'label' => $cms->trans("BA"), 'tooltip' => $cms->trans("Team Combined Batting Average"), 'callback' => 'remove_zero_point' ),
	'on_base_average'		=> array( 'label' => $cms->trans("OBA"), 'tooltip' => $cms->trans("Team Combined On Base Average"), 'callback' => 'remove_zero_point' ),
	'slugging_average'		=> array( 'label' => $cms->trans("SLG"), 'tooltip' => $cms->trans("Team Combined Slugging Average"), 'callback' => 'remove_zero_point' ),
	'ops'					=> array( 'label' => $cms->trans("OPS"), 'tooltip' => $cms->trans("Team Combined On Base Plus Slugging Average"), 'callback' => 'remove_zero_point' ),
	'woba'					=> array( 'label' => $cms->trans("wOBA"), 'tooltip' => $cms->trans("Team Weighted On Base Average:\n—does not include HBP and IBB data"), 'callback' => 'remove_zero_point' ),
	'sacrifice_hits'		=> array( 'label' => $cms->trans("SH"), 'tooltip' => $cms->trans("Team Total Sacrifice Hits") ),
	'sacrifice_fails'		=> array( 'label' => $cms->trans("F"), 'tooltip' => $cms->trans("Team Total Failed Sacrifice Attempts") ),
	'sacrifice_flies'		=> array( 'label' => $cms->trans("SF"), 'tooltip' => $cms->trans("Team Total Sacrifice Flies") ),
	'gidps'					=> array( 'label' => $cms->trans("GIDP"), 'tooltip' => $cms->trans("Team Total Grounded into Double Play") ),
	'stolen_bases'			=> array( 'label' => $cms->trans("SB"), 'tooltip' => $cms->trans("Team Total Stolen Bases") ),
	'caught_stealing'		=> array( 'label' => $cms->trans("CS"), 'tooltip' => $cms->trans("Team Total Caught Stealing") ),
	'left_on_base'			=> array( 'label' => $cms->trans("LOB"), 'tooltip' => $cms->trans("Team Total Runners Left on Base") ),
	'left_on_base_percent'	=> array( 'label' => $cms->trans("LOB %"), 'tooltip' => $cms->trans("(Team RBI - Team HR)/(Team Total Base Runners - Team HR)"), 'callback' => 'remove_zero_point' ),
	'team_srat'				=> array( 'label' => $cms->trans("SRAT"), 'tooltip' => $cms->trans("Team Speed Rating:\n—all offensive stats affected by baserunning combined into a single number\n—roughly equivalent to runs scored per 9 innings affected by team speed"), 'callback' => 'remove_zero_point' )
));
$table->column_attr('rank', 'class', 'first');
$table->column_attr('team_name', 'class', 'left');
$table->column_attr('team_srat', 'class', 'right');
$table->column_attr('run_support', 'class', 'primary');
//$table->column_attr('rank', 'class', 'left');
//$table->header_attr('rank', 'colspan', '2');
$ps->index_table_mod($table);
$cms->filter('teams_table_object', $table);

// assign variables to the theme
$cms->theme->assign(array(
	'q'				=> $q,
	'search'		=> $search,
	'results'		=> $results,
	'search_blurb'	=> $cms->trans('Search criteria "<em>%s</em>" matched %d ranked teams out of %d total',
		psss_escape_html($q), $total['ranked'], $total['absolute']
	),
	'la_team_rs'	=> $la_team_rs,
	'teams'			=> $teams,
	'teams_table'	=> $table->render(),
	'total'			=> $total,
	'language_list'	=> $cms->theme->get_language_list(),
	'theme_list'	=> $cms->theme->get_theme_list(),
	'language'		=> $cms->theme->language,
	'seasons_h'		=> $ps->get_seasons_h(),
	'season'		=> $season,
	'season_c'		=> $season_c,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
//$cms->theme->add_js('js/index.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

function remove_zero_point($val) {
	return preg_replace('/^(-|)0\./', '$1.', $val);
}

function negposavg($val) {
	global $la_team_rs;
	if ($val < $la_team_rs) {
		$output = sprintf("<span class='neg'>$val</span>");
	} else {
		$output = sprintf("<span class='pos'>$val</span>");
	}
	return $output;
}

?>
