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

// Check to see if there is any data in the database before we continue.
$cmd = "SELECT * FROM $ps->t_team_adv LIMIT 1";

$results = array();
$results = $ps->db->fetch_rows(1, $cmd);

// if $results is empty then we have no data in the database
if (empty($results)) {
	$cms->full_page_err('awards', array(
		'message_title'	=> $cms->trans("No Teams in the Database"),
		'message'	=> $cms->trans("There must be teams in the database before anyone can register."),
		'lastupdate'	=> $ps->get_lastupdate(),
		'division'		=> null,
		'wildcard'		=> null,
		'season_c'		=> null,
		'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
		'cookieconsent'	=> $cookieconsent,
	));
	exit();
}
unset ($results);

# Are there divisions or wilcards in this league?
$division = $ps->get_total_divisions() - 1;
$wildcard = $ps->get_total_wc();
$lastupdate	= $ps->get_lastupdate();

$form->default_modifier('trim');
$form->field('team_id', 'blank');
$form->field('username', 'blank');
$form->field('name', 'blank');
$form->field('email', 'email,email_match');
$form->field('email2', 'email');
$form->field('password', 'blank,password_match');
$form->field('password2', 'blank');

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
		$team = $ps->get_team_profile($id, 'team_id');
		if (!$team) {
			$form->error('team_id', $cms->trans("The %s does not exist!", $team_id_label));
		} elseif ($team['userid']) {
			$form->error('team_id', $cms->trans("This team is already registered!"));
		} elseif ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email']) && $input['email'] == '') {
			$form->error('email', $cms->trans("An email address must be entered for new users"));
		}

		if ($u->username_exists($input['username'])) {
			$form->error('username', $cms->trans("Username already exists!"));
		}
	}

	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		$userinfo = $input;
		// email and name is saved to profile, not user
		unset($userinfo['team_id'], $userinfo['password2'], $userinfo['name'], $userinfo['email'], $userinfo['email2']);

		$userinfo['userid'] = $u->next_userid();
		$userinfo['password'] = $u->hash($userinfo['password']);
		$userinfo['temp_password'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? $u->hash(psss_generate_pw()) : null;
		$userinfo['tpw_timestamp'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? time() : 0;
		$userinfo['accesslevel'] = $u->acl_user();
		$userinfo['email_confirmed'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? 0 : 1;
		$userinfo['confirmed'] = $ps->conf['main']['registration'] == 'open' ? 1 : 0;

		$ps->db->begin();
		$ok = $u->insert_user($userinfo);
		if ($ok) {
			$ok = $ps->db->update($ps->t_team_profile, 
				array( 'userid' => $userinfo['userid'], 'name' => $input['name'] ? $input['name'] : null, 'email' => $input['email'] ? $input['email'] : null), 
				'team_id', $id
			);
			if (!$ok) $form->error('fatal', $cms->trans("Error updating team profile: " . $ps->db->errstr));
		} else {
			$form->error('fatal', $cms->trans("Error creating user: " . $u->db->errstr));
		}

		if ($ok and !$form->has_errors()) {
			$ps->db->commit();

			// load this team
			$team = $ps->get_team(array('team_id' 	=> $id,));

			$cms->theme->assign(array(
				'team'	=> $team,
				'reg'	=> $userinfo,
				'lastupdate'	=> $lastupdate,
				'season_c'		=> null,
				'division'		=> $division,
				'wildcard'		=> $wildcard,
				'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
				'cookieconsent'	=> $cookieconsent,
			));

			// send email confirmation notice if email notifictions are enabled
			if ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {

				// Setup the confirmation url.
				$base_url_array = explode('/', $_SERVER['HTTP_REFERER']);
				array_pop($base_url_array);
				$base_url = implode('/', $base_url_array);
				$confirmation_url = $base_url . "/email_confirmation.php?user=" . $userinfo['username'] . "&tpw=" . $userinfo['temp_password'];
				unset($base_url_array);

				$cms->theme->assign(array(
					'confirmation_url'	=> $confirmation_url,
				));

				// Setup email variables.
				$email = $input['email'];
				$from = $ps->conf['main']['email']['admin_email'];
				$subject = "Please confirm your email address";
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
	
			// display the registration confirmation
			$basename = basename(__FILE__, '.php') . '_confirmation';
			$cms->theme->add_css('css/forms.css');
			$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer');
			exit;
		} else {
			$ps->db->rollback();
		}
	}

}

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

// assign variables to the theme
$cms->theme->assign(array(
//	'team'		=> $ps->get_team(6375, true),
	'errors'	=> $form->errors(),
	'form'		=> $form->values(),
	'team_id_label' => $team_id_label,
	'lastupdate'	=> $lastupdate,
	'season_c'		=> null,
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
