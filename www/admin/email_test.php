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
 *	Version: 0.0.0	$
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");

$validfields = array('ref','cancel','submit');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

// Setup email variables.
$email = $ps->conf['main']['email']['admin_email'];
$subject = "PsychoStats Email Test";
// Setup the email page.
$template = 'test';
$email_page = $cms->return_email_page($template, 'email_header', 'email_footer');
// Setup the email headers.
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= 'From: '.$email."\r\n".
    'Reply-To: '.$email."\r\n" .
    'X-Mailer: PHP/' . phpversion();

$form = $cms->new_form();
$form->default_modifier('trim');

// process the form if submitted
$valid = true;
if ($submit) {
	$form->validate();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	if ($valid) {
		$ok = psss_send_mail($email, $subject, $email_page, $headers);

		if ($ok !== true) {
			$form->error('fatal', "The email test failed.  PHP and/or your email server is not working properly and/or not configured properly, or you have an error in your admin email address.  Please consult your web host.");
		} else {
			$message = $cms->message('success', array(
				'message_title'	=> $cms->trans("Test Email Successfully Sent!"), 
				'message'	=> $cms->trans("The test email has been sent successfully.  Please check the administration email account to see if the email was received."),
			));
		}
	}
}

$cms->crumb('Manage', psss_url_wrapper($_SERVER['REQUEST_URI']));
$cms->crumb('Reset Stats', psss_url_wrapper($php_scnm));

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

// Setup $email_enabled variable.
$email_enabled = ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email']));

// assign variables to the theme
$cms->theme->assign(array(
	'errors'	=> $form->errors(),
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'page'		=> basename(__FILE__, '.php'),
	'email_enabled'	=> $email_enabled,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/2column.css');
$cms->theme->add_css('css/forms.css');
//$cms->theme->add_js('js/jquery.interface.js');
$cms->theme->add_js('js/forms.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
