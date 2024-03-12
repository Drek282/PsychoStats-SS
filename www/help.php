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
 *	Version $Id: help.php $
 */
define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('Help—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance) previouspage('index.php');

// collect url parameters ...
$validfields = array('q','search','like');
$cms->theme->assign_request_vars($validfields, true);

// Default limit.
$DEFAULT_LIMIT = 5;

// if $q is longer than 100 characters we have a problem
if (strlen($q) > 100) {
	$cms->full_page_err('awards', array(
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'message_title'	=> $cms->trans("Invalid Search String"),
		'message'		=> $cms->trans("Searches are limited to 100 characters in length."),
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

unset($list);

$limit = $DEFAULT_LIMIT;

// Grab list of top help entries.
$cmd = "SELECT * FROM $ps->t_config_help ORDER BY idx LIMIT $limit";

$results = array();
$results = $ps->db->fetch_rows(1, $cmd);

// if $results is empty then we have no help entries in the database
if (empty($results)) {
	$cms->full_page_err('help', array(
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'message_title'	=> $cms->trans("No Help Entries Found"),
		'message'		=> $cms->trans("There are currently no help entries to display."),
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

// Iterate through list of help entriess.
foreach ($results as $h => $var) {

	// Only process help entry if it is enabled.
	if ($results[$h]['enabled'] == 0) continue;

	// fetch help item...
	$help[$h] = $ps->get_help(array(
		'id'			=> $results[$h]['id'],
		'enabled'		=> $results[$h]['enabled'],
		'idx'			=> $results[$h]['idx'],
		'title'			=> $results[$h]['title'],
		'content'		=> $results[$h]['content'],
		'img'			=> $results[$h]['img'],
		'weight'		=> $results[$h]['weight'],
	));
}
unset ($results);

$top_help = $ps->get_top_help();

$total = array();
$results = array();
if ($q != '') {
	// a new search was requested (a query string was given)
	$search = $ps->init_search();
	$matched = $ps->search_help($search, array(
		'phrase'	=> $q,
		'mode'		=> 'contains',
	));
	$results = $ps->get_search($search);
	
} else if ($ps->is_search($search)) {
	// an existing search was requested (new page or sort)
	$results = $ps->get_search($search);
	
} else {
	// no search, just fetch a list teams
	$search = '';
}

// determine the total teams found
$total['all'] = $ps->get_total_help();
$search_blurb ??= null;
if ($results && $matched['help']) {
	$total['results'] = $matched['count'];
	unset($help);
	$help = $matched['help'];
	$search_blurb = $cms->trans('search criteria "<em>%s</em>" matched %d help entries out of %d total',
			psss_escape_html($q), $total['results'], $total['all']
		);
} elseif ($search) {
	$search_blurb = $cms->trans('either your search criteria was too general or no help entries contained your query');
}

// if there is no 'w' key sort the array by idx
if (isset($help[1]['w'])) {
	$idx = array_column($help, 'w');
	array_multisort($idx, SORT_DESC, $help);
} else {
	$idx = array_column($help, 'idx');
	array_multisort($idx, SORT_ASC, $help);
}
unset($idx);
unset($hsort);
//print_r($help);

// feedback system
// ten minute delay between ratings
$cms->session->options['ltime'] ??= 0;
$lrest = $cms->session->options['ltime'] + $ps->conf['main']['fb_delay'];
if ($like && $lrest < time()) {
	$cms->session->opt('ltime', time());
	$cms->session->save_session_options();
	$lary = explode(':', $like);
	$lid = $lary[0];
	$ll = $lary[1];
	$wght = implode($ps->db->fetch_row(1, "SELECT weight FROM $ps->t_config_help WHERE id=$lid"));
	$wght = $wght + $ll;
	$ps->db->update($ps->t_config_help, array( 'weight' => $wght ), 'id', $lid);
	unset($like);
	unset($lid);
	unset($ll);
	unset($wght);
}

// Declare shades array.
$shades = array(
	's_popular'		=> null,
);

// assign variables to the theme
$cms->theme->assign(array(
	'oscript'		=> $oscript,
	'maintenance'	=> $maintenance,
	'search'		=> $search,
	'results'		=> $results,
	'search_blurb'	=> $search_blurb,
	'page'			=> basename(__FILE__,'.php'),
	'help'			=> $help,
	'top_help'		=> $top_help,
	'himgs_url'		=> $ps->conf['theme']['himgs_url'],
	'language_list'	=> $cms->theme->get_language_list(),
	'theme_list'	=> $cms->theme->get_theme_list(),
	'language'		=> $cms->theme->language,
	'lastupdate'	=> $lastupdate,
	'season'		=> null,
	'season_c'		=> null,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'shades'		=> $shades,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/help.css');
$cms->theme->add_css('css/2column.css');	// this page has a left column
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

function tablesearchlink($val) {
	return psss_table_search_link($val);
}

?>
