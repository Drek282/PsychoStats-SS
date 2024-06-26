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
 *	Version: $Id: awards_edit.php 420 2008-04-27 15:24:49Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
$basename = basename(__FILE__, '.php');
include("../includes/common.php");
include("./common.php");
$cms->theme->assign('page', 'awards');

$validfields = array('ref','id','del','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'awards.php' )));
}

// load the matching award if an ID was given
$award = array();
if (is_numeric($id)) {
	$award = $ps->db->fetch_row(1, "SELECT * FROM $ps->t_config_awards WHERE id=" . $ps->db->escape($id));
	if (!$award['id']) {
		$data = array('message' => $cms->trans("Invalid award ID Specified"));
		$cms->full_page_err($basename, $data);
		exit();		
	}
} elseif (!empty($id)) {
	$data = array('message' => $cms->trans("Invalid award ID Specified"));
	$cms->full_page_err($basename, $data);
	exit();		
}

// delete it, if asked to
if ($del and $award['id'] == $id) {
	$ps->db->delete($ps->t_config_awards, 'id', $id);
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'awards.php' )));
}

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('enabled');
$form->field('negative');
$form->field('award_name','blank');
$form->field('phrase','blank');
$form->field('groupname');
$form->field('expr','blank');
$form->field('order');
$form->field('where');
$form->field('limit','numeric');
$form->field('format');
$form->field('description');

// process the form if submitted
$valid = true;
if ($submit) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	// lets keep the description plain... no html.
	$input['description'] = psss_strip_tags($input['description']);

	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		$ok = false;
		if ($id) {
			$ok = $ps->db->update($ps->t_config_awards, $input, 'id', $id);
		} else {
			$input['id'] = $ps->db->next_id($ps->t_config_awards);
			$ok = $ps->db->insert($ps->t_config_awards, $input);
		}
		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage(psss_url_wrapper('awards.php'));
		}
	}

} else {
	// fill in defaults
	if ($id) {
		$form->input($award);
	} else {
		// new awards should default to being enabled
		$form->input['enabled'] = 1;
		$form->input['limit'] = 5;
		$form->input['order'] = 'desc';
		$form->input['format'] = '%s';
	}
}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('Awards', psss_url_wrapper('awards.php'));
$cms->crumb('Edit');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$tokens ??= null;
$cms->theme->assign(array(
	'errors'	=> $form->errors(),
	'award'		=> $award,
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'tokens'	=> $tokens,
));

// display the output
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/forms.js');
$cms->theme->add_js('js/awards.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
