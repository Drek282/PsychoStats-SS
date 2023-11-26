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
 *	Version: $Id: owners_edit.php $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");
$cms->theme->assign('page', 'owners');

$validfields = array('ref','team_id','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'owners.php' )));
}

// load the matching team if team_id was given
$team = array();
if (is_numeric($team_id)) {
	$cmd  = "SELECT team_id, owner_name FROM $ps->t_team_ids_names a ";
	$cmd .= "WHERE lastseen = (";
	$cmd .= "SELECT MAX(lastseen) FROM $ps->t_team_ids_names b ";
	$cmd .= "WHERE a.team_id=$team_id AND a.owner_name != '') ";
	$team = $ps->db->fetch_row(1, $cmd);

	if (!$team['team_id']) {
		$data = array('message' => $cms->trans("Invalid Team # Specified"));
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();		
	}
} elseif (!empty($team_id)) {
	$data = array('message' => $cms->trans("Invalid Team # Specified"));
	$cms->full_page_err(basename(__FILE__, '.php'), $data);
	exit();		
}

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('team_id','blank');
$form->field('owner_name','blank');

// process the form if submitted
$valid = true;
if ($submit) {
	$form->validate();
	$input = $form->values();

	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	if (stristr($input['owner_name'], $team['owner_name']) or stristr($team['owner_name'], $input['owner_name'])) {
		$form->error('owner_name', $cms->trans("%s is already listed as the owner of this team!", $team['owner_name']));
	}
	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		$ok = false;
		$current_date = date('Y-m-d');
		$id = $ps->db->next_id($ps->t_team_ids_names);

		// First update all the lastseen dates for current
		// owner names and team names with the current date
		$cmd  = "SELECT MAX(lastseen) lastseen FROM $ps->t_team_ids_names LIMIT 1";

		$lastseen = $ps->db->fetch_row(1, $cmd);
		if (is_array($lastseen)) $lastseen = implode($lastseen);
		
		$ok = $ps->db->update($ps->t_team_ids_names, 
			array( 'lastseen' => $current_date), 
			'lastseen', $lastseen
		);
		if (!$ok) $form->error('fatal', $cms->trans("Error updating team dates: " . $ps->db->errstr));
		
		// reset the team names with matching team_id to the previous date
		$cmd = "UPDATE $ps->t_team_ids_names SET lastseen = '$lastseen' WHERE team_id=$team_id AND owner_name='" . $team['owner_name'] . "' AND lastseen='$current_date'";

		$ok = $ps->db->query($cmd);
		if (!$ok) $form->error('fatal', $cms->trans("Error updating team dates: " . $ps->db->errstr));

		$team_ids_names = array(
			'id'			=> $id,
			'team_id'		=> $team_id,
			'team_name'		=> '',
			'owner_name'	=> $input['owner_name'],
			'firstseen'		=> $current_date,
			'lastseen'		=> $current_date
		);
		$ok = $ps->db->insert($ps->t_team_ids_names, $team_ids_names);
		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage(psss_url_wrapper('owners.php'));
		}
	}

}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('Owners', psss_url_wrapper('owners.php'));
$cms->crumb('Edit');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$tokens ??= null;
$cms->theme->assign(array(
	'errors'	=> $form->errors(),
	'team_id'	=> $team_id,
	'team'		=> $team,
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'tokens'	=> $tokens,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/forms.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
