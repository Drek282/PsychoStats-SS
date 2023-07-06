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
 *	Version: $Id: teams_edit.php 389 2008-04-18 15:04:10Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");
$cms->theme->assign('page', 'users');

$validfields = array('ref','id','del','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'teams.php' )));
}

// load the matching team if an ID was given
$team = array();
$team_user =& $cms->new_user();

if ($id) {
	// load the team based on their team_id
	$team = $ps->get_team_profile($id);
	if ($team and $team['team_id'] == null) { // no matching profile; lets create one (all teams should have one, regardless)
		$_id = $ps->db->escape($id, true);
		list($uid) = $ps->db->fetch_list("SELECT team_id FROM $ps->t_team WHERE team_id=$_id");
		list($team_name) = $ps->db->fetch_list("SELECT team_name FROM $ps->t_team_ids_names WHERE team_id=$_id ORDER BY lastseen DESC LIMIT 1");
		$ps->db->insert($ps->t_team_profile, array( 'team_id' => $uid ));
		$team['team_id'] = $uid;
		$team['team_name'] = $team_name;
	}

	if (!$team) {
		$data = array( 'message' => $cms->trans("Invalid team ID Specified") );
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();		
	}
	if ($team['userid']) {
		$team_user->load($team['userid']);
		if (!$team_user->userid()) {	// the user doesn't actually exist
			// remove userid from team profile
			$ps->db->update($ps->t_team_profile, array( 'userid' => null ), 'team_id', $team['team_id']);
			$team_user->userid(0);
		}
	}
} else {
	$data = array( 'message' => $cms->trans("Invalid team ID Specified") );
	$cms->full_page_err(basename(__FILE__, '.php'), $data);
	exit();		
}

// delete it, if asked to
if ($del and $id and $team['team_id'] == $id) {
	if (!$ps->delete_team($id)) {
		$data = array( 'message' => $cms->trans("Error deleting team: " . $ps->db->errstr) );
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();
	}
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'teams.php' )));
}

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('namelocked');
if ($cms->user->is_admin()) {
	$form->field('username');
	$form->field('password');
	$form->field('password2');
	$form->field('email', 'email');
	$form->field('email2');
	$form->field('accesslevel');

//	$form->field('confirmed');
}

// process the form if submitted
$valid = true;
if ($submit) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	if (!array_key_exists($input['accesslevel'], $cms->user->accesslevels())) {
		$form->error('accesslevel', $cms->trans("Invalid access level specified"));
	}

	if (!$form->error('username') and $input['username'] != '') {
		// load the user matching the username
		$_u = $team_user->load_user($input['username'], 'username');
		// do not allow a duplicate username if another user has it already
		if ($_u and $_u['userid']) {
			$form->error('username', $cms->trans("Username already exists; please try another name"));
		}
		unset($_u);
	}

	// if a username is given we need to make sure a password and email address was provided too (if there wasn't one already)
	if ($input['username'] != '') {
		// verify the passwords match if one was specified
		if (!$team_user->userid() and $input['password'] == '') {
			$form->error('password', $cms->trans("A password must be entered for new users"));
		} elseif ($input['password'] != '') {
			if ($input['password'] != $input['password2']) {
				$form->error('password', $cms->trans("Passwords do not match; please try again"));
				$form->error('password2', ' ');
			} elseif ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email']) && $input['email'] == '') {
				$form->error('email', $cms->trans("An email address must be entered for new users"));
			} else {
				if ($input['email'] != $input['email2']) {
					$form->error('email', $cms->trans("Email addresses do not match; please try again"));
					$form->error('email2', ' ');
				}
			}
		} else {
			unset($input['password']);
			unset($input['email']);
		}
		unset($input['password2']);
		unset($input['email2']);
	}

	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		// setup user record
		$u['username'] = $input['username'];
		$u['password'] = $team_user->hash($input['password']);
		//$u['email'] = $input['email'];
		$u['accesslevel'] = $input['accesslevel'];
		$u['confirmed'] = 1;
		unset($input['username']);
		unset($input['team_id']);
		unset($input['password']);
		unset($input['password2']);
		//unset($input['email']);
		unset($input['email2']);
		unset($input['accesslevel']);

        if (!$input['namelocked']) $input['namelocked'] = 0;

		// save a NEW user record if this team didn't have one
		$inserted = false;
		if (!$team_user->userid() and $u['username'] != '') {
			$inserted = true;

			if ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {
				$u['temp_password'] = $team_user->hash(psss_generate_pw());
				$u['email_confirmed'] = 0;
				$u['tpw_timestamp'] = time();
			} else {
				$u['temp_password'] = null;
				$u['email_confirmed'] = 1;
				$u['tpw_timestamp'] = 0;
			}
			$u['userid'] = $team_user->next_userid();	// assign an ID
			$input['userid'] = $u['userid'];		// point the team_profile to this userid
			$ok = $team_user->insert_user($u);
			if (!$ok) {
				$form->error('fatal', $cms->trans("Error saving user: " . $team_user->db->errstr));
			} else {
				$ok = $ps->db->update($ps->t_team_profile, 
					array( 'userid' => $input['userid'], 'email' => $input['email'] ? $input['email'] : null, 'cc' => $input['cc'] ? $input['cc'] : null), 
					'team_id', $id
					);
				if (!$ok) {
					$form->error('fatal', $cms->trans("Error updating team profile: " . $ps->db->errstr));
				} else {
					$team_user->load($u['userid']);

					// send email confirmation notice if email notifictions are enabled
					if ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {

						// Setup the confirmation url.
						$base_url = $ps->conf['main']['base_url'];
						$confirmation_url = $base_url . "/pwreset_final.php?username=" . $u['username'] . "&tpw=" . $u['temp_password'];

						$cms->theme->assign(array(
							'confirmation_url'	=> $confirmation_url,
						));

						// Setup email variables.
						$email = $input['email'];
						$from = $ps->conf['main']['email']['admin_email'];
						$subject = "Please choose a password for your new account";
						$template = 'confirmation';
						// Setup the email page.
						$email_page = $cms->return_email_page($template, 'email_header', 'email_footer');
						// Setup the email headers.
						$headers  = 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
						$headers .= 'From: '.$from."\r\n".
    						'Reply-To: '.$from."\r\n" .
    						'X-Mailer: PHP/' . phpversion();

						psss_send_mail($email, $subject, $email_page, $headers);

					}
				}
			}
		}

		$ok = false;
		// update team record (even if the user failed to insert above)
		if ($id) {
			$ok = $ps->db->update($ps->t_team_profile, $input, 'team_id', $team['team_id']);
		} else {
			$ok = $ps->db->insert($ps->t_team_profile, $input);
		}

		// update user record if something was changed
		if (!$inserted and $ok) {
			$changed = false;
			foreach (array('username', 'password', 'temp_password', 'tpw_timestamp', 'accesslevel', 'email_confirmed', 'confirmed') as $k) {
				if (!array_key_exists($k, $u)) continue;
				if ($team_user->$k() != $u[$k]) {
					$changed = true;
					break;
				}
			}
			if ($changed) {
				$ok = $team_user->update_user($u, $team_user->userid());
			}
		}

		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage(psss_url_wrapper('teams.php'));
		}

	}

} else {
	// fill in defaults
	if ($id) {
		$in = $team;
		if ($team_user->userid()) {
			$in = array_merge($in, $team_user->to_form_input());
		} else {
			$in['accesslevel'] = $team_user->acl_user();
		}
		$form->input($in);
	} else {
//		$form->set('accesslevel', $team_user->acl_user());
//		$form->set('confirmed', 1);
	}
}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('Teams', psss_url_wrapper('teams.php'));
$cms->crumb('Edit');

// save a new form key in the teams session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$username ??= null;

$allowed_html_tags = str_replace(',', ', ', $ps->conf['theme']['format']['allowed_html_tags']);
if ($allowed_html_tags == '') $allowed_html_tags = '<em>' . $cms->translate("none") . '</em>';
$cms->theme->assign(array(
	'errors'	=> $form->errors(),
	'team'		=> $team,
	'team_user'	=> $team_user->to_form_input(),
	'allowed_html_tags' => $allowed_html_tags,
	'accesslevels'	=> $team_user->accesslevels(),
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'username'	=> $username,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
//$cms->theme->add_js('js/jquery.interface.js');
$cms->theme->add_js('js/forms.js');
$cms->theme->add_js('js/teams.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
