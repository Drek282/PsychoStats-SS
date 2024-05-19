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
 *	Version: $Id: logout.php 450 2008-05-20 11:34:52Z lifo $
 */

define("PSYCHOSTATS_PAGE", true);
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Logout—PSSS');

$validfields = array('ref');
$_GET['ref'] = htmlspecialchars($_GET['ref'] ?? ''); //XSS Fix. Thanks to JS2007
$cms->theme->assign_request_vars($validfields, true);

# Page can only be viewed if user is logged in and site is not in maintenance mode.
if (!$cms->user->logged_in() or $maintenance and !$cms->user->is_admin()) previouspage('index.php');

$cms->session->online_status(0);

// assign variables to the theme
$cms->theme->assign(array(
	'season'		=> null,
	'season_c'		=> null,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
	// ...
));

// display the output
$cms->theme->add_css('css/forms.css');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
header("Refresh:3");

?>
