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
 *	Version: 0.0.1  $
 */

/***
	CMS_functions.php
	Functions that can be overridden by plugins.

	All functions here can be overridden by a plugin. 
	Only the FIRST plugin to override a function will actually succeed. 
	These functions provide features that mainly deal with how users are authenticated
	and how sessions are maintained. Other utility methods are also available. 
	A plugin could override functions to authenticate against a 3rd party software 
	suite (ie: integrate PsychoStats into your forum database). But for more advanced
	overrides a plugin will have to override the base 'user' and 'session' classes too.

***/

if (!function_exists('psss_auto_login')) { 
	/** 
	Used to automatically authenticate a user who is not logged in 
	but has a saved login cookie in their browser. The password will
	already be hashed.
	*/
	function psss_auto_login($id, $password) {
		global $cms;
		list($userid,$acl,$confirmed) = $cms->db->fetch_row(0, 
			"SELECT userid,accesslevel,confirmed " . 
			"FROM " . $cms->db->table('user') . " " . 
			"WHERE userid=" . $cms->db->escape($id,true) . " AND password=" . $cms->db->escape($password,true));
		if ($acl < 1 or !$confirmed) $userid = 0;
		return $userid;
	}
}

if (!function_exists('psss_session_start')) {
	/** 
	Starts up the session for the current page request
	*/
	require_once(rtrim(dirname(__DIR__), '/\\') . "/class_session.php");
	function psss_session_start(&$cms) {
		global $ps;
		$time = $ps->conf['main']['security']['cookie_life'];
		// do not allow less than 60 seconds for cookies; or users may lock themselves out of the site.
		if (!is_numeric($time) or $time < 60) $time = 60;
		$cms->session = new PsychoSession(array(
			'cms'			=> &$cms,
			'dbhandle'		=> &$cms->db,
			'db_session_table'	=> $cms->db->table('sessions'),
			'db_user_table'		=> $cms->db->table('user'),
			'db_user_session_last'	=> 'session_last',
			'db_user_login_key'	=> 'session_login_key',
			'db_user_last_visit'	=> 'lastvisit',
			'login_callback_func'	=> 'psss_auto_login',
			'cookiename'		=> 'psss_sess',
			'cookiepath'		=> '/',
			'cookiedomain'		=> '', 
			'cookielife'		=> $time ? $time : 60*60,
			'cookiecompress'	=> $ps->conf['main']['security']['cookie_compress'],
			'cookieencode'		=> $ps->conf['main']['security']['cookie_encode']
		));
	}
}

if (!function_exists('psss_user_is_admin')) {
	/** 
	Returns true if the current user has admin privileges
	*/
	function psss_user_is_admin() {
		global $cms;
		return $cms->user->is_admin();
	}
}

if (!function_exists('psss_user_logged_in')) {
	/** 
	Returns true if the current user is logged in
	*/
	function psss_user_logged_in() {
		global $cms;
		return $cms->session->logged_in();
	}
}

if (!function_exists('psss_url_wrapper')) {
	/**
	All url's within a theme pass through this wrapper.
	$url is an array of key=>value pairs for parameters. 
	See the url() function for more details.
	*/
	function psss_url_wrapper($url = array()) {
		return url($url);
	}
}

if (!function_exists('psss_date')) {
	/** 
	Returns a formatted date using the timestamp given. 
	The date will be offset by the configuration setting $theme.format.time_offset;
	*/
	function psss_date($fmt, $epoch = null, $ignore_ofs = false) {
		global $ps;
		static $ofs = null;
		// calculate the offset once...
		if (is_null($ofs)) {
			$ofs = $ps->conf['theme']['format']['time_offset'];
			if ($ofs) {
				$sign = substr($ofs,0,1);
				$neg = (bool)($sign == '-');
				if ($neg || $sign == '+') $ofs = substr($ofs,1);
				if (str_contains($ofs, ':')) {
					list($h,$m) = explode(':', $ofs);
					$h = (int)$h;
					$m = (int)$m;
					$ofs = 60*60*$h + 60*$m;
				} else {
					$ofs = 60*60*$ofs;
				}
				if ($neg) $ofs *= -1;
			} else {
				$ofs = 0;	// make sure it's not null if time_offset is empty
			}
		}
		if (is_null($epoch)) $epoch = time();
		return date($fmt, $ignore_ofs ? $epoch : $epoch + $ofs);
	}
}

if (!function_exists('psss_datetime_stamp')) {
	/** 
	Used to return a quick date and time stamp. Used from certain theme routines.
	*/
	function psss_datetime_stamp($epoch, $fmt = null) {
		global $ps;
		if (!$fmt) $fmt = $ps->conf['theme']['format']['datetime'];
		if (empty($fmt)) $fmt = "Y-m-d H:i:s";
		return psss_date($fmt, $epoch);
	}
}

if (!function_exists('psss_date_stamp')) {
	/** 
	Used to return a quick date stamp. Used from certain theme routines.
	*/
	function psss_date_stamp($epoch, $fmt = null) {
		global $ps;
		if (!$fmt) $fmt = $ps->conf['theme']['format']['date'];
		if (empty($fmt)) $fmt = "Y-m-d";
		return psss_date($fmt, $epoch);
	}
}

if (!function_exists('psss_time_stamp')) {
	/** 
	Used to return a quick time stamp. Used from certain theme routines.
	*/
	function psss_time_stamp($epoch, $fmt = null) {
		global $ps;
		if (!$fmt) $fmt = $ps->conf['theme']['format']['time'];
		if (empty($fmt)) $fmt = "H:i:s";
		return psss_date($fmt, $epoch);
	}
}

if (!function_exists('psss_table_map_link')) {
	/**
	Called from the dynamic table class when creating a table that has a map <a> link.
	@param: $map contains stats for the current map. But mainly the $id is only needed.
	*/
	function psss_table_map_link($name, $map) {
		global $ps;
		$url = psss_url_wrapper(array( '_base' => 'map.php', 'id' => $map['mapid'] ));
		$img = $ps->mapimg($map, array( 'width' => 32, 'noimg' => '' ));
		return "<a class='map' href='$url'>$img</a>";
	}
}

if (!function_exists('psss_table_map_text_link')) {
	/**
	Called from the dynamic table class when creating a table that has a map <a> link.
	@param: $map contains stats for the current map. But mainly the $id is only needed.
	*/
	function psss_table_map_text_link($name, $map) {
		global $ps;
		$url = psss_url_wrapper(array( '_base' => 'map.php', 'id' => $map['mapid'] ));
//		$img = $ps->mapimg($map, array( 'width' => 32, 'height' => 24, 'noimg' => ''));
		return "<a class='map' href='$url'>" . psss_escape_html($name) . "</a>";
	}
}

if (!function_exists('psss_table_session_map_link')) {
	/**
	Called from the dynamic table class when creating a team session table that has a 
	map <a> link.
	@param: $team contains stats for the current team. But mainly the $name is only needed.
	*/
	function psss_table_session_map_link($name, $sess) {
		global $ps;
		$url = psss_url_wrapper(array( '_base' => 'map.php', 'id' => $sess['mapid'] ));
//		$img = $ps->mapimg($map, array( 'width' => 32, 'height' => 24, 'noimg' => ''));
		return "<a class='map' href='$url'>" . psss_escape_html($name) . "</a>";
	}
}

if (!function_exists('psss_table_session_time_link')) {
	/**
	Called from the dynamic table class when creating a team session table that has a timestamp
	@param: $sess contains stats for the current team session.
	*/
	function psss_table_session_time_link($time, $sess) {
		global $ps;
		$time = psss_date_stamp($sess['sessionstart']);
		$time .= " @ " . psss_time_stamp($sess['sessionstart'],'H:i') . " - " . psss_time_stamp($sess['sessionend'],'H:i');
		return $time;
	}
}

if (!function_exists('psss_table_team_link')) {
	/**
	Called from the dynamic table class when creating a table that has a team <a> link.
	@param: $team contains stats for the current team. But mainly the $id is only needed.
	*/
	function psss_table_team_link($name, $team, $inc_icon = true, $inc_flag = true) {
		global $ps;
		$team['team_id'] ??= null;
		$url = psss_url_wrapper(array( '_base' => 'team.php', 'id' => $team['team_id'] ));
		$team['icon'] ??= null;
		$icons = ($inc_icon and $ps->conf['theme']['permissions']['show_team_icons']) ? $ps->iconimg($team['icon']) . ' ' : '';
		$team['cc'] ??= null;
		$flags = ($inc_flag and $ps->conf['theme']['permissions']['show_team_flags']) ? $ps->flagimg($team['cc']) . ' ' : '';
		return "<a class='team' href='$url'>$flags$icons" . psss_escape_html($name) . "</a>";
	}
}

if (!function_exists('psss_table_team_roster_link')) {
	/**
	Called from the dynamic table class when creating a table that has a team <a> link.
	@param: $team contains stats for the current team. But mainly the $id is only needed.
	*/
	function psss_table_team_roster_link($season, $team) {
		global $ps;
		$team['team_id'] ??= null;
		$url = psss_url_wrapper(array( '_base' => 'roster.php', 'id' => $team['team_id'], 'season' => $season  ));
		return "<a class='team' href='$url'>" . $season . "</a>";
	}
}

if (!function_exists('psss_table_br_search_link')) {
	/**
	Called from the dynamic table class when creating a table that has a team <a> link.
	@param: $team contains stats for the current team. But mainly the $id is only needed.
	*/
	function psss_table_br_search_link($player_name) {
		global $ps;
		if (preg_match('/AAA/', $player_name)) return $player_name;
		$url = 'https://www.baseball-reference.com/search/search.fcgi?search=' . $player_name;
		return "<a class='team' href='$url' target='_blank' rel='noopener noreferrer'>" . $player_name . "</a>";
	}
}

if (!function_exists('psss_table_division_link')) {
	/**
	Called from the dynamic table class when creating a table that has a division <a> link.
	@param: $division contains stats for the current division. But mainly the $id is only needed.
	*/
	function psss_table_division_link($name, $division, $inc_icon = true) {
		global $ps;
		$division['divisionname'] ??= null;
		$url = psss_url_wrapper(array( '_base' => 'division.php', 'id' => $division['divisionname'] ));
		return "<a class='division' href='$url'>" . psss_escape_html($name != '' ? $name : '-') . "</a>";
	}
}

if (!function_exists('psss_table_search_link')) {
	/**
	Creates a search link from a query string.  Does not close the <a> tag.
	*/
	function psss_table_search_link($q) {
		global $ps;

		// setup query string for url
    	$qurl = strtolower($q);
    	$qurl = preg_replace("/[[:punct:]]+/", "", $qurl);
    	$qurl = preg_replace("/ +/", "+", $qurl);

		$url = psss_url_wrapper(array( '_base' => 'help.php')) . "?q=" . $qurl;
		return "<a class='division' href='$url'>";
	}
}

if (!function_exists('psss_escape_html')) {
	/**
	Escapes a string for output within the HTML themes. This should be used instead of 
	htmlentities() as that can mess up certain characters with UT8 encoded names.
	@param: $str contains the plain string to escape
	@param: $quote_style defines how to handle single and double quotes. Default is ENT_QUOTES
	which will escape both quotes.
	*/
	function psss_escape_html($str,$quote_style = ENT_QUOTES) {
		return htmlspecialchars($str ?? '', $quote_style, 'UTF-8');	// PHP >= 4.3.0
	}
}

if (!function_exists('psss_strip_tags')) {
	/** 
	Strips html tags from a string. Will also remove certain keywords like 'onmouseover', 'onclick', etc...
	By default the allowed_html_tags configuration option is used if $allowed is not specified.
	@param: $html contains the HTML string to strip
	@param: $allowed (optional) is a space separated list of tag names to NOT strip. By default 'allowed_html_tags' 
		configuration option is used. Set this to an empty string to not allow any tags.
	*/
	function psss_strip_tags($html, $allowed = null) {
		global $ps;
		if ($allowed == null) $allowed = $ps->conf['theme']['format']['allowed_html_tags'];
		if (!empty($allowed)) {
			$allowed = '<' . str_replace(',', '><', preg_replace('/\\s+/m', ',', $allowed)) . '>';
		} else {
			$allowed = '';
		}
		// repeat loop incase embedded tags are attempted (ie: <di<div>v>malicious</div>)
		// I'm not convinced this is needed ... but does not hurt at the moment.
		while ($html != strip_tags($html, $allowed)) {
			$html = strip_tags($html, $allowed);
		}
		return preg_replace_callback('/<(.*?)>/i', function($m) { return '<' . psss_strip_attribs($m[1]) . '>'; }, $html);
//		return preg_replace('/<(.*?)>/ie', "'<' . psss_strip_attribs('\\1') . '>'", $html);
	}
}

if (!function_exists('psss_strip_attribs')) {
	/**
	Disables harmful attributes from an HTML tag (eq: onclick, etc).
	See psss_strip_tags.
	@param $html is the text inside a tag w/o the angled brackets (ie: <...>)
	*/
	function psss_strip_attribs($html) {
		$attribs = 'javascript|on(?:dbl)?click|onmouse(?:click|over|out)|onkey(?:press|up|down)';
		$html = stripslashes(preg_replace("/([^\w](?:$attribs))(?!_disabled)/i", '\\1_disabled', $html));
		return $html;
	}
}

if (!function_exists('psss_user_can_edit_team')) {
	/**
	Returns true if the user can edit the team ID specified
	@param $team is either an array of team info or a numeric ID to check against
	@param $user (optional) specifies the user object to check against, uses the logged in user if null
	*/
	function psss_user_can_edit_team($team, $user = null) {
		global $cms, $ps;
		if ($user == null) $user =& $cms->user;
		if ($user->is_admin()) return true;
		if (!is_array($team)) {
			$team_id = $team;
			$team = $ps->get_team_profile($team_id);
		}

		return ($user->logged_in() and $team['userid'] == $user->userid());
	}
}

if (!function_exists('psss_user_can_edit_division')) {
	/**
	Returns true if the user can edit the division ID specified
	@param $divisionid is the divisionid to check against
	@param $team is either an array of team info (including divisionid) or a numeric team ID to check against
	@param $user (optional) specifies the user object to check against, uses the logged in user if null
	*/
	function psss_user_can_edit_division($divisionid, $team = null, $user = null) {
		global $cms, $ps;
		if ($user == null) $user =& $cms->user;
		if ($user->is_admin()) return true;
		if (is_array($divisionid)) {
			$divisionid = $divisionid['divisionid'];
		}
		if (!is_array($team)) {
			if ($team == null) $team = psss_user_team_id($user);
			$team_id = $team;
			$team = $ps->get_team_profile($team_id);
		}
		return ($user->logged_in() and $team['userid'] == $user->userid() and $team['divisionid'] == $divisionid);
	}
}

if (!function_exists('psss_user_team_id')) {
	/**
	Returns the team_id associated with the user provided.
	@param $user (optional) the user to match a team against, if no user is given the currently logged in user is used.
	*/
	function psss_user_team_id($user = null) {
		global $cms, $ps;
		if ($user == null) $user =& $cms->user;
		$team_id = $ps->db->fetch_item("SELECT p.team_id FROM $ps->t_team p, $ps->t_team_profile pp WHERE pp.team_id=p.team_id AND pp.userid=" . $ps->db->escape($user->userid(), true));
		return $team_id ? $team_id : 0;
	}
}

if (!function_exists('psss_ob_gzhandler')) {
	/**
	 * Output handler for all HTML based content. Default behavior will
	 * detect GZIP or DEFLATE support on the client and encode the HTML
	 * accordingly. A proper 'content-length' header is also sent to allow
	 * for persistent HTTP connections (PHP's built in ob_gzhandler does
	 * not send a content-length).
	 */
	function psss_ob_gzhandler($buffer, $flags) {
		// don't do anything if the buffer isn't being closed or if
		// headers were already sent.
		if (($flags & PHP_OUTPUT_HANDLER_END != PHP_OUTPUT_HANDLER_END) || headers_sent() || empty($buffer)) {
			return false;
		}

		$zipped = '';
		$original_length = strlen($buffer);
		$encoding = false;
		// build an array of accepted encodings
		$accept = (array)explode(",", str_replace(" ", "", strtolower($_SERVER['HTTP_ACCEPT_ENCODING'])));
		if (in_array("gzip", $accept)) {		// GZIP
			$zipped = gzencode($buffer);
			$encoding = 'gzip';
		} elseif (in_array('deflate', $accept)) {	// DEFLATE
			$zipped = gzcompress($buffer);
			$encoding = 'deflate';
		} else {
			$zipped =& $buffer;
		}
		$length = strlen($zipped);
		
		// don't send compressed output if the zipped length is
		// greater than the original (only occurs on small output)
		if ($length > $original_length) {
			$zipped = false;
			$length = strlen($buffer);
		}
		
		// provide content-length to allow HTTP persistent connections.
		// PHP's built in ob_gzhandler does not send this header
		header("Content-Length: " . $length, true);
		
		if ($zipped) {
			header("Vary: Accept-Encoding", true); 	// handle proxies
			header("Content-Encoding: " . $encoding, true);
			// add an information value showing how much compression we actually achieved.
			header("X-Compression: " . sprintf("%d/%d (%.02f%%)", $length, $original_length, abs($length / $original_length * 100 - 100)), true);
			return $zipped;
		} else {
			return $buffer;
		}
	}
}

if (!function_exists('psss_ob_handler')) {
	/**
	 * Output handler for all HTML based content. No compression.
	 * A proper 'content-length' header is also sent to allow
	 * for persistent HTTP connections.
	 */
	function psss_ob_handler($buffer, $flags) {
		// don't do anything if the buffer isn't being closed or if
		// headers were already sent.
		if (($flags & PHP_OUTPUT_HANDLER_END != PHP_OUTPUT_HANDLER_END) || headers_sent() || empty($buffer)) {
			return false;
		}

		// provide content-length to allow HTTP persistent connections.
		// PHP's built in ob_gzhandler does not send this header
		header("Content-Length: " . strlen($buffer), true);
		header("Vary: Accept-Encoding", true); 	// handle proxies

		return false; //$buffer;
	}
}

if (!function_exists('psss_send_mail')) { 
	/** 
	 * Used to send email notifications using the PHP mail function.  Return true on success, false on failure.
	 */
	function psss_send_mail($email, $subject, $email_page, $headers) {
		$ok = mail($email, $subject, $email_page, $headers);
		return $ok;
	}
}

if (!function_exists('psss_generate_pw')) {
	/**
	 * Generate a random string, using a cryptographically secure 
	 * pseudorandom number generator (random_int)
	 * 
	 * @param int $length      How many characters do we want?
	 * @param string $keyspace A string of all possible characters
	 *                         to select from
	 * @return string
	 */
	function psss_generate_pw(
		$length = 24,
		$keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	)
	{
		$pw = '';
		$max = mb_strlen($keyspace, '8bit') - 1;
		if ($max < 1) {
			throw new Exception('$keyspace must be at least two characters long');
		}
		for ($i = 0; $i < $length; ++$i) {
			$pw .= $keyspace[random_int(0, $max)];
		}
		return $pw;
	}
}

if (!function_exists('psss_debug')) {
	/**
	 * DEBUG handler. Outputs as much debugging information possible
	 * somewhere within the current theme output. 
	 */
	function psss_debug(&$buffer) {
		global $cms, $ps;
		$str = '';
		
		// output all queries sent
		$str .= "<ul class='ps-debug'>\n";
		foreach ($ps->db->queries as $q) {
			$str .= "<li>" . psss_escape_html($q) . "</li>\n";
		}
		$str .= "</ul>\n";
		
		// output any errors that occured
		if ($ps->db->errors) {
			$str .= "<ul class='ps-debug'>\n";
			foreach ($ps->db->errors as $e) {
				$str .= "\t<li><span class='error'>" . $e['error'] . "</span>\n\t<span class='query'>" . $e['query'] . "</span></li>\n";
			}
			$str .= "</ul>\n";
		}
		$buffer = str_replace('</body>', $str . '</body>', $buffer);
	}
}

?>
