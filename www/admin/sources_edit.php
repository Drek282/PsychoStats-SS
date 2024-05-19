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
 *	Version: $Id: sources_edit.php 530 2008-08-08 17:53:35Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
$basename = basename(__FILE__, '.php');
include("../includes/common.php");
include("./common.php");
$cms->theme->assign('page', 'sources');

$protocols = array( 'ftp', 'sftp', 'stream' );

$validfields = array('ref','id','del','submit','cancel','test');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'sources.php' )));
}

// load the matching source if an ID was given
$lp = array();
if (is_numeric($id)) {
	$lp = $ps->db->fetch_row(1, "SELECT * FROM $ps->t_config_sources WHERE id=" . $ps->db->escape($id));
	if (!$lp['id']) {
		$data = array(
			'message' => $cms->trans("Invalid lp Source ID Specified"),
		);
		$cms->full_page_err($basename, $data);
		exit();		
	}
} elseif (!empty($id)) {
	$data = array(
		'message' => $cms->trans("Invalid lp Source ID Specified"),
	);
	$cms->full_page_err($basename, $data);
	exit();		
}

// delete it, if asked to
if ($del and $lp['id'] == $id) {
	$ps->db->delete($ps->t_config_sources, 'id', $id);
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'sources.php' )));
/*
	$message = $cms->message('success', array(
		'message_title'	=> $cms->trans("lp Source Deleted"),
		'message'	=> $cms->trans("lp source '%s' deleted", $ps->parse_source($lp))
	));
*/
}

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('type', 'val_type');
$form->field('source', 'blank');
$form->field('delete', 'numeric');
$form->field('league_name', 'blank');
$form->field('enabled', 'numeric');
//$form->field('idx');

$lp['id'] ??= null;
if ($test and $lp['id'] == $id) { 	// test the lp source, if asked to
	$test = $form->values();
	$result = 'success';
	$msg = '';
	if (url_exists($test['source']) == true) {
		$msg = $cms->trans("league page was found and is accessible.");
	} else {
		$result = 'failure';
		$msg = $cms->trans("league page was not found or is not accessible.");
		$msg .= $cms->trans("<br>- Please verify the url");
	}

	if (preg_match('/^(\d|\w)+$/', $test['league_name']) && strstr($test['source'], $test['league_name'])) {
		$msg .= $cms->trans("<br>league name is in the correct format");
		$msg .= $cms->trans("<br>and is part of the league url.");
	} else {
		$result = 'failure';
		$msg .= $cms->trans("<br>the league name cannot contain any spaces or special");
		$msg .= $cms->trans("<br>characters and must be a part of the league url!");
		$msg .= $cms->trans("<br>- Please try again");
	}
	
	$message = $cms->message($result, array(
		'message_title'	=> $cms->trans("Testing Results"), 
		'message'	=> $msg
	));
	// don't let the form be submitted
	unset($submit);
}

// process the form if submitted
$valid = true;
$submit ??= null;
if ($submit) {
	// do some special error checking and correction depending on the source type
	$form->input['type'] = 'html';
	$type = $form->input['type'];

	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	// convert certain blank fields to nulls, so mysql 'strict' mode won't complain
	$nulls = array( 'source', 'league_name', 'delete' );
	foreach ($nulls as $n) {
		if ($input[$n] == '') {
			$input[$n] = null;
		}
	}

	if ($valid) {
		$ok = false;
		if ($id) {
			$ok = $ps->db->update($ps->t_config_sources, $input, 'id', $id);
		} else {
			$input['id'] = $ps->db->next_id($ps->t_config_sources);
//			$input['idx'] = $ps->db->max($ps->t_config_sources, 'idx') + 10;	// last source
			$input['idx'] = 0;							// first source
			$ok = $ps->db->insert($ps->t_config_sources, $input);
		}
		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage(psss_url_wrapper('sources.php'));
		}
/*
		$message = $cms->message('success', array(
			'message_title'	=> $cms->trans("Update Successfull"),
			'message'	=> $cms->trans("lp Source has been updated"))
		));
*/

	}

} else {
	// fill in defaults
	if (!$test) {
		if ($id) {
			$form->input($lp);
		} else {
			// new sources should default to being enabled
			$form->input['enabled'] = 1;
			$form->input['league_name'] = '';
		}
	}
}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('League Page URL', psss_url_wrapper('sources.php'));
$cms->crumb('Edit');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$cms->theme->assign(array(
	'protocols'	=> $protocols,
	'errors'	=> $form->errors(),
	'lp'		=> $lp,
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
));

// display the output
$cms->theme->add_css('css/forms.css');
//$cms->theme->add_js('js/jquery.interface.js');
$cms->theme->add_js('js/forms.js');
$cms->theme->add_js('js/sources.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

function val_type($var, $value, &$form) {
	global $valid, $cms, $protocols;
	if (!empty($value)) {
		if (!in_array($value, $protocols) and $value != 'html') {
			$valid = false;
			$form->error($var, $cms->trans("Invalid protocol selected"));
		}
	}
	return $valid;
}

?>
