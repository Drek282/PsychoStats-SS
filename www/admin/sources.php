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
 *	Version: $Id: sources.php 389 2008-04-18 15:04:10Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
$basename = basename(__FILE__, '.php');
include("../includes/common.php");
include("./common.php");

$validfields = array('ref','start','limit','order','sort','move','toggle','id','ajax');
$cms->theme->assign_request_vars($validfields, true);

if (!is_numeric($start) or $start < 0) $start = 0;
if (!is_numeric($limit) or $limit < 0) $limit = 25;
if (!in_array($order, array('asc','desc'))) $order = 'asc';
$sort = 'idx';

$_order = array(
	'start'	=> $start,
	'limit'	=> $limit,
	'order' => $order, 
	'sort'	=> $sort
);

// toggle the enabled flag on a source
if ($toggle and $id) {
	$ok = $ps->db->query("UPDATE $ps->t_config_sources SET enabled=IF(enabled=1, 0, 1) WHERE id=" . $ps->db->escape($id, true));
	list($enabled) = $ps->db->fetch_list("SELECT enabled FROM $ps->t_config_sources WHERE id=" . $ps->db->escape($id, true));
	unset($submit);
	if ($ajax) {
		print xml_result(1, "success", true, array( 'enabled' => $enabled ));
		exit;
	}
}

// re-order league page sources
if ($move and $id) {
	$list = $ps->db->fetch_rows(1, "SELECT id,idx FROM $ps->t_config_sources ORDER BY idx");
	$inc = $move == 'up' ? -15 : 15;
	$idx = 0;
	// loop through all league page sources and set their idx linearly
	for ($i=0; $i < count($list); $i++) {
		$list[$i]['idx'] = ++$idx * 10;
		if ($list[$i]['id'] == $id) $list[$i]['idx'] += $inc;
		$ps->db->update($ps->t_config_sources, array( 'idx' => $list[$i]['idx'] ), 'id', $list[$i]['id']);
	}
	unset($submit);

	// return nothing if this was an ajax request
	if ($ajax) {
		print "success";
		exit;
	}
}

$list = $ps->db->fetch_rows(1, 
	"SELECT l.*,s.lastupdate FROM $ps->t_config_sources l " . 
	"LEFT JOIN $ps->t_state s ON s.source=l.id " . 
	$ps->getsortorder($_order)
);
$total = $ps->db->count($ps->t_config_sources);
$pager = pagination(array(
	'baseurl'	=> psss_url_wrapper(array('sort' => $sort, 'order' => $order, 'limit' => $limit)),
	'total'		=> $total,
	'start'		=> $start,
	'perpage'	=> $limit, 
	'pergroup'	=> 5,
	'separator'	=> ' ', 
	'force_prev_next' => true,
	'next'		=> $cms->trans("Next"),
	'prev'		=> $cms->trans("Previous"),
));

// massage the sources array a bit so we don't have to do the logic in the theme template
$sources = array();
$first = $list ? $list[0]['id'] : array();
$last  = $list ? $list[ count($list) - 1]['id'] : array();
foreach ($list as $lp) {
	$lp['source'] = $ps->parse_source($lp);
	$lp['down'] ??= null;
	$lp['up'] ??= null;
	if ($lp['id'] == $first) {
		$lp['down'] = 1;
	} elseif ($lp['id'] == $last) {
		$lp['up'] = 1;
	} else {
		$lp['down'] = 1;
		$lp['up'] = 1;
	}
	$sources[] = $lp;
}

$cms->crumb('Manage', psss_url_wrapper(array('_base' => 'manage.php' )));
$cms->crumb('League Page Sources', psss_url_wrapper(array('_base' => 'sources.php' )));


// assign variables to the theme
$cms->theme->assign(array(
	'page'		=> $basename, 
	'sources'	=> $sources,
	'pager'		=> $pager,
));

// display the output
$cms->theme->add_css('css/2column.css');
$cms->theme->add_css('css/forms.css');
//$cms->theme->add_js('js/jquery.interface.js');	// for Hightlight fx
$cms->theme->add_js('js/sources.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
