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
$cms->theme->page_title('Email Confirmation—PSSS');

// Page cannot be viewed if the site is in maintenance mode or the user is logged in.
if ($maintenance or $cms->user->logged_in()) previouspage('index.php');

$validfields = array('user','tpw');
$cms->theme->assign_request_vars($validfields, true);

// If you are on this page $cookieconsent is assumed to be true.
$cms->session->options['cookieconsent'] = true;
$cookieconsent = $cms->session->options['cookieconsent'];

$valid = true;

if ($valid) {
	$u = & $cms->new_user();

	// lookup the username if it doesn't exist go to index page.
	if (!$u->username_exists($user)) previouspage('index.php');
}

if ($valid) {
	$userinfo = $u->load_user($user, 'username');
	$userinfo = array_merge($userinfo, $ps->get_team_profile($userinfo['userid'], 'userid'));
	$id = $userinfo['userid'];

	// already confirmed, temp password and timestamp checks
	$ok = (!$userinfo['email_confirmed']);

	if (!$ok) {
		$message = $cms->trans("This user has already confirmed their email.");
	} else {
		$ok = ($userinfo['temp_password'] == $tpw); 
		if ($ok) {
			$ok = (time() - ($userinfo['tpw_timestamp']) < 172800);
			if (!$ok) $message = $cms->trans("The time to confirm this email has expired.  You can use the forgot password link on the login page to request another email confirmation link.");
		}
	}
	if ($ok) {
		$ps->db->begin();
		$ok = $ps->db->update($ps->t_user, 
			array( 'userid' => $userinfo['userid'], 'email_confirmed' => true), 
			'userid', $id
		);
		if (!$ok) $message = $cms->trans("Error updating user: " . $ps->db->errstr);
	}

	if ($ok and !isset($message)) {
		$ps->db->commit();

		// load this team
		$team = $ps->get_team_profile($userinfo['userid'], 'userid');

		// Setup the Admin CP link for the notification email.
		$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    	$base_url .= $_SERVER['HTTP_HOST'];  
    	$base_url .= $_SERVER['REQUEST_URI'];
		$base_url_array = explode('/', $base_url);
		array_pop($base_url_array);
		$base_url = implode('/', $base_url_array);
		$admin_users_url = $base_url . "/admin/users.php";

		// ensure user is logged out
		$cms->session->online_status(0, $userinfo['userid']);

		$cms->theme->assign(array(
			'team'			=> $team,
			'reg'			=> $userinfo,
			'season'		=> null,
			'season_c'		=> null,
			'division'		=> $division,
			'wildcard'		=> $wildcard,
			'form_key'		=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
			'cookieconsent'	=> $cookieconsent,
			'admin_users_url'	=> $admin_users_url,
		));

		// if registration requires confirmation and email notifications are enabled
		// send an email to the administrator email if the user isn't already confirmed
		if (!$userinfo['confirmed'] && $ps->conf['main']['registration'] == 'confirm' && $ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {

			// Setup email variables.
			$email = $ps->conf['main']['email']['admin_email'];
			$subject = "A New User Has Registered";
			$template = 'reg_notification';
			// Setup the email page.
			$email_page = $cms->return_email_page($template, 'email_header', 'email_footer');
			// Setup the email headers.
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: '.$email."\r\n".
    			'Reply-To: '.$email."\r\n" .
    			'X-Mailer: PHP/' . phpversion();

			psss_send_mail($email, $subject, $email_page, $headers);

		}
	
		// display the registration confirmation
		$basename = basename(__FILE__, '.php');
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
		'message_title'	=> $cms->trans("Email Confirmation Failed"),
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

?>
