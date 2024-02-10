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
 *	Version: $Id: 0.0.0 $
 */

define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/common.php");
$cms->init_theme($ps->conf['main']['theme'], $ps->conf['theme']);
$ps->theme_setup($cms->theme);
$cms->theme->page_title('PsychoStats - Reset Password');

// Is PsychoStats in maintenance mode?
$maintenance = $ps->conf['main']['maintenance_mode']['enable'];

// Page cannot be viewed if the site is in maintenance mode.
if ($maintenance) previouspage('index.php');

$validfields = array('submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

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
		'oscript'		=> $oscript,
		'maintenance'	=> $maintenance,
		'message_title'	=> $cms->trans("No Teams in the Database"),
		'message'	=> $cms->trans("If there are no teams in the database, there are no users."),
		'lastupdate'	=> $ps->get_lastupdate(),
		'division'		=> null,
		'wildcard'		=> null,
		'season'		=> null,
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
$form->field('email', 'email,email_match');
$form->field('email2', 'email');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

if ($submit) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	$u =& $cms->new_user();

	$email = $input['email'];
	$team = array();
	// lookup the worldid/team_id ... 
	if ($input['email'] != '') {
		$team = $ps->get_team_profile($email, 'email');
		if (!$team) {
			$form->error('email', $cms->trans("There is no team associated with this email address!"));
		}
	}

	$valid = ($valid and !$form->has_errors());
	if ($valid) {
		$userinfo = $input;
		// email and name is saved to profile, not user
		unset($userinfo['email'], $userinfo['email2']);

		$id = $team['userid'];
		$userinfo['temp_password'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? $u->hash(psss_generate_pw()) : null;
		$userinfo['tpw_timestamp'] = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) ? time() : 0;

		$ps->db->begin();
		$ok = $u->update_user($userinfo, $id);

		if ($ok and !$form->has_errors()) {
			$ps->db->commit();

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
				$base_url = $ps->conf['main']['base_url'];
				$setpw_url = $base_url . "/pwreset_final.php?userid=" . $id . "&tpw=" . $userinfo['temp_password'];

				$cms->theme->assign(array(
					'setpw_url'	=> $setpw_url,
				));

				// Setup email variables.
				$email = $input['email'];
				$from = $ps->conf['main']['email']['admin_email'];
				$subject = "Please reset your password";
				$template = 'pwreset';
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

// assign variables to the theme
$cms->theme->assign(array(
	'oscript'		=> $oscript,
	'maintenance'	=> $maintenance,
	'errors'		=> $form->errors(),
	'form'			=> $form->values(),
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

?>
