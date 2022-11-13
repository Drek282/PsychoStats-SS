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
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('PsychoStats - Team Registration');

$validfields = array('submit','cancel','ref');
$cms->theme->assign_request_vars($validfields, true);
		
switch ($ps->conf['main']['team_id']) {
	case 'team_id': $team_id_label = $cms->trans("Team #"); break;
};

$form = $cms->new_form();

//if ($cancel or $cms->user->logged_in()) previouspage('index.php');
if ($cancel) previouspage('index.php');

// If you are on this page $cookieconsent is assumed to be true.
$cms->session->options['cookieconsent'] = true;
$cookieconsent = $cms->session->options['cookieconsent'];

$form->default_modifier('trim');
$form->field('team_id', 'blank');
$form->field('username', 'blank');
$form->field('password', 'blank,password_match');
$form->field('password2', 'blank');
$form->field('email', 'blank, email');

if ($submit) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	if ($ps->conf['main']['registration'] == 'closed') {
		$form->error('fatal', $cms->trans("Team registration is currently disabled!"));
	}

	$u =& $cms->new_user();

	$id = $input['team_id'];
	$team = array();
	// lookup the worldid/team_id ... 
	if ($input['team_id'] != '') {
		if ($ps->conf['main']['team_id'] == 'ipaddr') {
			$id = sprintf("%u", ip2long($id));
		}
		$team = $ps->get_team_profile($id, 'team_id');
		if (!$team) {
			$form->error('team_id', $cms->trans("The %s does not exist!", $team_id_label));
		} elseif ($team['userid']) {
			$form->error('team_id', $cms->trans("This team is already registered!"));
		}

		if ($u->username_exists($input['username'])) {
			$form->error('username', $cms->trans("Username already exists!"));
		}
	}

	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		$userinfo = $input;
		// email is saved to profile, not user
		unset($userinfo['team_id'], $userinfo['password2'], $userinfo['email']);

		$userinfo['userid'] = $u->next_userid();
		$userinfo['password'] = $u->hash($userinfo['password']);
		$userinfo['accesslevel'] = $u->acl_user();
		$userinfo['confirmed'] = $ps->conf['main']['registration'] == 'open' ? 1 : 0;

		$ps->db->begin();
		$ok = $u->insert_user($userinfo);
		if ($ok) {
			$ok = $ps->db->update($ps->t_team_profile, 
				array( 'userid' => $userinfo['userid'], 'email' => $input['email'] ? $input['email'] : null), 
				'team_id', $id
			);
			if (!$ok) $form->error('fatal', $cms->trans("Error updating team profile: " . $ps->db->errstr));
		} else {
			$form->error('fatal', $cms->trans("Error creating user: " . $u->db->errstr));
		}

		if ($ok and !$form->has_errors()) {
			$ps->db->commit();

			// load this team
			$team = $ps->get_team($team['team_id'], true);
			$cms->theme->assign(array(
				'team'	=> $team,
				'reg'	=> $userinfo, 
			));

			// if registration is open log the user in
			if ($ps->conf['main']['registration'] == 'open') {
				$cms->session->online_status(1, $userinfo['userid']);
			}
	
			// display the registration confirmation
			$basename = basename(__FILE__, '.php') . '_confirmation';
			$cms->theme->add_css('css/forms.css');
			$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
			exit;
		} else {
			$ps->db->rollback();
		}
	}

} else {
	if ($ps->conf['main']['team_id'] == 'ipaddr') {
		$form->set('team_id', remote_addr());
	}

}

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

# Are there divisions or wilcards in this league?
$division = $ps->get_total_divisions() - 1;
$wildcard = $ps->get_total_wc();

// assign variables to the theme
$cms->theme->assign(array(
//	'team'		=> $ps->get_team(6375, true),
	'errors'	=> $form->errors(),
	'form'		=> $form->values(),
	'team_id_label' => $team_id_label,
	'division'		=> $division,
	'wildcard'		=> $wildcard,
	'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'cookieconsent'	=> $cookieconsent,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/forms.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');

// validator functions --------------------------------------------------------------------------

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
