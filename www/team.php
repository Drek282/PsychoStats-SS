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
 *	Version: $Id: team.php 530 2021-07-27 $
 */

define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('PsychoStats for Scoresheet - Individual Team Stats');

// maximum team ID's to load for ipaddr, name, and worldid
$MAX_TEAM_IDS = 10;

$validfields = array(
	'id',
	'asort','aorder','astart','alimit',	// advanced
	'dsort','dorder','dstart','dlimit',	// defence
	'osort','oorder','ostart','olimit',	// offence
	'xml'
);
$cms->theme->assign_request_vars($validfields, true);

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
}

// SET DEFAULTS. Since they're basically the same for each list, we do this in a loop
foreach ($validfields as $var) {
	switch (substr($var, 1)) {
		case 'sort':
			if (!$$var) $$var = 'season';
			break;
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

$totalranked  = $ps->get_total_teams(array('allowall' => 0));

$team = $ps->get_team(array(
	'season'		=> null,
	'team_id' 	=> $id,
	'loadnames'	=> 1, 
	//'loadawards'	=> 1,
	'advancedsort'	=> $asort,
	'advancedorder'	=> $aorder,
	'advancedstart'	=> $astart,
	'advancedlimit'	=> $alimit,
	'defencesort'	=> $dsort,
	'defenceorder'	=> $dorder,
	'defencestart'	=> $dstart,
	'defencelimit'	=> $dlimit,
	'offencesort'	=> $osort,
	'offenceorder'	=> $oorder,
	'offencestart'	=> $ostart,
	'offencelimit'	=> $olimit,
	'idstart'		=> 0,
	'idlimit'		=> $MAX_TEAM_IDS,
	'idsort'		=> 'lastseen',
	'idorder'		=> 'desc',
));

$cms->theme->page_title(' for Team #' . $team['team_id'], true);

/*$x = substr($xml,0,1);
if (($x == 'd') or ($x == 'o')) {	// team
	// we have to alter some of the data for team arrays otherwise we'll end up with invalid or strange keys
	$ary = $team;
	$ary['defence'] = array();
	$ary['offence'] = array();
	foreach ($team['defence'] as $d) {
		$ary['defence'][ $d['team_id'] ] = $d;
	} 
	foreach ($team['offence'] as $o) {
		$ary['offence'][ $o['team_id'] ] = $o;
	} 
	print_xml($ary);
}*/

$team['totaladvanced'] ??= null;
$advancedpager = pagination(array(
    'baseurl'       => psss_url_wrapper(array( 'id' => $id, 'alimit' => $alimit, 'asort' => $asort, 'aorder' => $aorder)),
    'total'         => $team['totaladvanced'],
    'start'         => $astart,
    'startvar'      => 'astart',
    'perpage'       => $dlimit,
    'urltail'       => 'advanced',
    'separator'	=> ' ',
    'next'          => $cms->trans("Next"),
    'prev'          => $cms->trans("Previous"),
    'pergroup'	=> 5,
));

$team['totaldefence'] ??= null;
$defencepager = pagination(array(
    'baseurl'       => psss_url_wrapper(array( 'id' => $id, 'dlimit' => $dlimit, 'dsort' => $dsort, 'dorder' => $dorder)),
    'total'         => $team['totaldefence'],
    'start'         => $dstart,
    'startvar'      => 'dstart',
    'perpage'       => $dlimit,
    'urltail'       => 'defence',
    'separator'	=> ' ',
    'next'          => $cms->trans("Next"),
    'prev'          => $cms->trans("Previous"),
    'pergroup'	=> 5,
));

$team['totaloffence'] ??= null;
$offencepager = pagination(array(
	'baseurl'       => psss_url_wrapper(array( 'id' => $id, 'olimit' => $olimit, 'osort' => $osort, 'oorder' => $oorder)),
	'total'         => $team['totaloffence'],
	'start'         => $ostart,
	'startvar'      => 'ostart',
	'perpage'       => $olimit,
	'urltail'       => 'offence',
	'separator'	=> ' ',
	'next'          => $cms->trans("Next"),
	'prev'          => $cms->trans("Previous"),
	'pergroup'	=> 5,
));


// build team advanced table
$atable = $cms->new_table($team['advanced']);
$atable->if_no_data($cms->trans("No Advanced Stats Found"));
$atable->attr('class', 'ps-table ps-advanced-table');
$atable->sort_baseurl(array( 'id' => $id, '_anchor' => 'advanced' ));
$atable->start_and_sort($astart, $asort, $aorder, 'a');
$atable->columns(array(
	'season'			=> array( 'label' => $cms->trans("Season") ),
	'wins'			=> array( 'label' => $cms->trans("Wins") ),
	'losses'		=> array( 'label' => $cms->trans("Losses") ),
	'win_percent'			=> array( 'label' => $cms->trans("Win %") ),
	'games_back'			=> array( 'label' => $cms->trans("GB"), 'nosort' => true, 'tooltip' => $cms->trans("Playoff status and how many games behind division leader\n—\"dtlc\" indicates division title and league champion\n—\"lc\" indicates league champion\n—\"dt\" indicates division title") ),
	'team_rdiff'			=> array( 'label' => $cms->trans("Run Differential"), 'tooltip' => $cms->trans("(Total Runs Scored - Total Runs Against) / 9 Innings") ),
	'pythag'			=> array( 'label' => $cms->trans("Pythag"), 'tooltip' => $cms->trans("Pythagorean Expectation") )
));
$atable->column_attr('season', 'class', 'first');
$atable->column_attr('pythag', 'class', 'right');
$ps->team_advanced_table_mod($atable);
$cms->filter('team_advanced_table_object', $atable);


// build team defence table
$dtable = $cms->new_table($team['defence']);
$dtable->if_no_data($cms->trans("No Defensive Stats Found"));
$dtable->attr('class', 'ps-table ps-defence-table');
$dtable->sort_baseurl(array( 'id' => $id, '_anchor' => 'defence' ));
$dtable->start_and_sort($dstart, $dsort, $dorder, 'd');
$dtable->columns(array(
	'season'			=> array( 'label' => $cms->trans("Season") ),
	'team_era'			=> array( 'label' => $cms->trans("ERA"), 'tooltip' => $cms->trans("Team Earned Runs Against Average per 9 Innings") ),
	'team_ra'		=> array( 'label' => $cms->trans("RA"), 'tooltip' => $cms->trans("Team Runs Against Average per 9 Innings") ),
//	'complete_games'			=> array( 'label' => $cms->trans("Complete Games") ),
	'shutouts'			=> array( 'label' => $cms->trans("Shutouts") ),
	'team_saves'			=> array( 'label' => $cms->trans("Saves") ),
	'innings_pitched'			=> array( 'label' => $cms->trans("IP"), 'tooltip' => $cms->trans("Team Total Innings Pitched") ),
	'total_runs_against'			=> array( 'label' => $cms->trans("TRA"), 'tooltip' => $cms->trans("Team Total Runs Scored Against") ),
	'total_earned_runs_against'			=> array( 'label' => $cms->trans("TERA"), 'tooltip' => $cms->trans("Team Total Earned Runs Scored Against") ),
	'hits_surrendered'			=> array( 'label' => $cms->trans("Hits"), 'tooltip' => $cms->trans("Team Total Hits Against") ),
	'opp_batting_average'			=> array( 'label' => $cms->trans("BAA"), 'tooltip' => $cms->trans("Team Batting Average Against") ),
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
	'team_drat'			=> array( 'label' => $cms->trans("DRAT"), 'tooltip' => $cms->trans("Team Defensive Rating:\n—all defensive stats combined into a single number, not including wild pitches\n—roughly equivalent to defensive runs saved per 9 innings") ),
));
$dtable->column_attr('season', 'class', 'first');
$dtable->column_attr('team_drat', 'class', 'right');
$ps->team_defence_table_mod($dtable);
$cms->filter('team_defence_table_object', $dtable);


// build team offence table
$otable = $cms->new_table($team['offence']);
$otable->if_no_data($cms->trans("No Offensive Stats Found"));
$otable->attr('class', 'ps-table ps-offence-table');
$otable->sort_baseurl(array( 'id' => $id, '_anchor' => 'offence' ));
$otable->start_and_sort($ostart, $osort, $oorder, 'o');
$otable->columns(array(
	'season'			=> array( 'label' => $cms->trans("Season") ),
	'run_support'			=> array( 'label' => $cms->trans("Run Support"), 'tooltip' => $cms->trans("Team Total Runs Scored per Game") ),
	'at_bats'			=> array( 'label' => $cms->trans("AB"), 'tooltip' => $cms->trans("Team Total At Bats") ),
	'runs'			=> array( 'label' => $cms->trans("R"), 'tooltip' => $cms->trans("Team Total Runs Scored") ),
	'hits'			=> array( 'label' => $cms->trans("H"), 'tooltip' => $cms->trans("Team Total Hits") ),
	'doubles'			=> array( 'label' => $cms->trans("Doubles"), 'tooltip' => $cms->trans("Team Total Doubles") ),
	'triples'			=> array( 'label' => $cms->trans("Triples"), 'tooltip' => $cms->trans("Team Total Triples") ),
	'home_runs'			=> array( 'label' => $cms->trans("HR"), 'tooltip' => $cms->trans("Team Total Home Runs") ),
	'team_rbis'			=> array( 'label' => $cms->trans("RBI"), 'tooltip' => $cms->trans("Team Total RBI's") ),
	'walks'			=> array( 'label' => $cms->trans("BB"), 'tooltip' => $cms->trans("Team Total Base on Balls") ),
	'strikeouts'			=> array( 'label' => $cms->trans("K"), 'tooltip' => $cms->trans("Team Total Strikeouts") ),
	'batting_average'			=> array( 'label' => $cms->trans("BA"), 'tooltip' => $cms->trans("Team Combined Batting Average") ),
	'on_base_average'			=> array( 'label' => $cms->trans("OBA"), 'tooltip' => $cms->trans("Team Combined On Base Average") ),
	'slugging_average'			=> array( 'label' => $cms->trans("SLG"), 'tooltip' => $cms->trans("Team Combined Slugging Average") ),
	'ops'		=> array( 'label' => $cms->trans("OPS"), 'tooltip' => $cms->trans("Team Combined On Base Plus Slugging Average") ),
	'woba'			=> array( 'label' => $cms->trans("wOBA"), 'tooltip' => $cms->trans("Team Weighted On Base Average:\n—does not include HBP and IBB data") ),
	'sacrifice_hits'		=> array( 'label' => $cms->trans("SH"), 'tooltip' => $cms->trans("Team Total Sacrifice Hits") ),
	'sacrifice_fails'		=> array( 'label' => $cms->trans("F"), 'tooltip' => $cms->trans("Team Total Failed Sacrifice Attempts") ),
	'sacrifice_flies'		=> array( 'label' => $cms->trans("SF"), 'tooltip' => $cms->trans("Team Total Sacrifice Flies") ),
	'gidps'		=> array( 'label' => $cms->trans("GIDP"), 'tooltip' => $cms->trans("Team Total Grounded into Double Play") ),
	'stolen_bases'		=> array( 'label' => $cms->trans("SB"), 'tooltip' => $cms->trans("Team Total Stolen Bases") ),
	'caught_stealing'		=> array( 'label' => $cms->trans("CS"), 'tooltip' => $cms->trans("Team Total Caught Stealing") ),
	'left_on_base'			=> array( 'label' => $cms->trans("LOB"), 'tooltip' => $cms->trans("Team Total Runners Left on Base") ),
	'left_on_base_percent'			=> array( 'label' => $cms->trans("LOB %"), 'tooltip' => $cms->trans("(Team Total Base Runners - Team HR)/(Team RBI - Team HR)") ),
	'team_srat'			=> array( 'label' => $cms->trans("SRAT"), 'tooltip' => $cms->trans("Team Speed Rating:\n—all offensive stats affected by baserunning combined into a single number\n—roughly equivalent to runs scored per 9 innings affected by team speed") ),
));
$otable->column_attr('season', 'class', 'first');
$otable->column_attr('left_on_base_percent', 'class', 'right');
$ps->team_offence_table_mod($otable);
$cms->filter('team_offence_table_object', $otable);

// Are there divisions or wilcards in this league?
$division = $ps->get_total_divisions() - 1;
$wildcard = $ps->get_total_wc();

// Declare shades array.
$shades = array(
	's_team_rundown'	=> null,
	's_modactions'		=> null,
	's_team_hist'		=> null,
	's_teamname'		=> null,
	's_teamadvanced'	=> null,
	's_teamdefence'		=> null,
	's_teamoffence'		=> null,
);

$cms->theme->assign_by_ref('team', $team);
$cms->theme->assign(array(
	'advanced_table'	=> $atable->render(),
	'defence_table'		=> $dtable->render(),
	'offence_table'		=> $otable->render(),
	'advancedpager'		=> $advancedpager,
	'defencepager'		=> $defencepager,
	'offencepager'		=> $offencepager,
	'totalranked'		=> $totalranked,
	'lastupdate'		=> $ps->get_lastupdate(),
	'season_c'			=> null,
	'division'			=> $division,
	'wildcard'			=> $wildcard,
	'shades'			=> $shades,
	'form_key'			=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'		=> $cookieconsent,
));

$basename = basename(__FILE__, '.php');
if ($team['team_id']) {
	// allow mods to have their own section on the left side bar
	$ps->team_left_column_mod($team, $cms->theme);

	$cms->theme->add_css('css/2column.css');	// this page has a left column
	$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
} else {
	$cms->full_page_err($basename, array(
		'message_title'	=> $cms->trans("No Team Found!"),
		'message'	=> $cms->trans("Invalid team ID specified.") . " " . $cms->trans("Please go back and try again.")
	));
}

function dash_if_empty($val) {
	return !empty($val) ? $val : '-';
}

?>
