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
 *	Version: $Id: def.php $
 */
define("PSYCHOSTATS_PAGE", true);
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Defence—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance and !$cms->user->is_admin()) previouspage('index.php');

// change this if you want the default sort of the team listing to be something else like 'total_runs_against'
$DEFAULT_SORT = 'team_ra, team_era';
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
if (!in_array($order, array('asc','desc'))) $order = 'asc';
if (!is_numeric($start) || $start < 0) $start = 0;
if (!is_numeric($limit) || $limit < 0 || $limit > 100) $limit = $DEFAULT_LIMIT;
$q = trim($q ?? '');

// sanitize sorts
$sort = ($ps->db->column_exists(array($ps->t_team, $ps->t_team_adv, $ps->t_team_def, $ps->t_team_off, $ps->t_team_ids_names), $sort)) ? $sort : $DEFAULT_SORT;

# secondary sorts
if ($sort != 'team_ra, team_era') {
	($sort == 'team_ra') ? $sort = $sort . ", team_era" : $sort = $sort . ", team_ra";
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

// Generate league average runs against
$la_team_ra = 0;
foreach ($teams as $tm => $val) {
	$teams[$tm]['team_ra'] ??= 0;
    $la_team_ra = $la_team_ra + $teams[$tm]['team_ra'];
}
$total['ranked'] ??= 0;
if ($total['ranked'] > 0) {
	$la_team_ra = round($la_team_ra / $total['ranked'], 2);
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
	'rank'			=> array( 'label' => $cms->trans("Rank"), 'tooltip' => $cms->trans("Ranked by Team Winning Percentage") ),
	'team_n'			=> array( 'label' => $cms->trans("Team #") ),
	'team_name'			=> array( 'label' => $cms->trans("Team Name"), 'callback' => 'psss_table_team_link' ),
//	'divisionname'			=> array( 'label' => $cms->trans("Division"), 'callback' => 'ps_table_division_link' ),
	'team_era'			=> array( 'label' => $cms->trans("ERA"), 'tooltip' => $cms->trans("Team Earned Runs Against Average per 9 Innings") ),
	'team_ra'		=> array( 'label' => $cms->trans("RA"), 'tooltip' => $cms->trans("Team Runs Against Average per 9 Innings"), 'callback' => 'negposavg' ),
	'complete_games'			=> array( 'label' => $cms->trans("CG"), 'tooltip' => $cms->trans("Team Total Complete Games\n—Pitcher AAA excluded") ),
	'shutouts'			=> array( 'label' => $cms->trans("ShO"), 'tooltip' => $cms->trans("Team Total Shutouts") ),
	'team_saves'			=> array( 'label' => $cms->trans("Saves") ),
	'innings_pitched'			=> array( 'label' => $cms->trans("IP"), 'tooltip' => $cms->trans("Team Total Innings Pitched") ),
	'total_runs_against'			=> array( 'label' => $cms->trans("TRA"), 'tooltip' => $cms->trans("Team Total Runs Scored Against") ),
	'total_earned_runs_against'			=> array( 'label' => $cms->trans("TERA"), 'tooltip' => $cms->trans("Team Total Earned Runs Scored Against") ),
	'hits_surrendered'			=> array( 'label' => $cms->trans("Hits"), 'tooltip' => $cms->trans("Team Total Hits Against") ),
	'opp_batting_average'			=> array( 'label' => $cms->trans("BAA"), 'tooltip' => $cms->trans("Team Batting Average Against"), 'callback' => 'remove_zero_point' ),
	'opp_walks'			=> array( 'label' => $cms->trans("BBA"), 'tooltip' => $cms->trans("Team Total Walks Allowed") ),
	'team_whip'			=> array( 'label' => $cms->trans("WHIP"), 'tooltip' => $cms->trans("Team Average (Hits + Walks)/Inning Pitched") ),
	'opp_strikeouts'			=> array( 'label' => $cms->trans("K"), 'tooltip' => $cms->trans("Team Total Strikeouts by Pitchers") ),
	'outstanding_plays'			=> array( 'label' => $cms->trans("OP"), 'tooltip' => $cms->trans("Team Total Outstanding Defensive Plays") ),
	'double_plays_turned'			=> array( 'label' => $cms->trans("DP"), 'tooltip' => $cms->trans("Team Total Double Plays Turned") ),
	'fielding_errors'			=> array( 'label' => $cms->trans("E"), 'tooltip' => $cms->trans("Team Total Defensive Errors") ),
	'team_wild_pitches'			=> array( 'label' => $cms->trans("WP"), 'tooltip' => $cms->trans("Team Total Wild Pitches") ),
	'passed_balls'			=> array( 'label' => $cms->trans("PB"), 'tooltip' => $cms->trans("Team Total Passed Balls") ),
	'opp_stolen_bases'			=> array( 'label' => $cms->trans("OSB"), 'tooltip' => $cms->trans("Team Total Stolen Bases Allowed") ),
	'opp_caught_stealing'			=> array( 'label' => $cms->trans("OCS"), 'tooltip' => $cms->trans("Team Total Opponents Caught Stealing") ),
	'team_drat'			=> array( 'label' => $cms->trans("DRAT"), 'tooltip' => $cms->trans("Team Defensive Rating:\n—all defensive stats combined into a single number, not including wild pitches\n—roughly equivalent to defensive runs saved per 9 innings"), 'callback' => 'remove_zero_point' )
));
$table->column_attr('rank', 'class', 'first');
$table->column_attr('team_name', 'class', 'left');
$table->column_attr('team_drat', 'class', 'right');
$table->column_attr('team_ra', 'class', 'primary');
$table->column_attr('team_era', 'class', 'secondary');
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
	'la_team_ra'	=> $la_team_ra,
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
	global $la_team_ra;
	if ($val > $la_team_ra) {
		$output = sprintf("<span class='neg'>$val</span>");
	} else {
		$output = sprintf("<span class='pos'>$val</span>");
	}
	return $output;
}

?>
