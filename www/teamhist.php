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
 *	Version: $Id: teamhist.php 493 2021-07-30 $
 */

define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('PsychoStats - Team History');

$validfields = array(
	'id',
	'start','sort','order',
	'sstart','ssort','sorder','slimit',
);
$cms->theme->assign_request_vars($validfields, true);

if (!$start or $start < 0) $start = 0;
if (!$limit or $limit < 0 or $limit > 100) $limit = 31;
if (!$order or !in_array($order, array('asc', 'desc'))) $order = 'desc';
if (!$sort) $sort = 'statdate';

if (!$sstart or $sstart < 0) $sstart = 0;
if (!$slimit or $slimit < 0 or $slimit > 100) $slimit = 31;
if (!$sorder or !in_array($sorder, array('asc', 'desc'))) $sorder = 'desc';
if (!$ssort) $ssort = 'sessionstart';

$totalranked  = $ps->get_total_teams(array('allowall' => 0));

$team = $ps->get_team(array(
	'team_id' 	=> $id,
	'loadcounts'	=> 1,
	'loadids'	=> 1,
));

$cms->theme->page_title(' for ' . $team['name'], true);

$history = $ps->get_team_days(array(
	'team_id'		=> $id,
	'sort'		=> $sort,
	'order'		=> $order,
	'start'		=> $start,
	'limit'		=> $limit
));

$days = array();
foreach ($history as $s) {
	$days[] = $s['statdate'];
}
sort($days, SORT_STRING);

$htable = $cms->new_table($history);
$htable->if_no_data($cms->trans("No Historical Stats Found"));
$htable->attr('class', 'ps-table ps-teamhistory-table');
$htable->sort_baseurl(array( 'id' => $id ));
$htable->start_and_sort($start, $sort, $order);
$htable->columns(array(
	'statdate'		=> array( 'label' => $cms->trans("Date") ),
	'win_percent'			=> array( 'label' => $cms->trans("K"), 'modifier' => 'commify', 'tooltip' => $cms->trans("Wins") ),
	'team_rdiff'		=> $team_rdiff ),
));
$cms->filter('team_history_table_object', $htable);

$sessionpager = pagination(array(
	'baseurl'       => psss_url_wrapper(array( 'id' => $id, 'slimit' => $slimit, 'ssort' => $ssort, 'sorder' => $sorder)),
	'total'         => $team['totalsessions'],
	'start'         => $sstart,
	'startvar'      => 'sstart',
	'perpage'       => $slimit,
	'urltail'       => 'sessions',
	'separator'	=> ' ',
	'next'          => $cms->trans("Next"),
	'prev'          => $cms->trans("Previous"),
));

$cms->theme->assign_by_ref('team', $team);
$cms->theme->assign(array(
	'history'		=> $history,
	'history_table'		=> $htable->render(),
	'sessions_table'	=> $stable->render(),
	'days'			=> $days,
	'totalranked'		=> $totalranked,
	'top10percentile'	=> $team['rank'] ? $team['rank'] < $totalranked * 0.10 : false,
	'top1percentile'	=> $team['rank'] ? $team['rank'] < $totalranked * 0.01 : false,
	'sessionpager'		=> $sessionpager,
	'lastupdate'		=> $ps->get_lastupdate(),
	'season_c'		=> null,
));

$basename = basename(__FILE__, '.php');
if ($team['team_id']) {
	// allow mods to have their own section on the left side bar
	$ps->team_left_column_mod($team, $cms->theme);

	if ($ps->conf['main']['team_id'] == 'team_id') {
		$team_id = $team['ids_team_id'][0]['team_id'];
		if ($team_id and strtoupper(substr($team_id, 0, 3)) !== 'BOT' and function_exists('gmp_init')) {
			include_once(PS_ROOTDIR . "/includes/class_Team_ID.php");
			$v = new Team_ID($team_id);
			$friendid = $v->ConvertToUInt64($team_id);
			$team['friend_id'] = $friendid;
			$team['steam_community_url'] = $v->steam_community_url($friendid);
			$team['steam_add_friend_url'] = $v->steam_add_friend_url($friendid);
		}
	}

	$cms->theme->add_css('css/2column.css');	// this page has a left column
	$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
} else {
	$cms->full_page_err($basename, array(
		'message_title'	=> $cms->trans("No Team Found!"),
		'message'	=> $cms->trans("Invalid team ID specified.") . " " . $cms->trans("Please go back and try again.")
	));
}

?>
