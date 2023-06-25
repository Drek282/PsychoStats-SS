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
 *	Version: $Id: users.php 389 2008-04-18 15:04:10Z lifo $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");

$validfields = array('ref','start','limit','order','sort','filter','ec','c', 'sel', 'delete','confirm');
$cms->theme->assign_request_vars($validfields, true);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if (!is_numeric($start) or $start < 0) $start = 0;
if (!is_numeric($limit) or $limit < 0) $limit = 100;
if (!in_array($order, array('asc','desc'))) $order = 'asc';
if (!in_array($sort, array('username'))) $sort = 'username';
$ec = trim($ec ?? '');
if ($ec == '') $ec = -1;
$c = trim($c ?? '');
if ($c == '') $c = -1;

$_order = array(
	'start'		=> $start,
	'limit'		=> $limit,
	'order' 	=> $order, 
	'sort'		=> $sort,
	'username'	=> $filter,
	'email_confirmed'	=> $ec,
	'confirmed'	=> $c
);

if (($delete or $confirm) and is_array($sel) and count($sel)) {
	$total_processed = 0;
	foreach ($sel as $id) {
		// do not allow the current user to mess with their own account
		if (is_numeric($id) and $id != $cms->user->userid()) {
			if ($delete) {
				if ($cms->user->delete_user($id)) {
					$ps->db->update($ps->t_team_profile, array( 'userid' => null, 'email' => null, 'youtube' => null, 'website' => null, 'icon' => null, 'cc' => null, 'logo' => null ), 'userid', $id);
					$total_processed++;
				}
			} else { // confirm
				if ($cms->user->confirm_user(1, $id)) {

					// If email notifications are enabled notify the user that the account has been confirmed.
					if ($ps->conf['main']['email']['enable'] && !empty($ps->conf['main']['email']['admin_email'])) {

						// load this team profile
						$team = $ps->get_team_profile($id, 'userid');

						// Setup the site url for the notification email.
						$base_url = $ps->conf['main']['base_url'];
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

					$total_processed++;
				}
			}
		}
	}
	if ($delete) {
		$message = $cms->message('success', array(
			'message_title'	=> $cms->trans("Users Deleted!"),
			'message'	=> $cms->trans("%d users were deleted successfully", $total_processed),
		));
	} else {
		$message = $cms->message('success', array(
			'message_title'	=> $cms->trans("Users Confirmed!"),
			'message'	=> $cms->trans("%d users were confirmed successfully", $total_processed),
		));
	}
}

$uobj =& $cms->new_user();	// start a user object

// delete stale users
$users = $uobj->get_user_list(true, $_order);

foreach ($users as $u) {
	if (((time() - $u['tpw_timestamp']) > 172800) && (!$u['email_confirmed'] || !$u['confirmed'])) {
		$cms->user->delete_user($u['userid']);
		$ps->db->update($ps->t_team_profile, array( 'userid' => null, 'email' => null, 'youtube' => null, 'website' => null, 'icon' => null, 'cc' => null, 'logo' => null ), 'userid', $u['userid']);
	}
}
unset($users);

$users = $uobj->get_user_list(true, $_order);	// true = get associated team info too
$total = $uobj->total_users($_order);
$pager = pagination(array(
	'baseurl'	=> psss_url_wrapper(array('sort' => $sort, 'order' => $order, 'limit' => $limit, 'filter' => $filter, 'ec' => $ec, 'c' => $c)),
	'total'		=> $total,
	'start'		=> $start,
	'perpage'	=> $limit, 
	'pergroup'	=> 5,
	'separator'	=> ' ', 
	'force_prev_next' => true,
	'next'		=> $cms->trans("Next"),
	'prev'		=> $cms->trans("Previous"),
));

$cms->crumb('Manage', psss_url_wrapper(array('_base' => 'manage.php' )));
$cms->crumb('Users', psss_url_wrapper(array('_base' => $php_scnm )));
// assign variables to the theme
$cms->theme->assign(array(
	'page'		=> basename(__FILE__, '.php'), 
	'user'		=> $cms->user->to_form_input(),
	'users'		=> $users,
	'pager'		=> $pager,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/2column.css');
$cms->theme->add_css('css/forms.css');
$cms->theme->add_js('js/users.js');
$cms->theme->add_js('js/message.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

?>
