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
 *	Version: $Id: register.php 450 2008-05-20 11:34:52Z lifo $
 */

define("PSYCHOSTATS_PAGE", true);
$basename = basename(__FILE__, '.php');
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('PW Reset—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance and !$cms->user->is_admin()) previouspage('index.php');

$validfields = array('userid','tpw','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);
		
$team_id_label = $cms->trans("Team #");

//if ($cancel or $cms->user->logged_in()) previouspage('index.php');
if ($cancel) previouspage('index.php');

// If you are on this page $cookieconsent is assumed to be true.
$cms->session->options['cookieconsent'] = true;
$cookieconsent = $cms->session->options['cookieconsent'];

$form->default_modifier('trim');
$form->field('userid', 'blank');
$form->field('tpw', 'blank');
$form->field('password', 'blank,password_match');
$form->field('password2', 'blank');

// redirect to index.php if user logs in from this page
if ($cancel or $cms->user->logged_in()) previouspage('index.php');

$u = & $cms->new_user();

// load the userinfo
$userinfo = $u->load_user($userid, 'userid');

if (!$userinfo) {
	$message = $cms->trans("The user does not exist!");
}

// check to see if link has expired
if (!isset($message) && ((time() - $userinfo['tpw_timestamp']) > 172800)) {
	$message = $cms->trans("This reset password link has expired. If the account is still valid you may request another password reset.");
}

if ($submit and !isset($message)) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();

	$userinfo = array_merge($userinfo, $ps->get_team_profile($userinfo['userid'], 'userid'));
	$id = $userinfo['userid'];
	$userinfo['password'] = $u->hash($input['password']);

	// temp password check
	$ok = ($userinfo['temp_password'] == $tpw);

	if ($ok) {
		$ps->db->begin();
		// reset the timestamp
		$tpw_timestamp = 0;
		$ok = $ps->db->update($ps->t_user, 
			array( 'password' => $userinfo['password'], 'tpw_timestamp' => $tpw_timestamp, 'email_confirmed' => true ), 
			'userid', $id
		);
		if (!$ok) $message = $cms->trans("Error updating user: " . $ps->db->errstr);
	} else {
		$message = $cms->trans("Authentication error: " . $u->db->errstr);
	}

	if ($ok and !isset($message)) {
		$ps->db->commit();

		// ensure user is logged out
		$cms->session->online_status(0, $id);

		// assign variables to the theme
		$cms->theme->assign(array(
			'username'		=> $userinfo['username'],
			'team_id_label' => $team_id_label,
			'season'		=> null,
			'season_c'		=> null,
			'division'		=> $division,
			'wildcard'		=> $wildcard,
			'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
			'cookieconsent'	=> $cookieconsent,
		));

		// display the output
		$basename = basename(__FILE__, '.php') . '_confirmation';
		$cms->theme->add_css('css/forms.css');
		$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
		exit;
	} else {
		$ps->db->rollback();
	}
}

// if $message then we have an error
if (isset($message)) {
	$cms->full_page_err($basename, array(
		'message_title'	=> $cms->trans("Password Reset Failed"),
		'message'		=> $message,
		'division'		=> null,
		'wildcard'		=> null,
		'season'		=> null,
		'season_c'		=> null,
		'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
		'cookieconsent'	=> $cookieconsent,
	));
	exit();
}

// assign variables to the theme
$cms->theme->assign(array(
	'username'		=> $userinfo['username'],
	'errors'		=> $form->errors(),
	'form'			=> $form->values(),
	'team_id_label' => $team_id_label,
	'season'		=> null,
	'season_c'		=> null,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
$cms->theme->add_css('css/forms.css');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

function password_match($var, $value, &$form) {
	global $valid, $cms, $ps;
	if (!empty($value)) {
		if ($value != $form->input['password2']) {
			$valid = false;
			$form->error($var, $cms->trans("Passwords do not match"));
			$form->error('password2', $cms->trans("Passwords do not match"));
		}
	}
	return $valid;
}

?>
