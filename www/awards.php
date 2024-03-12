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
 *	Version $Id: awards.php 495 2008-06-18 18:41:37Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('HoF—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance) previouspage('index.php');

// collect url parameters ...
$validfields = array('t');
$cms->theme->assign_request_vars($validfields, true);

if (!is_numeric($t)) $t = '';
$_t = $ps->db->escape($t, true);

// Defaults limit.
$DEFAULT_LIMIT = 5;

$order ??= 'desc';
$limit ??= $DEFAULT_LIMIT;

unset($list);

// Get list of teams and the division titles and championships count.
$dt_count = $ps->get_tc_count('dt', $limit);
$lc_count = $ps->get_tc_count('lc', $limit);

// Grab list of awards.
$cmd = "SELECT * FROM $ps->t_config_awards";

$results = array();
$results = $ps->db->fetch_rows(1, $cmd);

// if $results is empty then we have no awards in the database
if (empty($results)) {
	$cms->full_page_err('awards', array(
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'message_title'	=> $cms->trans("No Awards Found"),
		'message'		=> $cms->trans("There are currently no awards to display."),
		'lastupdate'	=> $lastupdate,
		'division'		=> $division,
		'wildcard'		=> $wildcard,
		'season'		=> null,
		'season_c'		=> null,
		'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
		'cookieconsent'	=> $cookieconsent,
	));
	exit();
}

// Iterate through list of awards.
foreach ($results as $a => $var) {

	// Only process award if it is enabled.
	if ($results[$a]['enabled'] == 0) continue;

	// fetch award...
	$awards[$a] = $ps->get_award(array(
		'id'			=> $results[$a]['id'],
		'enabled'		=> $results[$a]['enabled'],
		'idx'			=> $results[$a]['idx'],
		'negative'		=> $results[$a]['negative'],
		'award_name'	=> $results[$a]['award_name'],
		'groupname'		=> $results[$a]['groupname'],
		'phrase'		=> $results[$a]['phrase'],
		'expr'			=> $results[$a]['expr'],
		'where'			=> $results[$a]['where'],
		'format'		=> $results[$a]['format'],
		'description'	=> $results[$a]['description'],
		'order'			=> $results[$a]['order'],
		'limit'			=> $results[$a]['limit'],
	));
}
unset ($results);

// Sort the array by idx.
$idx = array_column($awards, 'idx');
array_multisort($idx, SORT_ASC, $awards);
unset ($idx);
//print_r($awards);

// Declare shades array.
$shades = array(
	's_titles'		=> null,
	's_championships'		=> null,
);

// assign variables to the theme
$cms->theme->assign(array(
	'oscript'		=> $oscript,
	'maintenance'	=> $maintenance,
	'page'			=> basename(__FILE__,'.php'),
	'awards'		=> $awards,
	'language_list'	=> $cms->theme->get_language_list(),
	'theme_list'	=> $cms->theme->get_theme_list(),
	'language'		=> $cms->theme->language,
	'lastupdate'	=> $lastupdate,
	'season'		=> null,
	'season_c'		=> null,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'team_id'		=> $t,
	'dt_count'		=> $dt_count,
	'lc_count'		=> $lc_count,
	'shades'		=> $shades,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/2column.css');	// this page has a left column
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

?>
