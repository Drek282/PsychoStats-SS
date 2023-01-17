<?php
/**
 *	This file is part of PsychoStats.
 *
 *	Written by Jason Morriss
 *	Copyright 2008 Jason Morriss
 *
 *	PsychoStats is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public Licenhelpse as published by
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
 *	Version: $Id: help_edit.php $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");
$cms->theme->assign('page', 'help');

$validfields = array('ref','id','del','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'help.php' )));
}

// load the matching help if an ID was given
$help = array();
if (is_numeric($id)) {
	$help = $ps->db->fetch_row(1, "SELECT * FROM $ps->t_config_help WHERE id=" . $ps->db->escape($id));
	if (!$help['id']) {
		$data = array('message' => $cms->trans("Invalid help ID Specified"));
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();		
	}
} elseif (!empty($id)) {
	$data = array('message' => $cms->trans("Invalid help ID Specified"));
	$cms->full_page_err(basename(__FILE__, '.php'), $data);
	exit();		
}

// delete it, if asked to
if ($del and $help['id'] == $id) {
	$ps->db->delete($ps->t_config_help, 'id', $id);
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'help.php' )));
}

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('enabled');
$form->field('title','blank');
$form->field('img');
$form->field('content','blank');

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
		if ($id) {
			$ok = $ps->db->update($ps->t_config_help, $input, 'id', $id);
		} else {
			$input['id'] = $ps->db->next_id($ps->t_config_help);
			$ok = $ps->db->insert($ps->t_config_help, $input);
		}
		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage(psss_url_wrapper('help.php'));
		}
	}

} else {
	// fill in defaults
	if ($id) {
		$form->input($help);
	} else {
		// new help should default to being enabled
		$form->input['enabled'] = 1;
		$form->input['limit'] = 5;
		$form->input['order'] = 'desc';
		$form->input['format'] = '%s';
	}
}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('Awards', psss_url_wrapper('help.php'));
$cms->crumb('Edit');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$tokens ??= null;
$cms->theme->assign(array(
	'errors'	=> $form->errors(),
	'help'		=> $help,
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'tokens'	=> $tokens,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/forms.js');
$cms->theme->add_js('js/help.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
