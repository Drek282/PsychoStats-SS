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
 *	Version: $Id: editteam.php 549 2008-08-24 23:54:06Z lifo $
 */

define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->theme->page_title('Edit Team Profile—PSSS');

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance) previouspage('index.php');

// If you are on this page $cookieconsent is assumed to be true.
$cms->session->options['cookieconsent'] = true;
$cookieconsent = $cms->session->options['cookieconsent'];

$validfields = array('ref','id','del','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'index.php' )));
}

// load the matching team if an ID was given
$team = array();
$team_user =& $cms->new_user();
$allow_username_change = false;

if ($id) {
	// load the team based on their team_id
	$team = $ps->get_team_profile($id);
	if ($team and $team['team_id'] == null) { // no matching profile; lets create one (all teams should have one, regardless)
		$_id = $ps->db->escape($id, true);
		list($uid) = $ps->db->fetch_list("SELECT team_id FROM $ps->t_team WHERE team_id=$_id");
		list($team_name) = $ps->db->fetch_list("SELECT team_name FROM $ps->t_team_ids_name WHERE team_id=$_id ORDER BY lastseen DESC LIMIT 1");
		list($owner_name) = $ps->db->fetch_list("SELECT owner_name FROM $ps->t_team_ids_name WHERE team_id=$_id ORDER BY lastseen DESC LIMIT 1");
		$team['team_id'] = $uid;
	}

	if (!$team) {
		$data = array(
			'oscript'		=> $oscript,
			'maintenance'	=> $maintenance,
			'division'		=> $division,
			'wildcard'		=> $wildcard,
			'cookieconsent'	=> $cookieconsent,
			'message' => $cms->trans("Invalid team ID Specified")
		);
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
	$data = array(
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'division'		=> $division,
		'wildcard'		=> $wildcard,
		'cookieconsent'	=> $cookieconsent,
		'message' => $cms->trans("Invalid team ID Specified")
	);
	$cms->full_page_err(basename(__FILE__, '.php'), $data);
}

// check privileges to edit this team
if (!psss_user_can_edit_team($team)) {
	$cms->theme->assign(array(
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'lastupdate'	=> $lastupdate,
		'season'		=> null,
		'season_c'		=> null,
		'division'		=> $division,
		'wildcard'		=> $wildcard,
		'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
		'cookieconsent'	=> $cookieconsent,
	));
	$data = array( 'message' => $cms->trans("Insufficient privileges to edit team!") );
	$cms->full_page_err(basename(__FILE__, '.php'), $data);
	exit;
}

// delete the user, if asked to
/* we don't want normal users deleting themselves ... */
if ($cms->user->is_admin() and $del and $id and $team['team_id'] == $id) {
	if (!$cms->user->delete_user($team['userid'])) {
		$data = array( 'message' => $cms->trans("Error deleting user: " . $ps->db->errstr) );
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();
	}
	$ps->db->update($ps->t_team_profile, array( 'userid' => null, 'name' => '', 'email' => null, 'youtube' => null, 'website' => null, 'icon' => null, 'cc' => null, 'logo' => null ), 'userid', $team['userid']);
	// don't use previouspage, since chances are the team.php is the referrer and will no longer be valid.
	gotopage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'index.php' )));
}
/**/

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('owner_name');
$form->field('youtube');
$form->field('website');
$form->field('icon');
$form->field('cc');
$form->field('logo');
$form->field('namelocked');
$form->field('email', 'email,email_match');
$form->field('email2', 'email');
$form->field('password', 'password_match');
$form->field('password2');
if ($cms->user->is_admin()) {
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

	// can only complete an owner name, not change it
	$match = "/" . $team['owner_name'] . "/";
	if (!empty($input['owner_name']) and !preg_match($match, $input['owner_name'])) {
		$form->error('owner_name', $cms->trans("You can only add to an incomplete name."));
        $form->set('owner_name', $team['owner_name']);
	}

	// force a protocol prefix on the website url (http://)
	if (!empty($input['website']) and !preg_match('|^\w+://|', $input['website'])) {
		$input['website'] = "http://" . $input['website'];
	}

	// return error if website address does not exist or is unreachable
	if (!empty($input['website']) and !url_exists($input['website'])) {
        $form->error('website', $cms->trans("The web address is unreachable.") . " " .
            $cms->trans("Resubmit to try again.") 
			);
		$website ??= null;
        $form->set('website', $website);
	}

	// return error if youtube user name is not in correct format
	if (!empty($input['youtube']) and !preg_match('|^[a-zA-Z0-9_-]{1,}$|', $input['youtube'])) {
        $form->error('youtube', $cms->trans("YouTube user name not in correct format.") . " " .
            $cms->trans("Resubmit to try again.") 
			);
		$youtube ??= null;
        $form->set('youtube', $youtube);
	}
	
	// strip out any bad tags from the logo.
	if (!empty($input['logo'])) {
		$logo = psss_strip_tags($input['logo']);
		$c1 = md5($logo);
		$c2 = md5($input['logo']);
		if ($c1 != $c2) {
			$form->error('logo', $cms->trans("Invalid tags were removed.") . " " .
				$cms->trans("Resubmit to try again.") 
			);
			$logo ??= null;
			$form->set('logo', $logo);
		}
		$input['logo'] = $logo;
	}

	if ($cms->user->is_admin()) {
		if (!array_key_exists($input['accesslevel'], $cms->user->accesslevels())) {
			$form->error('accesslevel', $cms->trans("Invalid access level specified"));
		}
	}

	// if password is provided, make sure they match
	if ($team_user->userid() and $input['password'] != '') {
		// verify the passwords match if one was specified
		if (!$team_user->userid() and $input['password'] == '') {
			$form->error('password', $cms->trans("A password must be entered for new users"));
		} elseif ($input['password'] != '') {
			if ($input['password'] != $input['password2']) {
				$form->error('password', $cms->trans("Passwords do not match; please try again"));
				$form->error('password2', ' ');
			}
		} else {
			unset($input['password']);
		}
		unset($input['password2']);
	}

	
	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		// setup user record
		$u['username'] = $team_user->username();
		if ($input['password'] != '') $u['password'] = $team_user->hash($input['password']);
		if ($cms->user->is_admin()) {
			$u['accesslevel'] = $input['accesslevel'];
			$u['confirmed'] = 1; //$input['confirmed'];
		}
		unset($input['username']);
		unset($input['password']);
		unset($input['password2']);
		unset($input['email2']);
		unset($input['accesslevel']);

		$input['cc'] = strtoupper($input['cc']);
		if (!$input['namelocked']) $input['namelocked'] = 0;

		// save a NEW user record if this team didn't have one
		$inserted = false;
		if (!$team_user->userid() and $u['username'] != '') {
			$inserted = true;
			$u['userid'] = $team_user->next_userid();	// assign an ID
			$input['userid'] = $u['userid'];		// point the team_profile to this userid
			$ok = $team_user->insert_user($u);
			if (!$ok) {
				$form->error('fatal', $cms->trans("Error saving user: " . $team_user->db->errstr));
				unset($input['userid']);
			} else {
				$team_user->load($u['userid']);
			}
		}

		// update team record (even if the user failed to insert above)
		if ($id) {
			if ($input['email'] == '') unset($input['email']);
			$owner_name = $input['owner_name'];
			unset($input['owner_name']);
			$ok = $ps->db->update($ps->t_team_profile, $input, 'team_id', $team['team_id']);
			$cmd  = "UPDATE $ps->t_team_ids_names SET owner_name = '$owner_name' ";
			$cmd .= "WHERE team_id='" . $team['team_id'] . "' AND owner_name='" . $team['owner_name'] . "'";
			if ($ok) $ok = $ps->db->query($cmd);
		}

		// update user record if something was changed
		if (!$inserted and $ok) {
			$changed = false;
			foreach (array('username', 'password', 'accesslevel', 'confirmed') as $k) {
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

		// if email was changed require email confirmation
		if (isset($input['email']) && $ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {
			$userinfo['temp_password'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? $team_user->hash(psss_generate_pw()) : null;
			$userinfo['tpw_timestamp'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? time() : 0;
			$userinfo['email_confirmed'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? 0 : 1;

			$ps->db->begin();
			$ok = $team_user->update_user($userinfo, $team_user->userid());

			if ($ok and !$form->has_errors()) {
				$ps->db->commit();

				// load this team
				$user = $team_user->load_user($team_user->userid());

				$cms->theme->assign(
					array(
						'user' => $user,
						'lastupdate' 	=> $lastupdate,
						'season'		=> null,
						'season_c' 		=> null,
						'division' 		=> $division,
						'wildcard' 		=> $wildcard,
						'form_key' 		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
						'cookieconsent' => $cookieconsent,
					)
				);

				// Setup the confirmation url.
				$base_url = $ps->conf['main']['base_url'];
				$confirmation_url = $base_url . "/email_confirmation.php?user=" . $user['username'] . "&tpw=" . $user['temp_password'];

				$cms->theme->assign(
					array(
						'confirmation_url' => $confirmation_url,
					)
				);

				// Setup email variables.
				$email = $input['email'];
				$from = $ps->conf['main']['email']['admin_email'];
				$subject = "Please confirm your email address";
				$template = 'confirmation';
				// Setup the email page.
				$email_page = $cms->return_email_page($template, 'email_header', 'email_footer');
				// Setup the email headers.
				$headers = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= 'From: ' . $from . "\r\n" .
					'Reply-To: ' . $from . "\r\n" .
					'X-Mailer: PHP/' . phpversion();

				psss_send_mail($email, $subject, $email_page, $headers);

			}

			// display the registration confirmation
			$basename = basename(__FILE__, '.php') . '_confirmation';
			$cms->theme->add_css('css/forms.css');
			$cms->full_page($basename, $basename, $basename . '_header', $basename . '_footer');
			exit;
		}

		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage('index.php');
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
	}
}

// save a new form key in the teams session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$uid = $team['team_id'];

$allowed_html_tags = str_replace(',', ', ', $ps->conf['theme']['format']['allowed_html_tags']);
if ($allowed_html_tags == '') $allowed_html_tags = '<em>' . $cms->translate("none") . '</em>';
$cms->theme->assign(array(
	'oscript'				=> $oscript,
	'maintenance'			=> $maintenance,
	'page'					=> basename(__FILE__, '.php'), 
	'errors'				=> $form->errors(),
	'team'					=> $team,
	'team_user'				=> $team_user->to_form_input(),
	'team_team_id'			=> $uid,
	'allowed_html_tags' 	=> $allowed_html_tags,
	'accesslevels'			=> $team_user->accesslevels(),
	'form'					=> $form->values(),
	'form_key'				=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'allow_username_change' => $allow_username_change, 
	'lastupdate'			=> $lastupdate,
	'season'				=> null,
	'season_c'				=> null,
	'division'				=> $division,
	'wildcard'				=> $wildcard,
	'cookieconsent'			=> $cookieconsent,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/forms.js');
$cms->theme->add_js('js/editteam.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

// validator functions --------------------------------------------------------------------------

function email_match($var, $value, &$form) {
	global $valid, $cms, $ps;
	if (!empty($value)) {
		if ($value != $form->input['email2']) {
			$valid = false;
			$form->error($var, $cms->trans("Email addresses do not match"));
			$form->error('email2', $cms->trans("Email addresses do not match"));
		}
	}
	return $valid;
}

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
