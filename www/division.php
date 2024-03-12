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
 *	Version: $Id: division.php 450 2008-05-20 11:34:52Z lifo $
 */

define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('Division—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance) previouspage('index.php');

$validfields = array(
	'id',
	'season',
	'asort','aorder','astart','alimit',	// advanced
	'dsort','dorder','dstart','dlimit',	// defence
	'osort','oorder','ostart','olimit',	// offence
    'xml'
);
$cms->theme->assign_request_vars($validfields, true);

// Set global season variable to default if undeclared.
if (!isset($season) or !is_numeric($season) or strlen($season) != 4) $season = $ps->get_season_c();
$season_c ??= $ps->get_season_c();

// Check to see if the season is in the database before we continue.
$cmd = "SELECT season FROM $ps->t_team_adv WHERE season=$season LIMIT 1";

$nodata = array();
$nodata = $ps->db->fetch_rows(1, $cmd);

// if $nodata is empty then the season is not in the database and someone is misbehaving
if (empty($nodata)) {
	$cms->full_page_err('awards', array(
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'message_title'	=> $cms->trans("Season Parameter Invalid"),
		'message'		=> $cms->trans("There is no data in the database for the season passed to the script. The season parameter should not be passed directly to the script."),
		'lastupdate'	=> $lastupdate,
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

// SET DEFAULTS—sanitized
$asort = (isset($asort) and strlen($asort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $asort) : 'win_percent, pythag';
$dsort = (isset($dsort) and strlen($dsort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $dsort) : 'team_ra, team_era';
$osort = (isset($osort) and strlen($osort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $osort) : 'run_support, woba';

// SET DEFAULTS—sanitized. Since they're basically the same for each list, we do this in a loop
foreach ($validfields as $var) {
	switch (substr($var, 1)) {
		case 'order':
			if (!$$var or !in_array($$var, array('asc', 'desc'))) $$var = 'desc';
			break;
		case 'start':
			if (!is_numeric($$var) || $$var < 0) $$var = 0;
			break;
		case 'limit':
			if (!is_numeric($$var) || $$var < 0 || $$var > 100) $$var = 10;
			break;
		default:
		        break;
	}
}

// sanitize sorts
$asort = ($ps->db->column_exists(array($ps->t_team_adv, $ps->t_team_def, $ps->t_team_off, $ps->t_team_ids_names), $asort)) ? $asort : 'win_percent, team_rdiff';
$dsort = ($ps->db->column_exists(array($ps->t_team_adv, $ps->t_team_def, $ps->t_team_off, $ps->t_team_ids_names), $dsort)) ? $dsort : 'team_ra, team_era';
$osort = ($ps->db->column_exists(array($ps->t_team_adv, $ps->t_team_def, $ps->t_team_off, $ps->t_team_ids_names), $osort)) ? $osort : 'run_support, woba';

## secondary sorts
# advanced table
if ($asort != 'win_percent, pythag') {
	switch ($asort) {
		case 'win_percent':
		case 'wins':
		case 'team_rdiff':
		case 'pythag_plus':		$asort = $asort . ", pythag"; break;
		case 'pythag':			$asort = $asort . ", team_rdiff"; break;
		default:				break;
	}
}
# def table
if ($dsort != 'team_ra, team_era') {
	($dsort == 'team_ra') ? $dsort = $dsort . ", team_era" : $dsort = $dsort . ", team_ra";
}
# off table
if ($osort != 'run_support, woba') {
	($osort == 'run_support') ? $osort = $osort . ", woba" : $osort = $osort . ", run_support";
}

$division = $ps->get_division(array(
	'season'	=> $season,
	'divisionname' 	=> $id,
	'asort'	=> $asort,
	'aorder'	=> $aorder,
	'astart'	=> $astart,
	'alimit'	=> $alimit,
	'dsort'	=> $dsort,
	'dorder'	=> $dorder,
	'dstart'	=> $dstart,
	'dlimit'	=> $dlimit,
	'osort'	=> $osort,
	'oorder'	=> $oorder,
	'ostart'	=> $ostart,
	'olimit'	=> $olimit,
	'afields'	=> '',
	'dfields'	=> '',
	'ofields'	=> '',
));

$division['divisionname'] ??= null;
$cms->theme->page_title(' for ' . $division['divisionname'], true);

$x = substr($xml ?? '',0,1);
if ($x == 'd') {		// division
}

// reset sort variables to first sort column
$sort_arr = explode(", ", $asort);
$asort = $sort_arr[0];
unset($sort_arr);
$sort_arr = explode(", ", $dsort);
$dsort = $sort_arr[0];
unset($sort_arr);
$sort_arr = explode(", ", $osort);
$osort = $sort_arr[0];
unset($sort_arr);

// overal standings table
$atable = $cms->new_table($division['advanced']);
$atable->attr('class', 'ps-table ps-team-table');
$atable->sort_baseurl(array( 'id' => $id, '_anchor' => 'advanced' ));
$atable->start_and_sort($astart, $asort, $aorder, 'a');
$atable->columns(array(
	'rank'			=> array( 'label' => $cms->trans("Rank") ),
	'prevrank'		=> array( 'nolabel' => true, 'callback' => 'rankchange' ),
	'team_n'			=> array( 'label' => $cms->trans("Team #") ),
	'team_name'			=> array( 'label' => $cms->trans("Team"), 'callback' => 'psss_table_team_link' ),
	'wins'			=> array( 'label' => $cms->trans("Wins") ),
	'losses'			=> array( 'label' => $cms->trans("Losses") ),
	'win_percent'			=> array( 'label' => $cms->trans("Win %"), 'callback' => 'negpos500' ),
	'games_back'			=> array( 'label' => $cms->trans("GB"), 'nosort' => true, 'tooltip' => $cms->trans("Playoff status and how many games behind division leader"), 'callback' => 'standings' ),
	'team_rdiff'			=> array( 'label' => $cms->trans("Run Differential"), 'tooltip' => $cms->trans("(Total Runs Scored - Total Runs Against) / 9 Innings"), 'callback' => 'negpos' ),
	'pythag'			=> array( 'label' => $cms->trans("Pythag"), 'tooltip' => $cms->trans("Pythagorean Expectation"), 'callback' => 'negpos500' ),
	'pythag_plus'			=> array( 'label' => $cms->trans("Pythag+"), 'tooltip' => $cms->trans("The difference between Win % and Pythag"), 'callback' => 'negpos' )
));
$atable->column_attr('rank', 'class', 'first');
$atable->column_attr('team_name', 'class', 'left');
$atable->column_attr('pythag_plus', 'class', 'right');
$atable->column_attr('win_percent', 'class', 'primary');
$atable->column_attr('pythag', 'class', 'secondary');
$ps->division_teams_table_mod($atable);
$atable->header_attr('rank', 'colspan', '2');
$cms->filter('division_advanced_table_object', $atable);

// if season is not current season, remove rank columns.
if ($season != $season_c) {
	$atable->remove_columns(array('rank'));
	$atable->remove_columns(array('prevrank'));
}


// build team defence table
$dtable = $cms->new_table($division['defence']);
$dtable->if_no_data($cms->trans("No Defensive Stats Found"));
$dtable->attr('class', 'ps-table ps-defence-table');
$dtable->sort_baseurl(array( 'id' => $id, '_anchor' => 'defence' ));
$dtable->start_and_sort($dstart, $dsort, $dorder, 'd');
$dtable->columns(array(
	'team_n'			=> array( 'label' => $cms->trans("Team #"), 'callback' => 'psss_table_team_link' ),
	'team_era'			=> array( 'label' => $cms->trans("ERA"), 'tooltip' => $cms->trans("Team Earned Runs Against Average per 9 Innings") ),
	'team_ra'		=> array( 'label' => $cms->trans("RA"), 'tooltip' => $cms->trans("Team Runs Against Average per 9 Innings"), 'callback' => 'negposraavg' ),
//	'complete_games'			=> array( 'label' => $cms->trans("Complete Games") ),
	'shutouts'			=> array( 'label' => $cms->trans("Shutouts") ),
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
	'team_drat'			=> array( 'label' => $cms->trans("DRAT"), 'tooltip' => $cms->trans("Team Defensive Rating:\n—all defensive stats combined into a single number, not including wild pitches\n—roughly equivalent to defensive runs saved per 9 innings"), 'callback' => 'remove_zero_point' ),
));
$dtable->column_attr('team_n', 'class', 'left');
$dtable->column_attr('team_drat', 'class', 'right');
$dtable->column_attr('team_ra', 'class', 'primary');
$ps->division_defence_table_mod($dtable);
$cms->filter('division_defence_table_object', $dtable);


// build team offence table
$otable = $cms->new_table($division['offence']);
$otable->if_no_data($cms->trans("No Offensive Stats Found"));
$otable->attr('class', 'ps-table ps-offence-table');
$otable->sort_baseurl(array( 'id' => $id, '_anchor' => 'offence' ));
$otable->start_and_sort($ostart, $osort, $oorder, 'o');
$otable->columns(array(
	'team_n'			=> array( 'label' => $cms->trans("Team #"), 'callback' => 'psss_table_team_link' ),
	'run_support'			=> array( 'label' => $cms->trans("RS"), 'tooltip' => $cms->trans("Team Total Runs Scored per Game"), 'callback' => 'negposrsavg' ),
	'runs'			=> array( 'label' => $cms->trans("R"), 'tooltip' => $cms->trans("Team Total Runs Scored") ),
	'hits'			=> array( 'label' => $cms->trans("H"), 'tooltip' => $cms->trans("Team Total Hits") ),
	'doubles'			=> array( 'label' => $cms->trans("2B"), 'tooltip' => $cms->trans("Team Total Doubles") ),
	'triples'			=> array( 'label' => $cms->trans("3B"), 'tooltip' => $cms->trans("Team Total Triples") ),
	'home_runs'			=> array( 'label' => $cms->trans("HR"), 'tooltip' => $cms->trans("Team Total Home Runs") ),
	'team_rbis'			=> array( 'label' => $cms->trans("RBI"), 'tooltip' => $cms->trans("Team Total RBI&#39;s") ),
	'walks'			=> array( 'label' => $cms->trans("BB"), 'tooltip' => $cms->trans("Team Total Base on Balls") ),
	'strikeouts'			=> array( 'label' => $cms->trans("K"), 'tooltip' => $cms->trans("Team Total Strikeouts") ),
	'batting_average'			=> array( 'label' => $cms->trans("BA"), 'tooltip' => $cms->trans("Team Combined Batting Average"), 'callback' => 'remove_zero_point' ),
	'on_base_average'			=> array( 'label' => $cms->trans("OBA"), 'tooltip' => $cms->trans("Team Combined On Base Average"), 'callback' => 'remove_zero_point' ),
	'slugging_average'			=> array( 'label' => $cms->trans("SLG"), 'tooltip' => $cms->trans("Team Combined Slugging Average"), 'callback' => 'remove_zero_point' ),
	'ops'		=> array( 'label' => $cms->trans("OPS"), 'tooltip' => $cms->trans("Team Combined On Base Plus Slugging Average"), 'callback' => 'remove_zero_point' ),
	'woba'			=> array( 'label' => $cms->trans("wOBA"), 'tooltip' => $cms->trans("Team Weighted On Base Average:\n—does not include HBP and IBB data"), 'callback' => 'remove_zero_point' ),
	'sacrifice_hits'		=> array( 'label' => $cms->trans("SH"), 'tooltip' => $cms->trans("Team Total Sacrifice Hits") ),
	'sacrifice_fails'		=> array( 'label' => $cms->trans("F"), 'tooltip' => $cms->trans("Team Total Failed Sacrifice Attempts") ),
	'sacrifice_flies'		=> array( 'label' => $cms->trans("SF"), 'tooltip' => $cms->trans("Team Total Sacrifice Flies") ),
	'gidps'		=> array( 'label' => $cms->trans("GIDP"), 'tooltip' => $cms->trans("Team Total Grounded into Double Play") ),
	'stolen_bases'		=> array( 'label' => $cms->trans("SB"), 'tooltip' => $cms->trans("Team Total Stolen Bases") ),
	'caught_stealing'		=> array( 'label' => $cms->trans("CS"), 'tooltip' => $cms->trans("Team Total Caught Stealing") ),
	'left_on_base'			=> array( 'label' => $cms->trans("LOB"), 'tooltip' => $cms->trans("Team Total Runners Left on Base") ),
	'left_on_base_percent'			=> array( 'label' => $cms->trans("LOB %"), 'tooltip' => $cms->trans("(Team Total Base Runners - Team HR)/(Team RBI - Team HR)"), 'callback' => 'remove_zero_point' ),
	'team_srat'			=> array( 'label' => $cms->trans("SRAT"), 'tooltip' => $cms->trans("Team Speed Rating:\n—all offensive stats affected by baserunning combined into a single number\n—roughly equivalent to runs scored per 9 innings affected by team speed"), 'callback' => 'remove_zero_point' ),
));
$otable->column_attr('team_n', 'class', 'left');
$otable->column_attr('team_srat', 'class', 'right');
$otable->column_attr('run_support', 'class', 'primary');
$ps->division_offence_table_mod($otable);
$cms->filter('division_offence_table_object', $otable);

// Number formats.
$division['team_rdiff'] = sprintf("%.2f", $division['team_rdiff']);
$division['team_drat'] = sprintf("%.2f", $division['team_drat']);
$division['team_srat'] = sprintf("%.2f", $division['team_srat']);

// Declare shades array.
$shades = array(
	's_division_rundown'	=> null,
	's_modactions'			=> null,
	's_division_average'	=> null,
	's_divisionadvanced'	=> null,
	's_divisiondefence'		=> null,
	's_divisionoffence'		=> null,
);

$team_ra = $division['team_ra'];
$run_support = $division['run_support'];
$cms->theme->assign(array(
	'oscript'			=> $oscript,
	'maintenance'	=> $maintenance,
	'division'			=> $division,
	'advanced_table'	=> $atable->render(),
	'defence_table'		=> $dtable->render(),
	'offence_table'		=> $otable->render(),
	'lastupdate'		=> $lastupdate,
	'seasons_h'			=> $ps->get_seasons_h(),
	'season'			=> $season,
	'season_c'			=> $season_c,
	'wildcard'			=> $wildcard,
	'shades'			=> $shades,
	'form_key'			=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'		=> $cookieconsent,
));

$basename = basename(__FILE__, '.php');
if ($division['divisionname']) {
	// allow mods to have their own section on the left side bar
	$ps->division_left_column_mod($division, $cms->theme);

	$cms->theme->add_css('css/2column.css');	// this page has a left column
	$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
} else {
	$cms->full_page_err($basename, array(
		'message_title'	=> $cms->trans("No Division Found!"),
		'message'		=> $cms->trans("Invalid division ID specified.") . " " . $cms->trans("Please go back and try again.")
	));
}

function rankchange($val, $team) {
	return rank_change($team);
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

function remove_zero_point($val) {
	return preg_replace('/^0\./', '.', $val);
}

function one_decimal_zero($val) {
	return sprintf("%.1f", $val);
}

function one_decimal_zerozero($val) {
	return sprintf("%.2f", $val);
}

function negposraavg($val) {
	global $team_ra;
	if ($val > $team_ra) {
		$output = sprintf("<span class='neg'>$val</span>");
	} else {
		$output = sprintf("<span class='pos'>$val</span>");
	}
	return $output;
}

function negposrsavg($val) {
	global $run_support;
	$val = sprintf("%.1f", $val);
	if ($val < $run_support) {
		$output = sprintf("<span class='neg'>$val</span>");
	} else {
		$output = sprintf("<span class='pos'>$val</span>");
	}
	return $output;
}

?>
