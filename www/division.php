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
$cms->theme->page_title('PsychoStats for Scoresheet - Division Stats');

$validfields = array(
	'id',
	'season',
	'tsort','torder','tstart','tlimit',	// teams
    'xml'
);
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
}

if (!$tsort) $tsort = 'win_percent';

// SET DEFAULTS. Since they're basically the same for each list, we do this in a loop
foreach ($validfields as $var) {
	switch (substr($var, 1)) {
		case 'sort':
			if (!$$var) $$var = 'win_percent';
			break;
		case 'order':
			if (!$$var or !in_array($$var, array('asc', 'desc'))) $$var = 'desc';
			break;
		case 'start':
			if (!is_numeric($$var) || $$var < 0) $$var = 0;
			break;
		case 'limit':
			if (!is_numeric($$var) || $$var < 0 || $$var > 100) $$var = 20;
			break;
		default:
		        break;
	}
}

$division = $ps->get_division(array(
	'season'	=> $season,
	'divisionname' 	=> $id,
	'membersort'	=> $tsort,
	'memberorder'	=> $torder,
	'memberstart'	=> $tstart,
	'memberlimit'	=> $tlimit,
	'memberfields'	=> '',
));

$cms->theme->page_title(' for ' . $division['divisionname'], true);

$x = substr($xml,0,1);
if ($x == 'd') {		// division
}

//$data['mapblockfile'] = $smarty->get_block_file('block_maps');
//$data['teamblockfile'] = $smarty->get_block_file('block_team');


$ttable = $cms->new_table($division['members']);
$ttable->attr('class', 'ps-table ps-team-table');
$ttable->sort_baseurl(array( 'id' => $id, '_anchor' => 'members' ));
$ttable->start_and_sort($tstart, $tsort, $torder, 't');
$ttable->columns(array(
	'rank'			=> array( 'label' => $cms->trans("Rank"), 'callback' => 'dash_if_empty' ),
	'prevrank'		=> array( 'nolabel' => true, 'callback' => 'rankchange' ),
	'team_n'			=> array( 'label' => $cms->trans("Team #"), 'callback' => 'psss_table_team_link' ),
	'team_name'			=> array( 'label' => $cms->trans("Team"), 'callback' => 'psss_table_team_link' ),
	'wins'			=> array( 'label' => $cms->trans("Wins") ),
	'losses'			=> array( 'label' => $cms->trans("Losses") ),
	'win_percent'			=> array( 'label' => $cms->trans("Win %") ),
	'games_back'			=> array( 'label' => $cms->trans("GB"), 'nosort' => true, 'tooltip' => $cms->trans("Playoff status and how many games behind division leader\n—\"dtlc\" indicates division title and league champion\n—\"lc\" indicates league champion\n—\"dt\" indicates division title") ),
	'team_rdiff'			=> array( 'label' => $cms->trans("Run Differential"), 'tooltip' => $cms->trans("(Total Runs Scored - Total Runs Against) / 9 Innings") ),
	'pythag'			=> array( 'label' => $cms->trans("Pythag"), 'tooltip' => $cms->trans("Pythagorean Expectation") )
));
$ttable->column_attr('team_name', 'class', 'left');
$ttable->column_attr('team_rdiff', 'class', 'right');
$ps->division_teams_table_mod($ttable);
$ttable->header_attr('rank', 'colspan', '2');
$cms->filter('division_members_table_object', $ttable);

// If season is not current season, remove rank columns.
if ($season != $season_c) {
	$ttable->remove_columns(array('rank'));
	$ttable->remove_columns(array('prevrank'));
}

# Are wildcard statndings available?
$wildcard = $ps->get_total_wc();

// Declare shades array.
$shades = array(
	's_division_rundown'	=> null,
	's_modactions'			=> null,
	's_divisionmembers'		=> null,
);

$cms->theme->assign(array(
	'division'			=> $division,
	'members_table'		=> $ttable->render(),
	'lastupdate'		=> $ps->get_lastupdate(),
	'seasons_h'		=> $ps->get_seasons_h(),
	'season'		=> $season,
	'season_c'		=> $season_c,
	'wildcard'		=> $wildcard,
	'shades'		=> $shades,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// allow mods to have their own section on the left side bar
$ps->division_left_column_mod($division, $cms->theme);

$basename = basename(__FILE__, '.php');
if ($division['divisionname']) {
	$cms->theme->add_css('css/2column.css');	// this page has a left column
	$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
} else {
	$cms->full_page_err($basename, array(
		'message_title'	=> $cms->trans("No Division Found!"),
		'message'	=> $cms->trans("Invalid division ID specified.") . " " . $cms->trans("Please go back and try again.")
	));
}

function rankchange($val, $team) {
	return rank_change($team);
}

/*function dmg($dmg) {
	return "<abbr title='" . commify($dmg) . "'>" . abbrnum0($dmg) . "</abbr>";
}*/

?>
