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
		list($name) = $ps->db->fetch_list("SELECT name FROM $ps->t_team_ids_name WHERE team_id=$_id ORDER BY totaluses DESC LIMIT 1");
		$ps->db->insert($ps->t_team_profile, array( 'team_id' => $uid, 'name' => $name ));
		$team['team_id'] = $uid;
		$team['name'] = $name;
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
$form->field('team_name');	// 'team_name' is used instead of 'name' to avoid conflicts with some software (nuke)
$form->field('email');
$form->field('discord');
$form->field('twitch');
$form->field('youtube');
$form->field('socialclub');
$form->field('website');
$form->field('icon');
$form->field('cc');
$form->field('logo');
$form->field('namelocked');
if ($cms->user->is_admin()) {
	$form->field('username');
	$form->field('password');
	$form->field('password2');
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

	$input['name'] = $input['team_name'];
	unset($input['team_name']);

	// force a protocol prefix on the website url (http://)
	if (!empty($input['website']) and !preg_match('|^\w+://|', $input['website'])) {
		$input['website'] = "http://" . $input['website'];
	}

	// return error if website address does not exist or is unreachable
	if (!empty($input['website']) and !url_exists($input['website'])) {
        $form->error('website', $cms->trans("The web address is unreachable.") . " " .
            $cms->trans("Resubmit to try again.") 
			);
        $form->set('website', $website);
	}

	// return error if discord id is not an 18 digit number
	if (!empty($input['discord']) and !preg_match('|^[\d+]{17}$|', $input['discord'])) {
        $form->error('discord', $cms->trans("The Discord ID is not in the correct format.") . " " .
            $cms->trans("Resubmit to try again.") 
			);
        $form->set('discord', $discord);
	}

	// return error if twitch user name is not in correct format
	if (!empty($input['twitch']) and !preg_match('|^[a-zA-Z0-9][\w]{3,24}$|', $input['twitch'])) {
        $form->error('twitch', $cms->trans("Twitch user name not in correct format.") . " " .
            $cms->trans("Resubmit to try again.") 
			);
        $form->set('twitch', $twitch);
	}

	// return error if youtube user name is not in correct format
	if (!empty($input['youtube']) and !preg_match('|^[a-zA-Z0-9_-]{1,}$|', $input['youtube'])) {
        $form->error('youtube', $cms->trans("YouTube user name not in correct format.") . " " .
            $cms->trans("Resubmit to try again.") 
			);
        $form->set('youtube', $twitch);
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
			$form->set('logo', $logo);
		}
		$input['logo'] = $logo;
	}

	if ($cms->user->is_admin()) {
		if (!array_key_exists($input['accesslevel'], $cms->user->accesslevels())) {
			$form->error('accesslevel', $cms->trans("Invalid access level specified"));
		}
	}

	if (!$form->error('username') and $input['username'] != '') {
		// load the user matching the username
		$_u = $team_user->load_user($input['username'], 'username');
		// do not allow a duplicate username if another user has it already
		if ($_u and $_u['userid'] != $team_user->userid()) {
			$form->error('username', $cms->trans("Username already exists; please try another name"));
		}
		unset($_u);
	}

	// if a username is given we need to make sure a password was provided too (if there wasn't one already)
	if ($input['username'] != '') {
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
		$u['username'] = $input['username'];
		if ($input['password'] != '') $u['password'] = $team_user->hash($input['password']);
		$u['accesslevel'] = $input['accesslevel'];
		$u['confirmed'] = 1;
		unset($input['username']);
		unset($input['password']);
		unset($input['password2']);
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
			} else {
				$team_user->load($u['userid']);
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

		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage(psss_url_wrapper('teams.php'));
		}

	}

} else {
	// fill in defaults
	if ($id) {
		//$team['team_name'] = $team['name'];
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
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
//$cms->theme->add_js('js/jquery.interface.js');
$cms->theme->add_js('js/forms.js');
$cms->theme->add_js('js/teams.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
