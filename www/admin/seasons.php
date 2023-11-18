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
 *	Version: $Id: seasons.php $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");

$cms->crumb('Manage', psss_url_wrapper($_SERVER['REQUEST_URI']));
$cms->crumb('Seasons', psss_url_wrapper($php_scnm));

$validfields = array('ref','start','limit','order','sort','ajax','season_h');
$cms->theme->assign_request_vars($validfields, true);

if (!is_numeric($start) or $start < 0) $start = 0;
if (!is_numeric($limit) or $limit < 0) $limit = 50;
if (!in_array($order, array('asc','desc'))) $order = 'desc';
$sort = 'season_h';

$_order = array(
	'start'	=> $start,
	'limit'	=> $limit,
	'order' => $order, 
	'sort'	=> $sort
);

// get a list of seasons
$seasons = array();
$cmd = "SELECT * FROM $ps->t_seasons_h WHERE ";
$where = "1";
$cmd .= $where . " " . $ps->getsortorder($_order);
$list = $ps->db->fetch_rows(1, $cmd);

$total = $ps->db->count($ps->t_seasons_h, '*', $where);
$pager = pagination(array(
	'baseurl'	=> psss_url_wrapper($_order),
	'total'		=> $total,
	'start'		=> $start,
	'perpage'	=> $limit, 
	'pergroup'	=> 5,
	'separator'	=> ' ', 
	'force_prev_next' => true,
	'next'		=> $cms->trans("Next"),
	'prev'		=> $cms->trans("Previous"),
));

// massage the array a bit so we don't have to do the logic in the theme template
$seasons = array();
$first = $list ? $list[0]['season_h'] : array();
$last  = $list ? $list[ count($list) - 1]['season_h'] : array();
foreach ($list as $sh) {
	$sh['season_h'] ??= null;
	$sh['up'] ??= null;
	$sh['down'] ??= null;
	if ($sh['season_h'] == $first) {
		$sh['down'] = 1;
	} elseif ($sh['season_h'] == $last) {
		$sh['up'] = 1;
	} else {
		$sh['down'] = 1;
		$sh['up'] = 1;
	}
	$seasons[] = $sh;
}

// assign variables to the theme
$cms->theme->assign(array(
	'page'		=> basename(__FILE__, '.php'), 
	'pager'		=> $pager,
	'seasons'	=> $seasons,
	'text'		=> $cms->trans("Loading ..."),
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/2column.css');
$cms->theme->add_css('css/forms.css');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
