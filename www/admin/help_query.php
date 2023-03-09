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
 *	Version: $Id: help_query.php $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");

$cms->crumb('Manage', psss_url_wrapper($_SERVER['REQUEST_URI']));
$cms->crumb('Awards', psss_url_wrapper($php_scnm));

$validfields = array('ref','start','limit','order','sort','filter','ajax','id','all','del');
$cms->theme->assign_request_vars($validfields, true);

if (!is_numeric($start) or $start < 0) $start = 0;
if (!is_numeric($limit) or $limit < 0) $limit = 50;
if (!in_array($order, array('asc','desc'))) $order = 'asc';
$sort = 'abs_total';

$_order = array(
	'start'	=> $start,
	'limit'	=> $limit,
	'order' => $order, 
	'sort'	=> $sort
);

// delete selected search queries
if (is_array($del) and count($del)) {
	$total_deleted = 0;
	foreach ($del as $id) {
		if ($ps->db->delete($ps->db->table('search_results'), 'search_id', $id)) {
			$total_deleted++;
		}
	}	
	$message = $cms->message('success', array(
		'message_title'	=> $cms->trans("Players Deleted!"),
		'message'	=> $cms->trans("%d players were deleted successfully", $total_deleted),
	));
}


// get a list of search queries
$awards = array();
$cmd = "SELECT * FROM $ps->t_search_results WHERE ";
$where = "1";
if ($filter != '') {
	$f = '%' . $ps->db->escape($filter) . '%';
	$where .= " AND phrase LIKE '%$f%'";
}
$cmd .= $where . " " . $ps->getsortorder($_order);
$list = $ps->db->fetch_rows(1, $cmd);

$total = $ps->db->count($ps->t_search_results, '*', $where);
$pager = pagination(array(
	'baseurl'	=> psss_url_wrapper(array('filter' => $filter) + $_order),
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
$s_queries = array();
foreach ($list as $sq) {
	$s_queries[] = $sq;
}

// assign variables to the theme
$cms->theme->assign(array(
	'page'		=> basename(__FILE__, '.php'), 
	'pager'		=> $pager,
	's_queries'	=> $s_queries,
	'text'		=> $cms->trans("Loading ..."),
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/2column.css');
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/h_query.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
