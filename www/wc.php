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
 *	Version: $Id: index.php 506 2008-07-02 14:29:49Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Wild Card—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance and !$cms->user->is_admin()) previouspage('index.php');

// change this if you want the default sort of the team listing to be something else like 'wins'
$DEFAULT_SORT = 'win_percent, pythag';
$DEFAULT_LIMIT = 20;

// collect url parameters ...
$validfields = array('sort','order','start','limit','q','search');
$cms->theme->assign_request_vars($validfields, true);

// SET DEFAULTS—sanitized
$sort = ($sort and strlen($sort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $sort) : $DEFAULT_SORT;
$order = trim(strtolower($order));
if (!in_array($order, array('asc','desc'))) $order = 'desc';
if (!is_numeric($start) || $start < 0) $start = 0;
if (!is_numeric($limit) || $limit < 0 || $limit > 20) $limit = $DEFAULT_LIMIT;

# secondary sorts
if ($sort != 'win_percent, pythag') {
	switch ($sort) {
		case 'win_percent':		$sort = $sort . ", pythag"; break;
		case 'wins':			$sort = $sort . ", pythag"; break;
		case 'team_rdiff':		$sort = $sort . ", pythag"; break;
		case 'pythag':			$sort = $sort . ", team_rdiff"; break;
		case 'pythag_plus':		$sort = $sort . ", pythag"; break;
		default:				break;
	}
}

// sanitize sorts
$sort = ($ps->db->column_exists(array($ps->t_team, $ps->t_team_wc, $ps->t_team_adv, $ps->t_team_def, $ps->t_team_off, $ps->t_team_ids_names), $sort)) ? $sort : $DEFAULT_SORT;

// If a language is passed from GET/POST update the user's cookie. 
if (isset($cms->input['language'])) {
	if ($cms->theme->is_language($cms->input['language'])) {
		$cms->session->opt('language', $cms->input['language']);
		$cms->session->save_session_options();
	} else {
		// report an error?
		// na... just silently ignore the language
//		trigger_error("Invalid theme specified!", E_USER_WARNING);
	}
	previouspage($php_scnm);
}

$total = array();
$results = array();

// determine the total teams found
$total['all'] = $ps->get_total_teams(array('allowall' => 1));
if ($results) {
	$total['ranked'] = $results['result_total'];
	$total['absolute'] = $results['abs_total'];
} else {
	$total['ranked']   = $ps->get_total_teams(array('allowall' => 0));
	$total['absolute'] = $total['all'];
}

// Get total number teams listed in wild card standings.
$totalranked = $ps->get_total_wc();

// Set global season_c variable to default if undeclared.
$season_c ??= $ps->get_season_c();

// fetch stats, etc...
$teams = $ps->get_wc_list(array(
	'season_c'	=> $season_c,
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

// build a dynamic table that plugins can use to add custom columns of data
$table = $cms->new_table($teams);
$table->if_no_data($cms->trans("No Teams Found"));
$table->attr('class', 'ps-table ps-team-table');
//$table->sort_baseurl($search ? array( 'search' => $search ) : array( 'q' => $q ));
$table->start_and_sort($start, $sort, $order);
$table->columns(array(
	'rank'			=> array( 'label' => $cms->trans("Rank") ),
	'prevrank'		=> array( 'nolabel' => true, 'callback' => 'rankchange' ),
	'team_n'			=> array( 'label' => $cms->trans("Team #") ),
	'team_name'			=> array( 'label' => $cms->trans("Team Name"), 'callback' => 'psss_table_team_link' ),
	'wins'			=> array( 'label' => $cms->trans("Wins") ),
	'losses'		=> array( 'label' => $cms->trans("Losses") ),
	'win_percent'			=> array( 'label' => $cms->trans("Win %"), 'callback' => 'negpos500' ),
	'games_back_wc'			=> array( 'label' => $cms->trans("GB"), 'nosort' => true, 'tooltip' => $cms->trans("Wildcard status and how many games out of wild card playoff position"), 'callback' => 'standings' ),
	'team_rdiff'			=> array( 'label' => $cms->trans("Run Differential"), 'tooltip' => $cms->trans("(Total Runs Scored - Total Runs Against) / 9 Innings"), 'callback' => 'negpos' ),
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

// Are there divisions or wilcards in this league?
$division = $ps->get_total_divisions() - 1;
$wildcard = $ps->get_total_wc();

// assign variables to the theme
$cms->theme->assign(array(
	'search'		=> $search,
	'results'		=> $results,
	'totalranked'	=> $totalranked,
	'teams'			=> $teams,
	'teams_table'	=> $table->render(),
	'total'			=> $total,
	'language_list'	=> $cms->theme->get_language_list(),
	'theme_list'	=> $cms->theme->get_theme_list(),
	'language'		=> $cms->theme->language,
	'season'		=> null,
	'season_c'		=> null,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
//$cms->theme->add_js('js/index.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

function rankchange($val, $team) {
	return rank_change($team);
}

function team_rank($val, $team) {
	return rank_change($team) . " " . $val;
}

function negpos($val) {
	return neg_pos($val);
}

function negpos500($val) {
	return neg_pos_500($val);
}

function remove_zero_point($val) {
	$val = preg_replace('/^0\./', '.', $val);
	return $val;
}

function standings($val) {
	return gb_status($val);
}

?>
