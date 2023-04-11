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
 *	Version: $Id: go-admin.php 476 2008-06-04 00:28:42Z lifo $
 */

/*
	Create Admin user(s)
*/
if (!defined("PSYCHOSTATS_INSTALL_PAGE")) die("Unauthorized access to " . basename(__FILE__));

$validfields = array('base_url','username','password','password2','del');
$cms->theme->assign_request_vars($validfields, true);

// make DB connection
load_db_opts();
$db->config(array(
	'dbtype' => $dbtype,
	'dbhost' => $dbhost,
	'dbport' => $dbport,
	'dbname' => $dbname,
	'dbuser' => $dbuser,
	'dbpass' => $dbpass,
	'dbtblprefix' => $dbtblprefix
));
$db->clear_errors();
$db->connect();

if (!$db->connected || !$db->dbexists($db->dbname)) {
	if ($ajax_request) {
		print "<script>window.location = 'go.php?s=db&re=1&install=" . urlencode($install) . "';</script>";
		exit;
	} else {
		gotopage("go.php?s=db&re=1&install=" . urlencode($install));
	}
}


// now that the DB connection should be valid, reinitialize, so we'll have full access to user and session objects
$cms->init();

$errors = array();
$filter = array('accesslevel' => $cms->user->acl_admin());
$action = "created";
$admin_list = array();

$cms->theme->assign_by_ref('errors', $errors);
$cms->theme->assign_by_ref('action', $action);
$cms->theme->assign_by_ref('admin_list', $admin_list);

// Try to auto create the $base_url variable.
if (isset($_SERVER['HTTP_REFERER']) && empty($base_url)) {
	$base_url_array = explode('/', $_SERVER['HTTP_REFERER']);
	array_pop($base_url_array);
	array_pop($base_url_array);
	$base_url = implode('/', $base_url_array);
	unset($base_url_array);
}

// delete the specified admin 
if ($ajax_request and $del != '') {
	$action = "deleted";
	if (!$cms->user->delete_user($del, 'username')) {
		$errors['fatal'] = "Error deleting admin '$del': " . $cms->user->db->errstr;
	}
}

// load current admin list
$admin_list = load_admins();
$allow_next = ( $cms->user->total_users($filter) > 0 );

$cms->theme->assign(array(
	'deleted'	=> $del,
));

if ($ajax_request) {
//	sleep(1);

	if (!$del) {
		$base_url = trim($base_url);
		$username = trim($username);
		$password = trim($password);
		$password2 = trim($password2);

		if (!empty($base_url) and url_exists($base_url) != true) {
			$errors['base_url'] = 'The URL is not accessible!  Please verify the url.';
		}

		if ($username == '') {
			$errors['username'] = "Please enter a valid username!";
		} elseif ($cms->user->username_exists($username)) {
			$errors['username'] = "Username already exists!";
		}

		if ($password == '') {
			$errors['password'] = "Please enter a password!";
		} elseif ($password != $password2) {
			$errors['password2'] = "Password mismatch, Please try again!";
		}

		if (!$errors) {
			$set = array(
				'userid'	=> $cms->user->next_userid(),
				'accesslevel'	=> $cms->user->acl_admin(),
				'username'	=> $username,
				'password'	=> $cms->user->hash($password),
				'temp_password'	=> null,
				'tpw_timestamp'	=> 0,
				'lastvisit'	=> time(),
				'session_last'	=> time(),
				'email_confirmed'	=> 1,
				'confirmed'	=> 1
			);
			if (!$cms->db->update($cms->db->table('config'), "`value` = '" . $base_url . "'" , 'var', 'base_url')) {
				$errors['fatal'] = "Error updating base URL in database.";
			}
			if (!$cms->user->insert_user($set)) {
				if ($errors['fatal']) {
					$errors['fatal'] = "<br>Error creating user: " . $cms->user->db->errstr;
				} else {
					$errors['fatal'] = "Error creating user: " . $cms->user->db->errstr;
				}
			} else {
				$admin_list = load_admins();
				$allow_next = true;
			}
		}

	}
    
    $errors['fatal'] ??= null;
    $errors['base_url'] ??= null;
    $errors['username'] ??= null;
    $errors['password'] ??= null;
    $errors['password2'] ??= null;
	$pagename = 'go-admin-results';
	$cms->tiny_page($pagename, $pagename);
	exit();
}

function load_admins() {
	global $cms, $filter;
	$list = $cms->user->get_user_list(false, $filter);
	$admin_list = array();
	foreach ($list as $u) {
		$admin_list[] = "<a href='javascript:void(0)'>" . psss_escape_html($u['username']) . "</a>";
	}
	return $admin_list ? join(', ',$admin_list) : '';
}

?>
