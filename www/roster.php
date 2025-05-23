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
 *	Version: $Id: roster.php $
 */
define("PSYCHOSTATS_PAGE", true);
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Team Roster—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance and !$cms->user->is_admin()) previouspage('index.php');

// change this if you want the default sort of the player listing to be something else like 'wins'
$DEFAULT_PITCHER_SORT = 'pi_innings_pitched';
$DEFAULT_POSITION_SORT = 'po_at_bats';

$validfields = array(
	'id','season',
	'dsort','dorder','dstart','dlimit',	// pitcher
	'osort','oorder','ostart','olimit',	// position
);
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

// SET DEFAULTS—sanitized
$dsort = ($dsort and strlen($dsort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $dsort) : $DEFAULT_PITCHER_SORT;
$osort = ($osort and strlen($osort) <= 64) ? preg_replace('/[^A-Za-z0-9_\-\.]/', '', $osort) : $DEFAULT_POSITION_SORT;

// Since they're basically the same for each list, we do this in a loop
foreach ($validfields as $var) {
	switch (substr($var, 1)) {
		case 'order':
			if (!$$var or !in_array($$var, array('asc', 'desc'))) $$var = 'desc';
			break;
		case 'start':
			if (!is_numeric($$var) || $$var < 0) $$var = 0;
			break;
		case 'limit':
			if (!is_numeric($$var) || $$var < 0 || $$var > 100) $$var = 40;
			break;
		default:
		        break;
	}
}

// sanitize sorts
$dsort = ($ps->db->column_exists(array($ps->t_team_rpi), $dsort)) ? $dsort : $DEFAULT_PITCHER_SORT;
$osort = ($ps->db->column_exists(array($ps->t_team_rpo), $osort)) ? $osort : $DEFAULT_POSITION_SORT;

$roster = $ps->get_team_roster(array(
	'season'		=> $season,
	'team_id' 		=> $id,
	'pitchersort'	=> $dsort,
	'pitcherorder'	=> $dorder,
	'pitcherstart'	=> $dstart,
	'pitcherlimit'	=> $dlimit,
	'positionsort'	=> $osort,
	'positionorder'	=> $oorder,
	'positionstart'	=> $ostart,
	'positionlimit'	=> $olimit,
));

$team = $ps->get_team_profile($id);

$cms->theme->page_title(' for Team #' . $id, true);

$roster['totalpitcher'] ??= 0;
$pitcherpager = pagination(array(
    'baseurl'       => psss_url_wrapper(array( 'id' => $id, 'dlimit' => $dlimit, 'dsort' => $dsort, 'dorder' => $dorder)),
    'total'         => $roster['totalpitcher'],
    'start'         => $dstart,
    'startvar'      => 'dstart',
    'perpage'       => $dlimit,
    'urltail'       => 'pitcher',
    'separator'	=> ' ',
    'next'          => $cms->trans("Next"),
    'prev'          => $cms->trans("Previous"),
    'pergroup'	=> 5,
));

$roster['totalposition'] ??= 0;
$positionpager = pagination(array(
	'baseurl'       => psss_url_wrapper(array( 'id' => $id, 'olimit' => $olimit, 'osort' => $osort, 'oorder' => $oorder)),
	'total'         => $roster['totalposition'],
	'start'         => $ostart,
	'startvar'      => 'ostart',
	'perpage'       => $olimit,
	'urltail'       => 'position',
	'separator'	=> ' ',
	'next'          => $cms->trans("Next"),
	'prev'          => $cms->trans("Previous"),
	'pergroup'	=> 5,
));
// build team pitchers table
$dtable = $cms->new_table($roster['pitcher']);
$dtable->if_no_data($cms->trans("No Pitcher Stats Found"));
$dtable->attr('class', 'ps-table ps-pitcher-table');
$dtable->sort_baseurl(array( 'id' => $id, '_anchor' => 'pitcher' ));
$dtable->start_and_sort($dstart, $dsort, $dorder, 'd');
$dtable->columns(array(
	'player_name'				=> array( 'label' => $cms->trans("Player Name"), 'callback' => 'psss_table_br_search_link', 'tooltip' => $cms->trans("Click name to search Baseball Reference") ),
	'pi_wins'					=> array( 'label' => $cms->trans("W"), 'tooltip' => $cms->trans("Wins") ),
	'pi_losses'					=> array( 'label' => $cms->trans("L"), 'tooltip' => $cms->trans("Losses") ),
	'pi_win_percent'			=> array( 'label' => $cms->trans("W%"), 'tooltip' => $cms->trans("Win %"), 'callback' => 'negpos500' ),
	'pi_era'					=> array( 'label' => $cms->trans("ERA"), 'tooltip' => $cms->trans("Earned Runs Against Average per 9 Innings") ),
	'pi_games_played'			=> array( 'label' => $cms->trans("G"), 'tooltip' => $cms->trans("Games Played") ),
	'pi_games_started'			=> array( 'label' => $cms->trans("GS"), 'tooltip' => $cms->trans("Games Started") ),
	'pi_complete_games'			=> array( 'label' => $cms->trans("CG"), 'tooltip' => $cms->trans("Complete Games") ),
	'pi_shutouts'				=> array( 'label' => $cms->trans("ShO"), 'tooltip' => $cms->trans("Shutouts") ),
	'pi_run_support'			=> array( 'label' => $cms->trans("RS"), 'tooltip' => $cms->trans("Run Support"), 'callback' => 'dash_if_empty' ),
	'pi_saves'					=> array( 'label' => $cms->trans("Sv") ),
	'pi_innings_pitched'		=> array( 'label' => $cms->trans("IP"), 'tooltip' => $cms->trans("Innings Pitched") ),
	'pi_runs_against'			=> array( 'label' => $cms->trans("R"), 'tooltip' => $cms->trans("Runs Scored Against") ),
	'pi_earned_runs_against'	=> array( 'label' => $cms->trans("ER"), 'tooltip' => $cms->trans("Earned Runs Scored Against") ),
	'pi_hits_surrendered'		=> array( 'label' => $cms->trans("H"), 'tooltip' => $cms->trans("Hits Surrendered") ),
	'pi_opp_batting_average'	=> array( 'label' => $cms->trans("BA"), 'tooltip' => $cms->trans("Batting Average Against"), 'callback' => 'remove_zero_point' ),
	'pi_opp_walks'				=> array( 'label' => $cms->trans("BB"), 'tooltip' => $cms->trans("Walks Surrendered") ),
	'pi_whip'					=> array( 'label' => $cms->trans("WHIP"), 'tooltip' => $cms->trans("(Hits + Walks)/Inning Pitched") ),
	'pi_strikeouts'				=> array( 'label' => $cms->trans("K"), 'tooltip' => $cms->trans("Strikeouts") ),
	'pi_wild_pitches'			=> array( 'label' => $cms->trans("WP"), 'tooltip' => $cms->trans("Wild Pitches") ),
	'pi_v'			=> array( 'label' => $cms->trans("V"), 'tooltip' => $cms->trans("Player Value:\n—based on WHIP and IP\n—percentile value rating that allows for comparison between players"), 'callback' => 'remove_zero_point_die' )
));
$dtable->column_attr('player_name', 'class', 'left');
$dtable->column_attr('pi_v', 'class', 'right');
$dtable->column_attr('pi_era', 'class', 'primary');
$ps->team_pitcher_table_mod($dtable);
$cms->filter('roster_pitcher_table_object', $dtable);


// build team position players table
$otable = $cms->new_table($roster['position']);
$otable->if_no_data($cms->trans("No Position Player Stats Found"));
$otable->attr('class', 'ps-table ps-position-table');
$otable->sort_baseurl(array( 'id' => $id, '_anchor' => 'position' ));
$otable->start_and_sort($ostart, $osort, $oorder, 'o');
$otable->columns(array(
	'player_name'				=> array( 'label' => $cms->trans("Player Name"), 'callback' => 'psss_table_br_search_link', 'tooltip' => $cms->trans("Click name to search Baseball Reference") ),
	'po_games_played'			=> array( 'label' => $cms->trans("G"), 'tooltip' => $cms->trans("Games Played") ),
	'po_at_bats'				=> array( 'label' => $cms->trans("AB"), 'tooltip' => $cms->trans("At Bats") ),
	'po_runs'					=> array( 'label' => $cms->trans("R"), 'tooltip' => $cms->trans("Runs Scored") ),
	'po_hits'					=> array( 'label' => $cms->trans("H"), 'tooltip' => $cms->trans("Hits") ),
	'po_doubles'				=> array( 'label' => $cms->trans("D"), 'tooltip' => $cms->trans("Doubles") ),
	'po_triples'				=> array( 'label' => $cms->trans("T"), 'tooltip' => $cms->trans("Triples") ),
	'po_home_runs'				=> array( 'label' => $cms->trans("HR"), 'tooltip' => $cms->trans("Home Runs") ),
	'po_rbis'					=> array( 'label' => $cms->trans("RBI"), 'tooltip' => $cms->trans("RBI&#39;s") ),
	'po_walks'					=> array( 'label' => $cms->trans("BB"), 'tooltip' => $cms->trans("Base on Balls") ),
	'po_strikeouts'				=> array( 'label' => $cms->trans("K"), 'tooltip' => $cms->trans("Strikeouts") ),
	'po_batting_average'		=> array( 'label' => $cms->trans("BA"), 'tooltip' => $cms->trans("Batting Average"), 'callback' => 'remove_zero_point' ),
	'po_on_base_average'		=> array( 'label' => $cms->trans("OBA"), 'tooltip' => $cms->trans("On Base Average"), 'callback' => 'remove_zero_point' ),
	'po_slugging_average'		=> array( 'label' => $cms->trans("SlgA"), 'tooltip' => $cms->trans("Slugging Average"), 'callback' => 'remove_zero_point' ),
	'po_ops'					=> array( 'label' => $cms->trans("OPS"), 'tooltip' => $cms->trans("On Base Plus Slugging Average"), 'callback' => 'remove_zero_point' ),
	'po_woba'					=> array( 'label' => $cms->trans("wOBA"), 'tooltip' => $cms->trans("Weighted On Base Average:\n—does not include HBP and IBB data"), 'callback' => 'remove_zero_point' ),
	'po_sacrifice_hits'			=> array( 'label' => $cms->trans("SH"), 'tooltip' => $cms->trans("Sacrifice Hits") ),
	'po_sacrifice_fails'		=> array( 'label' => $cms->trans("F"), 'tooltip' => $cms->trans("Failed Sacrifice Attempts") ),
	'po_sacrifice_flies'		=> array( 'label' => $cms->trans("SF"), 'tooltip' => $cms->trans("Sacrifice Flies") ),
	'po_gidps'					=> array( 'label' => $cms->trans("GDP"), 'tooltip' => $cms->trans("Grounded into Double Play") ),
	'po_stolen_bases'			=> array( 'label' => $cms->trans("SB"), 'tooltip' => $cms->trans("Stolen Bases") ),
	'po_caught_stealing'		=> array( 'label' => $cms->trans("CS"), 'tooltip' => $cms->trans("Caught Stealing") ),
	'po_outstanding_plays'		=> array( 'label' => $cms->trans("OP"), 'tooltip' => $cms->trans("Outstanding Plays") ),
	'po_fielding_errors'		=> array( 'label' => $cms->trans("E"), 'tooltip' => $cms->trans("Fielding Errors") ),
	'po_passed_balls'			=> array( 'label' => $cms->trans("PB"), 'tooltip' => $cms->trans("Passed Balls"), 'callback' => 'dash_if_empty' ),
	'po_v'			=> array( 'label' => $cms->trans("V"), 'tooltip' => $cms->trans("Player Value:\n—based on OPS and AB\n—percentile value rating that allows for comparison between players"), 'callback' => 'remove_zero_point_die' )
));
$otable->column_attr('player_name', 'class', 'left');
$otable->column_attr('po_v', 'class', 'right');
$otable->column_attr('po_ops', 'class', 'primary');
$ps->team_position_table_mod($otable);
$cms->filter('roster_position_table_object', $otable);

// Declare shades array.
$shades = array(
	's_teampitcher'		=> null,
	's_teamposition'	=> null,
);

$cms->theme->assign_by_ref('roster', $roster);
$cms->theme->assign(array(
	'pitcher_table'		=> $dtable->render(),
	'position_table'	=> $otable->render(),
	'pitcherpager'		=> $pitcherpager,
	'positionpager'		=> $positionpager,
	'team'				=> $team,
	'season_c'			=> $season_c,
	'season'			=> $season,
	'seasons_h'			=> $ps->get_seasons_h(),
	'division'			=> $division,
	'wildcard'			=> $wildcard,
	'shades'			=> $shades,
	'form_key'			=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'		=> $cookieconsent,
));

if (isset($team['team_id'])) {
	$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
} else {
	$cms->full_page_err($basename, array(
		'message_title'	=> $cms->trans("No Team Found!"),
		'message'	=> $cms->trans("This page cannot be accessed directly.") . " " . $cms->trans("Please go to a team page and access team rosters there.")
	));
}

function dash_if_empty($val) {
	return !empty(intval($val * 1000)) ? $val : '-';
}

function negpos500($val, $med = 0.5, $remz = true) {
	return neg_pos_500($val, $med, $remz);
}

function remove_zero_point($val) {
	return preg_replace('/^(-|)0\./', '$1.', $val);
}

function remove_zero_point_die($val) {
	$val = preg_replace('/^0\./', '.', $val);
	return !empty(intval($val * 1000)) ? $val : '-';
}

?>
