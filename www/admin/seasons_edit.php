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
 *	Version: $Id: seasons_edit.php $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
$basename = basename(__FILE__, '.php');
include("../includes/common.php");
include("./common.php");
$cms->theme->assign('page', 'seasons');

$validfields = array('ref','season_h','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'seasons.php' )));
}

// load the matching season if season_h was given
$season = array();
if (is_numeric($season_h)) {
	$season = $ps->db->fetch_row(1, "SELECT * FROM $ps->t_seasons_h WHERE season_h=" . $ps->db->escape($season_h));
	if (!$season['season_h']) {
		$data = array('message' => $cms->trans("Invalid Season Specified"));
		$cms->full_page_err($basename, $data);
		exit();		
	}
} elseif (!empty($season_h)) {
	$data = array('message' => $cms->trans("Invalid Season Specified"));
	$cms->full_page_err($basename, $data);
	exit();		
}

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('season_l','blank');
$form->field('season_h','blank');

// process the form if submitted
$valid = true;
if ($submit) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		$ok = false;
		if ($season_h) {
			$ok = $ps->db->update($ps->t_seasons_h, $input, 'season_h', $season_h);
		} else {
			$input['season_h'] = $ps->db->next_season_h($ps->t_seasons_h);
			$ok = $ps->db->insert($ps->t_seasons_h, $input);
		}
		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage(psss_url_wrapper('seasons.php'));
		}
	}

} else {
	// fill in defaults
	if ($season_h) {
		$form->input($season);
	} else {
		$form->input['season_l'] = 162;
	}
}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('Seasons', psss_url_wrapper('seasons.php'));
$cms->crumb('Edit');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$tokens ??= null;
$cms->theme->assign(array(
	'errors'	=> $form->errors(),
	'season_h'	=> $season_h,
	'season'	=> $season,
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'tokens'	=> $tokens,
));

// display the output
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/forms.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
