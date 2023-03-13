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
 *	Version: $Id: users_edit.php 389 2008-04-18 15:04:10Z lifo $
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
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'users.php' )));
}

// load the matching user if an ID was given
$u =& $cms->new_user();
if (is_numeric($id)) {
	if (!$u->load($id)) {
		$data = array( 'message' => $cms->trans("Invalid User ID Specified") );
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();		
	}
} elseif (!empty($id)) {
	$data = array( 'message' => $cms->trans("Invalid User ID Specified") );
	$cms->full_page_err(basename(__FILE__, '.php'), $data);
	exit();		
} else {
	# set defaults for new user
	$u->info['username'] ??= '';
}

// delete it, if asked to
if ($del and $id and $u->userid() == $id) {
	// Count the number of admins in the database.
	$admin_count = $ps->db->count($ps->t_user, 'userid', 'accesslevel=99');
	// If this is the last admin, unlink the team from the user account and set the team profile to defaults.
	if ($admin_count == 1 and $u->is_admin()) {
		$ps->db->update($ps->t_team_profile, array( 'userid' => null, 'email' => null, 'youtube' => null, 'website' => null, 'icon' => null, 'cc' => null, 'logo' => null ), 'userid', $id);
		previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'users.php' )));
	}
	if (!$u->delete_user($id)) {
		$data = array( 'message' => $cms->trans("Error deleting user: " . $u->dberr()) );
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();
	}
	$ps->db->update($ps->t_team_profile, array( 'userid' => null, 'email' => null, 'youtube' => null, 'website' => null, 'icon' => null, 'cc' => null, 'logo' => null ), 'userid', $id);
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'users.php' )));
}

// Set a variable for current confirmed status.
$u->info['confirmed'] ??= true;
$start_conf_status = $u->info['confirmed'];

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$u->init_form($form);
$form->field('username', 'blank');
$form->field('owner_name', 'blank');
$form->field('team_id');
$form->field('email', 'email');
$form->field('email2', 'email');
$form->field('password');
$form->field('password2');
$form->field('accesslevel');
$form->field('confirmed');

// process the form if submitted
$valid = true;
if ($submit) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();
	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));

	if ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {
		// verify the email addresses match if one was specified
		if (!$id and $input['email'] == '') {
			$form->error('email', $cms->trans("An email address must be entered for new users"));
		} elseif ($input['email'] != '') {
			if ($input['email'] != $input['email2']) {
				$form->error('email', $cms->trans("The email addresses do not match.  Please try again."));
				$form->error('email2', '');
			}
		} else {
			unset($input['email']);
		}
		unset($input['email2']);
	}

	// verify the passwords match if one was specified
	if (!$id and $input['password'] == '') {
		$form->error('password', $cms->trans("A password must be entered for new users"));
	} elseif ($input['password'] != '') {
		if ($input['password'] != $input['password2']) {
			$form->error('password', $cms->trans("Passwords do not match; please try again."));
			$form->error('password2', ' ');
		} else {
			$input['password'] = $u->hash($input['password']);
		}
	} else {
		unset($input['password']);
	}
	unset($input['password2']);

	if (!array_key_exists($input['accesslevel'], $u->accesslevels())) {
		$form->error('accesslevel', $cms->trans("Invalid access level specified"));
	}

	if (empty($u->info['username']) and $u->load_user($input['username'], 'username')) {
		$form->error('username', $cms->trans("Username already exists under a different user"));
	}
	
	$valid = ($valid and !$form->has_errors());
	while ($valid) {
		$ok = false;
		// setup user record
		$team_id = $input['team_id'];
		$email = $input['email'] ?? null;
		$owner_name = $input['owner_name'];
		unset($input['team_id']);
		unset($input['email']);
		unset($input['email2']);
		unset($input['owner_name']);

		if ($id) {
			// check that $team_id and owner name match
			if (!empty($team_id) and !empty($owner_name)) {
				$cmd = "SELECT owner_name, MAX(lastseen) FROM $ps->t_team_ids_names ";
				$cmd .= "WHERE team_id = '$team_id' AND team_name = ''";
				$arry = $ps->db->fetch_row(1, $cmd);
				if (isset($arry['owner_name'])) {
					$on_check = $arry['owner_name'];
					unset($arry);

					if (isset($on_check)) {
						$match = "/" . $on_check . "/";
						if (!preg_match($match, $owner_name)) {
							$form->error('owner_name', $cms->trans("Name does not match owner for Team #."));
							$form->set('owner_name', $on_check);
							$form->set('team_id', $team_id);
							break;
						}
						unset($on_check);
					}
				} else {
					$form->error('team_id', $cms->trans("Invalid Team # provided."));
				}
			}
			$ok = $u->update_user($input, $id);
			if ($ok) {
				$ok = $ps->db->update($ps->t_team_profile, 
					array( 'userid' => $id, 'email' => empty($email) ? $u->info['email'] : $email), 
					'team_id', $team_id
					);
				$cmd  = "UPDATE $ps->t_team_ids_names SET owner_name = '$owner_name' ";
				$cmd .= "WHERE team_id='' AND owner_name='" . $u->info['owner_name'] . "'";
				if ($ok) $ok = $ps->db->query($cmd);
				if (!$ok) {
					$form->error('fatal', $cms->trans("Error updating team profile: " . $ps->db->errstr));
				}
			}
			if (!empty($u->info['email']) && $start_conf_status == false && $ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {

				// load this team profile
				$team = $ps->get_team_profile($id, 'userid');

				// Setup the site url for the notification email.
				$base_url_array = explode('/', $_SERVER['HTTP_REFERER']);
				array_pop($base_url_array);
				array_pop($base_url_array);
				$base_url = implode('/', $base_url_array);
				$login_url = $base_url . "/login.php";

				$cms->theme->assign(array(
					'team'	=> $team,
					'login_url'	=> $login_url,
				));

				// Setup email variables.
				$email = $team['email'];
				$site_email = $ps->conf['main']['email']['admin_email'];

				if ($site_name = $ps->conf['main']['site_name']) {
					$subject = $cms->trans("Your PsychoStats Account Has Been Confirmed for") . " " . $site_name;
				} else {
					$subject = $cms->trans("Your PsychoStats Account Has Been Confirmed");
				}
				
				$template = 'user_confirmation';
				// Setup the email page.
				$email_page = $cms->return_email_page($template, 'email_header', 'email_footer');
				// Setup the email headers.
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= 'From: '.$site_email."\r\n".
    				'Reply-To: '.$site_email."\r\n" .
    				'X-Mailer: PHP/' . phpversion();

				psss_send_mail($email, $subject, $email_page, $headers);

			}
		} else {
			// load team profile that matches team_id
			$team = $ps->get_team_profile($team_id);

			// if there is no matching team_id return form error
			if (empty($team)) {
				$form->error('team_id', $cms->trans("Invalid Team # provided."));
				break;
			}

			// can only complete an owner name, not change it
			$match = "/" . $team['owner_name'] . "/";
			if (!empty($owner_name) and !preg_match($match, $owner_name)) {
				$form->error('owner_name', $cms->trans("Owner name does not match owner for Team # ") . $team_id);
        		$form->set('owner_name', $team['owner_name']);
				break;
			}

			if ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {
				$input['temp_password'] = $u->hash(psss_generate_pw());
				$input['email_confirmed'] = 0;
				$input['tpw_timestamp'] = time();
			} else {
				$input['temp_password'] = null;
				$input['email_confirmed'] = 1;
				$input['tpw_timestamp'] = 0;
			}
			$input['userid'] = $u->next_userid();
			// if user is being added in Admin CP, confirmed status is assumed
			$input['confirmed'] = true;

			$ok = $u->insert_user($input);
			if ($ok) {
				$ok = $ps->db->update(
					$ps->t_team_profile,
					array('userid' => $input['userid'], 'email' => empty($email) ? null : $email),
					'team_id',
					$team_id
				);

				// send email confirmation notice if email notifictions are enabled
				if ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {

					// Setup the confirmation url.
					$base_url_array = explode('/', $_SERVER['HTTP_REFERER']);
					array_pop($base_url_array);
					array_pop($base_url_array);
					$base_url = implode('/', $base_url_array);
					$setpw_url = $base_url . "/pwreset_final.php?username=" . $input['username'] . "&tpw=" . $input['temp_password'];
					unset($base_url_array);

					$cms->theme->assign(array(
						'setpw_url'	=> $setpw_url,
					));

					// Setup email variables.
					$from = $ps->conf['main']['email']['admin_email'];
					$subject = "Please choose a password";
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
			}
		}
		if (!$ok) {
			$form->error('fatal', "Error updating database: " . $ps->db->errstr);
		} else {
			previouspage('users.php');
		}
		break;
	}

} else {
	// fill in defaults
	if ($id) {
		$form->input($u->to_form_input());
	} else {
		$form->set('accesslevel', $u->acl_user());
		$form->set('confirmed', true);
	}
}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('Users', psss_url_wrapper('users.php'));
$cms->crumb('Edit');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form

$email = (isset($email)) ? $email : $email = (isset($u->info['email'])) ? $u->info['email'] : null;
$cms->theme->assign(array(
	'errors'	=> $form ? $form->errors() : array('fatal' => null,),
	'u'		=> $u->to_form_input(),
	'email'			=> $email,
	'accesslevels'	=> $u->accesslevels(),
	'form'		=> $form->values(),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
//$cms->theme->add_js('js/jquery.interface.js');
$cms->theme->add_js('js/forms.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
