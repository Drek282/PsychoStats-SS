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
 *	Version: $Id: index.php $
 */
define("PSYCHOSTATS_PAGE", true);
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Standings—PSSS');

// change this if you want the default sort of the team listing to be something else like 'wins'
$DEFAULT_SORT = 'win_percent, pythag';
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

// if $q is longer than 50 characters we have a problem
if (strlen($q) > 50) {
	$cms->full_page_err($basename, array(
		'message_title'	=> $cms->trans("Invalid Search String"),
		'message'		=> $cms->trans("Searches are limited to 50 characters in length."),
		'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
		'cookieconsent'	=> $cookieconsent,
	));
	exit();
}

# secondary sorts
if ($sort != 'win_percent, pythag') {
	switch ($sort) {
		case 'win_percent':		$sort = $sort . ", pythag"; break;
		case 'divisionname': 	$sort = $sort . ", win_percent"; break;
		case 'wins':			$sort = $sort . ", pythag"; break;
		case 'team_rdiff':		$sort = $sort . ", pythag"; break;
		case 'pythag':			$sort = $sort . ", team_rdiff"; break;
		case 'pythag_plus':		$sort = $sort . ", pythag"; break;
		default:				break;
	}
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

// reset $sort variable to first sort column
$sort_arr = explode(", ", $sort);
$sort = $sort_arr[0];
unset($sort_arr);

$baseurl = array('season' => $season, 'sort' => $sort, 'order' => $order, 'limit' => $limit);
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
	'prevrank'		=> array( 'nolabel' => true, 'callback' => 'rankchange' ),
	'team_n'			=> array( 'label' => $cms->trans("Team #") ),
	'team_name'			=> array( 'label' => $cms->trans("Team Name"), 'callback' => 'psss_table_team_link' ),
	'divisionname'			=> array( 'label' => $cms->trans("Division"), 'callback' => 'psss_table_division_link' ),
	'wins'			=> array( 'label' => $cms->trans("Wins") ),
	'losses'		=> array( 'label' => $cms->trans("Losses") ),
	'win_percent'			=> array( 'label' => $cms->trans("Win %"), 'callback' => 'negpos500' ),
	'games_back'			=> array( 'label' => $cms->trans("GB"), 'nosort' => true, 'tooltip' => $cms->trans("Playoff status and how many games behind division leader"), 'callback' => 'standings' ),
	'team_rdiff'			=> array( 'label' => $cms->trans("Run Differential"), 'tooltip' => $cms->trans("(Total Runs Scored - Total Runs Against) / 9 Innings"), 'sort2' => 'win_percent', 'callback' => 'negpos' ),
	'pythag'			=> array( 'label' => $cms->trans("Pythag"), 'tooltip' => $cms->trans("Pythagorean Expectation"), 'callback' => 'negpos500' ),
	'pythag_plus'			=> array( 'label' => $cms->trans("Pythag+"), 'tooltip' => $cms->trans("The difference between Win % and Pythag"), 'callback' => 'negpos' )
));
$table->column_attr('rank', 'class', 'first');
$table->column_attr('team_name', 'class', 'left');
$table->column_attr('pythag_plus', 'class', 'right');
$table->column_attr('win_percent', 'class', 'primary');
$table->column_attr('pythag', 'class', 'secondary');
//$table->column_attr('rank', 'class', 'left');
$table->header_attr('rank', 'colspan', '2');
$ps->index_table_mod($table);
$cms->filter('teams_table_object', $table);

// assign variables to the theme
$cms->theme->assign(array(
	'q'		=> $q,
	'search'	=> $search,
	'results'	=> $results,
	'search_blurb'	=> $cms->trans('Search criteria "<em>%s</em>" matched %d ranked teams out of %d total',
		psss_escape_html($q), $total['ranked'], $total['absolute']
	),
	'teams'	=> $teams,
	'teams_table'	=> $table->render(),
	'total'		=> $total,
	'language_list'	=> $cms->theme->get_language_list(),
	'theme_list'	=> $cms->theme->get_theme_list(),
	'language'	=> $cms->theme->language,
	'seasons_h'		=> $ps->get_seasons_h(),
	'season'		=> $season,
	'season_c'		=> $season_c,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
//$cms->theme->add_js('js/index.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

function rankchange($val, $team) {
	return rank_change($val, $team);
}

function negpos($val) {
	return neg_pos($val);
}

function negpos500($val) {
	return neg_pos_500($val);
}

function standings($val) {
	return gb_status($val);
}

?>
