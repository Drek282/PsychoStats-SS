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
 *	Version: $Id: owners.php $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");

$cms->crumb('Manage', psss_url_wrapper($_SERVER['REQUEST_URI']));
$cms->crumb('Owners', psss_url_wrapper($php_scnm));

$validfields = array('ref','order','sort','ajax');
$cms->theme->assign_request_vars($validfields, true);

if (!in_array($order, array('asc','desc'))) $order = 'asc';
$sort = 'team_id';

$_order = array(
	'order' => $order, 
	'sort'	=> $sort
);

// get a list of current owners
$owners = array();

$cmd  = "SELECT team_id, owner_name, firstseen, lastseen FROM $ps->t_team_ids_names a ";
$cmd .= "WHERE lastseen = (";
$cmd .= "SELECT MAX(lastseen) FROM $ps->t_team_ids_names b ";
$cmd .= "WHERE a.team_id=b.team_id AND a.owner_name != '') ";
$cmd .= "AND firstseen = (";
$cmd .= "SELECT MIN(firstseen) FROM $ps->t_team_ids_names c ";
$cmd .= "WHERE a.owner_name=c.owner_name) ";
$cmd .= $ps->getsortorder($_order);

$owners = $ps->db->fetch_rows(1, $cmd);

$total = count($owners);

// assign variables to the theme
$cms->theme->assign(array(
	'page'		=> basename(__FILE__, '.php'), 
	'owners'	=> $owners,
	'text'		=> $cms->trans("Loading ..."),
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/2column.css');
$cms->theme->add_css('css/forms.css');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
