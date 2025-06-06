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
 *	Version: $Id: PS.php 568 2008-10-16 18:38:19Z lifo $
 *	
 *	PsychoStats base class
 *
 *	Depends: class_DB.php
 *	Optional Depends: class_HTTP.php
 *
 *      PsychoStats class. This is a self contained API class for PsychoStats.
 *      It can be included almost anywhere to fetch stats from a PsychoStats
 *      database. The API is simple and does not require the user to know how
 *      stats are stored in the database. No other libraries (except the DB
 *      class) are needed.
 *	
 *      Sub-classes will override this base class to provide some extra
 *      functionality based on game::mod.
 *      
 *	Example:
 *		include("class_PS.php");
 *		$dbconf = array( ... DB settings ... );
 *		$ps = PsychoStats::create($dbconf);
 *
 *		$top100 = $ps->get_team_list(array( ... params ... ));
 *		print_r($top100);
 *
 *		$divisions = $ps->get_division_list(array( ... params ... ));
 *		print_r($divisions);
 *		
 * @package PsychoStats
 * 
 */

if (defined("CLASS_PS_PHP")) return 1;
define("CLASS_PS_PHP", 1);

#[AllowDynamicProperties]
class PS {

/**
 *	The *_TYPES arrays are used for calculating division statistics. 
 *	Each array generally matches the same array found in the back-end perl 
 *	arrays in lib/PS/Team.pm
 */

var $DIVISION_TYPES = array( 
	'games_played'				=> '=',
	'wins'						=> '=',
	'losses'					=> '=',
	'win_percent'				=> '=',
	'team_rdiff'				=> '=',
	'losses'					=> '=',
	'win_percent'				=> '=',
	'team_rdiff'				=> '=',
	'team_ra'					=> '=',
	'run_support'				=> '=',
	'team_whip'					=> '=',
	'ops'						=> '=',
	'team_drat'					=> '=',
	'team_srat'					=> '=',
);

var $WC_TYPES = array( 
	'team_id'					=> '=',
	'team_name'					=> '=',
	'games_played'				=> '=',
	'wins'						=> '=',
	'losses'					=> '=',
	'win_percent'				=> '=',
	'games_back_wc'				=> '=',
	'team_rdiff'				=> '=',
	'pythag'					=> '=',
	'pythag_plus'				=> '=',
);

var $ADV_TYPES = array( 
	'team_id'					=> '=',
	'team_name'					=> '=',
	'divisionname'				=> '=',
	'games_played'				=> '=',
	'wins'						=> '=',
	'losses'					=> '=',
	'win_percent'				=> '=',
	'games_back'				=> '=',
	'team_rdiff'				=> '=',
	'pythag'					=> '=',
	'pythag_plus'				=> '=',
);

var $DEF_TYPES = array( 
	'team_id'					=> '=',
	'team_name'					=> '=',
	'team_era'					=> '=', 
	'shutouts'					=> '=',
	'team_saves'				=> '=',
	'innings_pitched'			=> '=',
	'total_runs_against'		=> '=',
	'total_earned_runs_against'	=> '=',
	'hits_surrendered'			=> '=', 
	'opp_batting_average'		=> '=',
	'opp_walks'					=> '=',
	'team_whip'					=> '=',
	'opp_strikeouts'			=> '=', 
	'outstanding_plays'			=> '=', 
	'double_plays_turned'		=> '=',
	'fielding_errors'			=> '=', 
	'team_wild_pitches'			=> '=',
	'passed_balls'				=> '=',
	'opp_stolen_bases'			=> '=',
	'opp_caught_stealing'		=> '=', 
	'team_drat'					=> '=', 
);

var $OFF_TYPES = array( 
	'team_id'					=> '=',
	'team_name'					=> '=',
	'run_support'				=> '=',
	'at_bats'					=> '=',
	'runs'						=> '=',
	'hits'						=> '=',
	'doubles'					=> '=',
	'triples'					=> '=',
	'home_runs'					=> '=', 
	'team_rbis'					=> '=',
	'walks'						=> '=',
	'strikeouts'				=> '=',
	'batting_average'			=> '=',
	'on_base_average'			=> '=',
	'slugging_average'			=> '=',
	'woba'						=> '=',
	'ops'						=> '=',
	'sacrifice_hits'			=> '=', 
	'sacrifice_fails'			=> '=',
	'sacrifice_flies'			=> '=',
	'gidps'						=> '=', 
	'stolen_bases'				=> '=',
	'caught_stealing'			=> '=', 
	'left_on_base'				=> '=',
	'left_on_base_percent'		=> '=',
	'team_srat'					=> '=', 
);

var $db = null;
var $tblprefix = '';

var $explained = array();
var $conf = array();
var $conf_layout = array();

var $class = 'PS';

function __construct(&$db) {
	$this->db =& $db;
	$this->tblprefix = $this->db->dbtblprefix;

	// normal tables ...
	$this->t_awards			= $this->tblprefix . 'awards';
	$this->t_awards_teams		= $this->tblprefix . 'awards_teams';
	$this->t_config 		= $this->tblprefix . 'config';
	$this->t_config_awards 		= $this->tblprefix . 'config_awards';
	$this->t_config_help 		= $this->tblprefix . 'config_help';
	$this->t_config_sources 	= $this->tblprefix . 'config_sources';
	$this->t_config_themes 		= $this->tblprefix . 'config_themes';
	$this->t_errlog 		= $this->tblprefix . 'errlog';
	$this->t_team_adv 			= $this->tblprefix . 'team_adv';
	$this->t_team_def 			= $this->tblprefix . 'team_def';
	$this->t_team_off 			= $this->tblprefix . 'team_off';
	$this->t_team_wc 			= $this->tblprefix . 'team_wc';
	$this->t_team 			= $this->tblprefix . 'team';
	$this->t_team_aliases 		= $this->tblprefix . 'team_aliases';
	$this->t_team_rpi 		= $this->tblprefix . 'team_rpi';
	$this->t_team_rpo 		= $this->tblprefix . 'team_rpo';
	$this->t_team_ids_names 		= $this->tblprefix . 'team_ids_names';
	$this->t_team_ids_team_id 	= $this->tblprefix . 'team_adv';
	$this->t_team_profile 		= $this->tblprefix . 'team_profile';
	$this->t_plugins 		= $this->tblprefix . 'plugins';
	$this->t_search_results		= $this->tblprefix . 'search_results';
	$this->t_sessions 		= $this->tblprefix . 'sessions';
	$this->t_state 			= $this->tblprefix . 'state';
	$this->t_user 			= $this->tblprefix . 'user';
	$this->t_seasons_h 			= $this->tblprefix . 'seasons_h';

	// load our main config ...
	$this->load_config(array('main','theme','info'));

	$this->tblsuffix = '_' . $this->conf['main']['gametype'] . '_' . $this->conf['main']['modtype'];
} // constructor

function PS(&$db) {
    self::__construct($db);
}

/*
    * function init_search
    * Generates a new unique search string (to be used with search_teams())
    *
    * @return  string  A new unique search ID.
*/
function init_search() {
	$id = md5(uniqid(rand(), true));	
	return $id;
}

/*
    * function search_teams
    * Performs a search on the DB for teams matching the criteria specified.
    * 
    * @param  string  $search_id  The search ID to use for this search.
    * @param  string/array  $criteria  Array of options allows to change
    * the criteria very specifically. A string will be used as the text to
    * search for.
    * 
    * @return integer Total matches found.
*/
function search_teams($search_id, $criteria) {
	global $cms;
	$team_ids = array();
	
	// convert criteria string to an array
	if (!is_array($criteria)) {
		$criteria = array( 'phrase' => $criteria );
	}

	// assign criteria defaults
	$criteria += array(
		'season'	=> null,
		'phrase'	=> null,
		'mode'		=> 'contains', 	// 'contains', 'begins', 'ends', 'exact'
		'status'	=> '',		// empty, 'ranked', 'unranked'
	);
	$season = $criteria['season'];
	// 'limit' is forced based on current configuration
	$criteria['limit'] = coalesce($this->conf['main']['security']['search_limit'], 1000);
	if (!$criteria['limit']) $criteria['limit'] = 1000;

	// do not allow blank phrases to be searched
	$criteria['phrase'] = trim($criteria['phrase']);
	if (is_null($criteria['phrase']) or $criteria['phrase'] == '') {
		return false;
	}

	// sanitize 'mode'
	$criteria['mode'] = strtolower($criteria['mode']);
	if (!in_array($criteria['mode'], array('contains', 'begins', 'ends', 'exact'))) {
		$criteria['mode'] = 'contains';
	}

	// sanitize 'status'
	$criteria['status'] = strtolower($criteria['status']);
	if (!in_array($criteria['status'], array('ranked', 'unranked'))) {
		$criteria['status'] = '';
	}

	// tokenize our search phrase
	$tokens = array();
	if ($criteria['mode'] == 'exact') {
		$tokens = array( $criteria['phrase'] );
	} else {
		$tokens = query_to_tokens($criteria['phrase']);
	}

	// build our WHERE clause
	$where = "";
	$inner = array();
	$outer = array();
	
	// loop through each field and add it to the 'where' clause.
	// Search team, profile and ids
	foreach (array('adv.team_id', 'adv.divisionname', 'names.team_name', 'names.owner_name', 'prof.email') as $field) {
		foreach ($tokens as $t) {
			$token = $this->token_to_sql($t, $criteria['mode']);
            $inner[] = "$field LIKE '$token'";
		}
		if ($inner) {
			$outer[] = $inner;
		}
		$inner = array();
	}

	// combine the outer and inner clauses into a where clause
	foreach ($outer as $in) {
		$where .= " (" . join(" AND ", $in) . ") OR ";
	}
	$where = substr($where, 0, -4);		// remove the trailing " OR "

	// perform search and find Jimmy Hoffa!
	// NOTE: SQL_CALC_FOUND_ROWS is MYSQL specific and would need to be
	// changed for other databases.
	$cmd  = "SELECT SQL_CALC_FOUND_ROWS DISTINCT adv.team_id " .
		"FROM $this->t_team_ids_names names, $this->t_team_adv adv, $this->t_team_profile prof " .
		"WHERE adv.season=$season AND adv.team_id=names.team_id AND adv.team_id=prof.team_id ";
	
	$cmd .= "AND ($where) ";
	$cmd .= "LIMIT " . $criteria['limit'];

	$team_ids = $this->db->fetch_list($cmd);
	$total = $this->db->fetch_item("SELECT FOUND_ROWS()");

	// delete any searches that are more than a few hours old
	$this->delete_stale_searches();

	// psss_search_results record for insertion
	$search = array(
		'search_id'		=> $search_id,
		'session_id'	=> $cms->session->sid(),
		'season'		=> $season,
		'phrase'		=> $criteria['phrase'],
		'result_total'	=> count($team_ids),
		'abs_total'		=> $total,
		'results'		=> join(',', $team_ids),
		'query'			=> $cmd,
		'updated'		=> date('Y-m-d H:i:s'),
		
	);
	$ok = $this->save_search($search);
	
	return $ok ? count($team_ids) : false;
}

/*
    * function search_help
    * Performs a search on the DB for teams matching the criteria specified.
    * 
    * @param  string  $search_id  The search ID to use for this search.
    * @param  string/array  $criteria  Array of options allows to change
    * the criteria very specifically. A string will be used as the text to
    * search for.
    * 
    * @return integer Total matches found.
*/
function search_help($search_id, $criteria) {
	global $cms;

	// delete any searches that are more than a few hours old
	$this->delete_stale_searches();
	
	// convert criteria string to an array
	if (!is_array($criteria)) {
		$criteria = array( 'phrase' => $criteria );
	}

	// assign criteria defaults
	$criteria += array(
		'phrase'	=> null,
		'mode'		=> 'contains', 	// 'contains', 'begins', 'ends', 'exact'
	);
	// 'limit' is forced based on current configuration
	$criteria['limit'] = coalesce($this->conf['main']['security']['search_limit'], 1000);
	if (!$criteria['limit']) $criteria['limit'] = 1000;

	// do not allow blank phrases to be searched
	$criteria['phrase'] = trim($criteria['phrase']);
	if (is_null($criteria['phrase']) or $criteria['phrase'] == '') {
		return false;
	}

	// sanitize 'mode'
	$criteria['mode'] = strtolower($criteria['mode']);
	if (!in_array($criteria['mode'], array('contains', 'begins', 'ends', 'exact'))) {
		$criteria['mode'] = 'contains';
	}

	// sanitize 'phrase'
    $criteria['phrase'] = strtolower($criteria['phrase']);
    $criteria['phrase'] = preg_replace("/[[:punct:]]+/", "", $criteria['phrase']);
    $criteria['phrase'] = str_replace(" +", " ", $criteria['phrase']);

	// tokenize our search phrase
	$tokens = array();
	if ($criteria['mode'] == 'exact') {
		$tokens = array( $criteria['phrase'] );
	} else {
		$tokens = query_to_tokens($criteria['phrase']);
	}

	/*
		first search title for exact matches
	*/
	
	// build our WHERE clause
	$where = "";
	$where = "title='" . $tokens[0] . "'";
	foreach (array_slice($tokens, 1) as $t) {
		$where .= " OR title='$t'";
	}

	// perform search and find Jimmy Hoffa!
	$cmd  = "SELECT *,IF(weight > 10, 20, IF(weight < -10, 0, (weight + 10))) w FROM $this->t_config_help ";
	
	$cmd .= "WHERE ($where) ";
	$cmd .= "LIMIT " . $criteria['limit'];
	$stitle = $this->db->fetch_rows(1, $cmd);

	/*
		now do the $help_ids search
	*/

	// build our WHERE clause
	$where = "";
	$inner = array();
	$outer = array();
	
	// loop through each field and add it to the 'where' clause.
	foreach (array('title', 'content') as $field) {
		foreach ($tokens as $t) {
			// don't include one or two letter tokens in $help_ids search
			if (strlen($t) < 3) continue;
			$token = $this->token_to_sql($t, $criteria['mode']);
            $inner[] = "$field LIKE '$token'";
		}
		if ($inner) {
			$outer[] = $inner;
		}
		$inner = array();
	}

	if (empty($outer)) {
		$help_ids = $this->db->fetch_list($cmd);
	} else {

		// combine the outer and inner clauses into a where clause
		foreach ($outer as $in) {
			$where .= " (" . join(" OR ", $in) . ") OR ";
		}
		$where = substr($where, 0, -4);		// remove the trailing " OR "

		// perform search and find Jimmy Hoffa!
		$cmd  = "SELECT * FROM $this->t_config_help ";
		$cmd .= "WHERE ($where) ";
		$cmd .= "LIMIT " . $criteria['limit'];
		$help_ids = $this->db->fetch_list($cmd);
	}

	/*
		now do a 'LIKE' search on title and contents
	*/

	// setup the  $help array and reindex $stitle
	$help = array();
	foreach ($stitle as $st) {
    	$help[$st['id']] = $st;
	}
	unset($stitle);

	// iterate through $tokens and do a search on each token while building the $help array
	foreach ($tokens as $t) {
		// don't include one or two letter tokens in $help_ids search
		if (strlen($t) < 3) continue;

		// greater weight to tokens that are 5+ characters in length
		if (strlen($t) > 4) {
			$cmd  = "SELECT *,IF(weight > 10, 12, IF(weight < -10, -8, (weight + 2))) w FROM $this->t_config_help ";
			$cmd .= "WHERE ( CONCAT_WS('', title,content) LIKE '%$t%' ) ";
			$cmd .= "LIMIT " . $criteria['limit'];
			$slike = $this->db->fetch_rows(1, $cmd);
			
			foreach ($slike as $s) {
    			$id = $s['id'];
    			if (isset($help[$id])) {
					$help[$id]['w'] = $s['w'] + $help[$id]['w'];
				} else {
					$help[$s['id']] = $s;
				}
			}
		} else {
			$cmd  = "SELECT *,IF(weight > 10, 10, IF(weight < -10, -10, weight)) w FROM $this->t_config_help ";
			$cmd .= "WHERE ( CONCAT_WS('', title,content) LIKE '%$t%' ) ";
			$cmd .= "LIMIT " . $criteria['limit'];
			$slike = $this->db->fetch_rows(1, $cmd);
			foreach ($slike as $s) {
    			$id = $s['id'];
    			if (isset($help[$id])) {
					$help[$id]['w'] = $s['w'] + $help[$id]['w'];
				} else {
					$help[$s['id']] = $s;
				}
			}
		
		}

		unset($slike);
	}

	// psss_search_results record for insertion
	$search = array(
		'search_id'	=> $search_id,
		'session_id'	=> $cms->session->sid(),
		'phrase'	=> $criteria['phrase'],
		'result_total'	=> count($help_ids),
		'abs_total'	=> count($help),
		'results'	=> join(',', $help_ids),
		'query'		=> $cmd,
		'updated'	=> date('Y-m-d H:i:s'),
		
	);
	$ok = $this->save_search($search);
	
	return $ok ? array('count' => count($help), 'help' => $help) : false;
}

/*
    * function save_search
    * Saves the results of a search done with search_teams
    * 
    * @param  array  $search  Search paramters to save
    * 
    * @return string  Returns true if the search was saved, false otherwise.
*/
function save_search($search) {
	return $this->db->insert($this->t_search_results, $search);
}

/*
    * function get_lastupdate
    * Returns the time of the last update
*/
function get_lastupdate() {
	$cmd = "SELECT lastupdate FROM $this->t_state LIMIT 1";

	$lastupdate = $this->db->fetch_row(1, $cmd);
	if (is_array($lastupdate)) $lastupdate = implode($lastupdate);
	return $lastupdate;
}

/*
    * function get_season_c
    * Returns the current season and updates it.
*/
function get_season_c() {
	// Get $season_c from $this->t_state.
	$cmd = "SELECT season_c FROM $this->t_state LIMIT 1";
	$season_c = $this->db->fetch_row(1, $cmd);
	if (is_array($season_c)) $season_c = implode($season_c);
	$season_c ??= null;

	// Check to see that data exists for $season_c.
	$cmd = "SELECT season FROM $this->t_team_adv ORDER BY season DESC LIMIT 1";
	$check = $this->db->fetch_row(1, $cmd);
	if (is_array($check)) $check = implode($check);

	// If $season_c and $check don't match, update $this->t_state.
	if ($check and $check != $season_c) {
		$season_c = $check;
		$this->db->query("UPDATE $this->t_state SET season_c=$season_c");
	}
	
	return $season_c ?? '1900';
}

/*
    * function get_seasons_h
    * Returns the list of historical seasons
*/
function get_seasons_h() {
	$cmd = "SELECT season_h FROM $this->t_seasons_h";

	$seasons_h = $this->db->fetch_rows(1, $cmd);
	foreach ($seasons_h as $v => $val) {
		$seasons_h[$v] = implode($seasons_h[$v]);
	}

	$seasons_h = array_reverse($seasons_h);

	return $seasons_h;
}

/*
    * function get_search
    * Returns a saved search result
    * 
    * @param  string  $search  Search paramters to save
    * 
    * @return string  Returns true if the search was saved, false otherwise.
*/
function get_search($search) {
	if ($this->is_search($search)) {
		return $this->db->fetch_row(1, "SELECT * FROM $this->t_search_results WHERE search_id=" . $this->db->escape($search, true));
	}
	return array();
}

/*
    * function is_search
    * Determines if the search id given is an active search
    * 
    * @param  string  $search  Search ID string to validate.
    * 
    * @return boolean Returns true if the search is valid.
*/
function is_search($search) {
	if (!$search) return false;
	return $this->db->exists($this->t_search_results, 'search_id', $search);
	
}

/*
    * function delete_search
    * Deletes the search results assoicated with the search ID given.
    * 
    * @param  string  $search  Search ID to delete
    * 
    * @return boolean  True if successful
*/
function delete_search($search) {
	if ($this->is_search($search)) {
		return $this->db->delete($this->t_search_results, 'search_id', $search);
	}
	return false;
}

/*
    * function delete_stale_searches
    * Deletes stale searches more than a few hours old
    * 
    * @param  integer  $hours  Maximum hours allowed to be stale (Optional)
    * 
    * @return void
*/
function delete_stale_searches($hours = 4) {
	if (!is_numeric($hours) or $hours < 0) $hours = 4;
	$this->db->query("DELETE FROM $this->t_search_results WHERE updated < NOW() - INTERVAL $hours HOUR");
}

/*
    * function token_to_sql
    * Converts the token string into a SQL string based on the $mode given.
    * 
    * @param  string  $str  The token string
    * @param  string  $mode Token mode (contains, begins, ends, exact)
    * 
    * @return string  Returns the string ready to be used in a SQL statement.
*/
function token_to_sql($str, $mode) {
	$token = $this->db->escape($str);
	switch ($mode) {
		case 'begins': 	return $token . '%'; break;
		case 'ends': 	return '%' . $token; break;
		case 'exact': 	return $token; break;
		case 'contains':
		default:	return '%' . $token . '%'; break;
	}
}

// load a team's profile only. does not load any extra statistics.
// if a team_id doesn't have a matching profile then nulls are returned for each column except team_id.
// @param $key is 'team_id'
function get_team_profile($team_id, $key = 'team_id') {
	$team = array();
	$cmd = "SELECT adv.*,prof.* FROM ";
	if ($key == 'team_id') {
		$cmd .= "$this->t_team_adv adv LEFT JOIN $this->t_team_profile prof USING(team_id) WHERE adv.team_id=";
	} else {
		$_key = $this->db->quote_identifier($key);
		$cmd .= "$this->t_team_profile prof LEFT JOIN $this->t_team_adv adv USING(team_id) WHERE prof.$_key=";
	}
	$cmd .= $this->db->escape($team_id, true);

	$team = $this->db->fetch_row(1, $cmd);

	if (!empty($team)) {
		$team['owner_name'] = implode($this->db->fetch_list("SELECT owner_name FROM $this->t_team_ids_names WHERE team_id=" . $team['team_id'] . " AND team_name='' ORDER BY lastseen DESC LIMIT 1"));
	
		$team['team_name'] = implode($this->db->fetch_list("SELECT team_name FROM $this->t_team_ids_names WHERE team_id=" . $team['team_id'] . " AND owner_name='' ORDER BY lastseen DESC LIMIT 1"));
	}

	return $team ? $team : false;
}

// $args can be a team ID, or an array of arguments
function get_team($args = array(), $minimal = false) {
	$args += array(
		'season_c'		=> null,
		'team_id'		=> 0,
		'minimal'	=> false, // if true, overrides all 'load...' options to false (or use $minimal parameter)
		'loadadvanced'	=> 1,
		'loaddefence'	=> 1,
		'loadoffence'	=> 1,
		'loaddivision'	=> 1,
		//'loadawards'	=> 0,			// no awards by default
		'loadnames'	=> 1,
		'loadcounts'	=> 1,
		'loadteam_ids'	=> 1,
		'advancedsort'	=> 'season',
		'advancedorder'	=> 'desc',
		'advancedstart'	=> 0,
		'advancedlimit'	=> 10,
		'defencesort'	=> 'season',
		'defenceorder'	=> 'desc',
		'defencestart'	=> 0,
		'defencelimit'	=> 10,
		'offencesort'	=> 'season',
		'offenceorder'	=> 'desc',
		'offencestart'	=> 0,
		'offencelimit'	=> 10,
		'idsort'	=> 'lastseen',
		'idorder'	=> 'desc',
		'idstart'	=> 0,
		'idlimit'	=> 10,
	);
	$team = array();
	$id = $args['team_id'];
	if (!is_numeric($id)) $id = 0;

	if ($minimal) $args['minimal'] = true;
	$season_c = $args['season_c'];

	// Load overall team information
	$cmd  = "SELECT wc.*,names.*,team.*,adv.*,def.*,off.*,prof.* FROM ($this->t_team_ids_names names, $this->t_team team, $this->t_team_adv adv, $this->t_team_def def, $this->t_team_off off, $this->t_team_profile prof) ";
	$cmd .= "LEFT JOIN $this->t_team_wc wc ON wc.team_id=team.team_id ";
	$cmd .= "WHERE team.team_id='$id' AND team.team_id=adv.team_id AND team.team_id=def.team_id AND team.team_id=off.team_id AND team.team_id=prof.team_id ";
	$cmd .= "AND adv.season='$season_c' AND def.season='$season_c' AND off.season='$season_c' ";
	$cmd .= "LIMIT 1 ";

	$team = $this->db->fetch_row(1, $cmd);

	// Load team division information
	if (!$args['minimal'] and $args['loaddivision'] and isset($team['divisionname'])) {
		$cmd  = "SELECT adv.* FROM $this->t_team_adv adv ";
		$cmd .= "WHERE divisionname='" . $this->db->escape($team['divisionname']) . "' ";
		$cmd .= "LIMIT 1";
		$team['division'] = $this->db->fetch_row(1, $cmd);
		$team['division']['totalmembers'] = $this->db->count($this->t_team_adv, '*', "divisionname='" . $this->db->escape($team['divisionname']) . "'");
	} else {
		$team['division'] = array();
	}

	if (!$args['minimal'] and $args['loadcounts']) {
		$team['totaladvanced'] 	= $this->db->count($this->t_team_adv, '*', "team_id='$id'");
		$team['totaldefence'] 	= $this->db->count($this->t_team_def, '*', "team_id='$id'");
		$team['totaloffence'] 	= $this->db->count($this->t_team_off, '*', "team_id='$id'");
		//$team['totalawards'] 	= $this->db->count($this->t_awards, '*', "topteam_id='$id'");
	}

	// Load advanced stats for the team.
	if (!$args['minimal'] and $args['loaddefence']) {
		$cmd  = "SELECT adv.* FROM $this->t_team_adv adv ";
		$cmd .= "WHERE adv.team_id='$id' ";
		$cmd .= $this->getsortorder($args, 'advanced');
		$team['advanced'] = $this->db->fetch_rows(1, $cmd);
	}

	// Load defensive stats for the team.
	if (!$args['minimal'] and $args['loaddefence']) {
		$cmd  = "SELECT def.* FROM $this->t_team_def def ";
		$cmd .= "WHERE def.team_id='$id' ";
		$cmd .= $this->getsortorder($args, 'defence');
		$team['defence'] = $this->db->fetch_rows(1, $cmd);
	}

	// Load offensive stats for the team.
	if (!$args['minimal'] and $args['loadoffence']) {
		$cmd  = "SELECT off.* FROM $this->t_team_off off ";
		$cmd .= "WHERE off.team_id='$id' ";
		$cmd .= $this->getsortorder($args, 'offence');
		$team['offence'] = $this->db->fetch_rows(1, $cmd);
	}

	// Fix the team_id
	$team['team_id'] = isset($team['advanced'][0]['team_id']) ? $team['advanced'][0]['team_id'] : 0;

	// Get historical team average win percentage.
	$team['hist_wp'] = 0;
	$count = 0;
	foreach ($team['advanced'] as $s => $val) {
		$team['hist_wp'] = $team['hist_wp'] + $team['advanced'][$s]['win_percent'];
		$count++;
	}
	$team['hist_wp'] = (!$count == 0) ? round($team['hist_wp'] / $count, 3) : 0;

	// Get historical team average runs against.
	$team['hist_ra'] = 0;
	$count = 0;
	foreach ($team['defence'] as $s => $val) {
		$team['hist_ra'] = $team['hist_ra'] + $team['defence'][$s]['team_ra'];
		$count++;
	}
	$team['hist_ra'] = (!$count == 0) ? round($team['hist_ra'] / $count, 2) : 0;

	// Get historical team average runs support.
	$team['hist_rs'] = 0;
	$count = 0;
	foreach ($team['offence'] as $s => $val) {
		$team['hist_rs'] = $team['hist_rs'] + $team['offence'][$s]['run_support'];
		$count++;
	}
	$team['hist_rs'] = (!$count == 0) ? round($team['hist_rs'] / $count, 1) : 0;

	// Get historical team average run differential.
	$team['hist_rdiff'] = 0;
	$count = 0;
	foreach ($team['advanced'] as $s => $val) {
		$team['hist_rdiff'] = $team['hist_rdiff'] + $team['advanced'][$s]['team_rdiff'];
		$count++;
	}
	$team['hist_rdiff'] = (!$count == 0) ? round($team['hist_rdiff'] / $count, 2) : 0;

	// Get historical team average pythag+.
	$team['hist_pythag_plus'] = 0;
	$count = 0;
	foreach ($team['advanced'] as $s => $val) {
		$team['hist_pythag_plus'] = $team['hist_pythag_plus'] + $team['advanced'][$s]['pythag_plus'];
		$count++;
	}
	$team['hist_pythag_plus'] = (!$count == 0) ? round($team['hist_pythag_plus'] / $count, 3) : 0;

	// Count the number of division titles.
	$count = 0;
	foreach ($team['advanced'] as $s => $val) {
		if (($team['advanced'][$s]['games_back'] == 'dt') or ($team['advanced'][$s]['games_back'] == 'dtlc')) $count++;
	}
	$team['div_ts'] = $count;
	
	// Count the number of league championships.
	$count = 0;
	foreach ($team['advanced'] as $s => $val) {
		if (($team['advanced'][$s]['games_back'] == 'lc') or ($team['advanced'][$s]['games_back'] == 'dtlc')) $count++;
	}
	$team['league_cs'] = $count;

	// Get playoff status.
	$team['games_back'] = isset($team['games_played']) && isset($team['games_back']) ? $this->get_playoff_status($season_c, $team['games_played'], $team['games_back']) : 'na';
	$team['games_back_wc'] = isset($team['games_played']) && isset($team['games_back_wc']) ? $this->get_playoff_status($season_c, $team['games_played'], $team['games_back_wc']) : 'na';

	// Load team names.
	if (!$args['minimal']) {
		$loadlist = array();
		if ($args['loadnames']) $loadlist[] = 'team_name';
		if ($loadlist) {
			foreach ($loadlist as $v) {
				$tbl = $this->{'t_team_ids_names'};
				$cmd  = "SELECT $v,lastseen FROM $tbl WHERE team_id='$id' AND owner_name='' ";
				$cmd .= $this->getsortorder($args, 'id');
				$team['ids_' . $v] = $this->db->fetch_rows(1, $cmd);
				$team[$v] = isset($team['ids_' . $v][0][$v]) ? $team['ids_' . $v][0][$v] : 'na';
#				print "<pre>"; print_r($team['ids_'.$v]); print "</pre>";
			}
		}
	}

	// Load owner names.
	if (!$args['minimal']) {
		$loadlist = array();
		if ($args['loadnames']) $loadlist[] = 'owner_name';
		if ($loadlist) {
			foreach ($loadlist as $v) {
				$tbl = $this->{'t_team_ids_names'};
				$cmd  = "SELECT $v,lastseen FROM $tbl WHERE team_id='$id' AND team_name='' ";
				$cmd .= $this->getsortorder($args, 'id');
				$team['ids_' . $v] = $this->db->fetch_rows(1, $cmd);
				$team[$v] = isset($team['ids_' . $v][0][$v]) ? $team['ids_' . $v][0][$v] : 'na';
#				print "<pre>"; print_r($team['ids_'.$v]); print "</pre>";
			}
		}
	}
	
	return $team;
}

function not0($a) { return ($a != '0.0.0.0'); }

function get_team_awards($args = array()) {
	$args += array(
		'team_id' 	=> 0,
		'sort'		=> 'awardname',
		'order'		=> 'asc',
	);
	$cmd  = "SELECT ap.team_id,a.awardname,ap.value,a.awarddate FROM $this->t_awards_teams ap, $this->t_awards a ";
	$cmd .= "WHERE a.id=ap.awardid AND ap.team_id='" . $this->db->escape($args['team_id']) . "'";
	$cmd .= $this->getsortorder($args);
	$list = array();
	$list = $this->db->fetch_rows(1, $cmd);
	return $list;
}


function get_division($args = array(), $minimal = false) {
	if (!is_array($args)) {
		$id = $args;
		$args = array( 'divisionname' => $id );
	}
	$args += array(
		'season'		=> null,
		'divisionname'	=> '',
		'minimal'	=> false, // if true, overrides all 'load...' options to false (or use $minimal parameter)
		'fields'	=> '',
		'allowall'	=> 0,
		'loadadvanced'	=> 1,
		'loaddefence'	=> 1,
		'loadoffence'	=> 1,
		'loadcounts'	=> 1,
		'asort'	=> 'win_percent, team_rdiff',
		'aorder'	=> 'desc',
		'astart'	=> 0,
		'alimit'	=> 10,
		'dsort'	=> 'team_ra, team_era',
		'dorder'	=> 'asc',
		'dstart'	=> 0,
		'dlimit'	=> 10,
		'osort'	=> 'run_support, woba',
		'oorder'	=> 'desc',
		'ostart'	=> 0,
		'olimit'	=> 10,
		'afields'	=> '',
		'dfields'	=> '',
		'ofields'	=> '',
	);
	$season = $args['season'];

	# Get season length.
	$season_l = $this->get_season_length($season);

	$division = array();
	$id = $this->db->escape($args['divisionname']);
	#if (!is_numeric($id)) $id = 0;

	if ($minimal) $args['minimal'] = true;

	$values = "names.*, adv.*,adv.team_id team_n, ";

	$types = $this->get_types('DIVISION');
	$fields = !empty($args['fields']) ? explode(',',$args['fields']) : array_keys($types);
	$values .= $this->_values($fields, $types);

	$cmd  = "SELECT $values ";
	$cmd .= "FROM $this->t_team_adv adv, $this->t_team_ids_names names, $this->t_team_def def, $this->t_team_off off ";
	$cmd .= "WHERE adv.divisionname='" . $id . "' AND names.team_id=adv.team_id AND adv.season=$season ";
	$args['where'] ??= null;
	if (trim($args['where'] ?? '') != '') $cmd .= "AND (" . $args['where'] . ") ";
	$cmd .= "GROUP BY adv.divisionname ";
	$cmd .= $this->getsortorder($args);

	$division = $this->db->fetch_row(1, $cmd);

	// Load advanced stats for the division.
	if (!$args['minimal'] and $args['loadadvanced']) {
        $s ??= null;
		$division['advanced'] = $this->get_team_list(array(
			'where' => "AND adv.divisionname='$id'",
			'season'	=> $season,
			'sort'	=> $args['asort'],
			'order' => $args['aorder'],
			'start' => $args['astart'],
			'limit' => $args['alimit'],
			'fields'=> $args['afields'],
//			'allowall' => 1,
			'allowall' => $args['allowall'],
		),$s);
	}

	// Load defensive stats for the division.
	if (!$args['minimal'] and $args['loaddefence']) {
        $s ??= null;
		$division['defence'] = $this->get_team_list(array(
			'where' => "AND adv.divisionname='$id'",
			'season'	=> $season,
			'sort'	=> $args['dsort'],
			'order' => $args['dorder'],
			'start' => $args['dstart'],
			'limit' => $args['dlimit'],
			'fields'=> $args['dfields'],
//			'allowall' => 1,
			'allowall' => $args['allowall'],
		),$s);
	}

	// Load offensive stats for the team.
	if (!$args['minimal'] and $args['loadoffence']) {
        $s ??= null;
		$division['offence'] = $this->get_team_list(array(
			'where' => "AND adv.divisionname='$id'",
			'season'	=> $season,
			'sort'	=> $args['osort'],
			'order' => $args['oorder'],
			'start' => $args['ostart'],
			'limit' => $args['olimit'],
			'fields'=> $args['ofields'],
//			'allowall' => 1,
			'allowall' => $args['allowall'],
		),$s);
	}

	// Get totalmembers.
	$division['totalmembers'] = count($division['advanced']);

	// Generate games_played and games_remaining
	$division['advanced'][0]['games_played'] ??= 0;
	$division['advanced'][0]['games_remaining'] = $season_l - $division['advanced'][0]['games_played'];

	// Get playoff status and stat totals.
	$clinch_count = ($division['advanced'][0]['games_remaining'] != 0) ? 0 : null;
	$division['games_played'] = 0;
	$division['wins'] = 0;
	$division['losses'] = 0;
	$division['win_percent'] = 0;
	$division['team_rdiff'] = 0;
	$division['team_ra'] = 0;
	$division['run_support'] = 0;
	$division['team_whip'] = 0;
	$division['ops'] = 0;
	$division['team_drat'] = 0;
	$division['team_srat'] = 0;
	foreach ($division['advanced'] as $tm => $val) {
		// Declare undeclared array keys
		$division['advanced'][$tm]['wins'] ??= 0;
		$division['advanced'][$tm]['losses'] ??= 0;
		$division['advanced'][$tm]['win_percent'] ??= 0;
		$division['advanced'][$tm]['team_rdiff'] ??= 0;
		$division['defence'][$tm]['team_ra'] ??= 0;
		$division['offence'][$tm]['run_support'] ??= 0;
		$division['defence'][$tm]['team_whip'] ??= 0;
		$division['offence'][$tm]['ops'] ??= 0;
		$division['defence'][$tm]['team_drat'] ??= 0;
		$division['offence'][$tm]['team_srat'] ??= 0;
		// Playoff status.
		if (!is_null($clinch_count)) {
			$division['advanced'][$tm]['games_back'] = $this->get_playoff_status($season, $division['advanced'][$tm]['games_played'], $division['advanced'][$tm]['games_back']);
			if ($division['advanced'][$tm]['games_back'] == 'elim') $clinch_count++;
		}
		// Stat totals.
		$division['games_played'] = $division['games_played'] + $division['advanced'][$tm]['games_played'];
		$division['wins'] = $division['wins'] + $division['advanced'][$tm]['wins'];
		$division['losses'] = $division['losses'] + $division['advanced'][$tm]['losses'];
		$division['win_percent'] = $division['win_percent'] + $division['advanced'][$tm]['win_percent'];
		$division['team_rdiff'] = $division['team_rdiff'] + $division['advanced'][$tm]['team_rdiff'];
		$division['team_ra'] = $division['team_ra'] + $division['defence'][$tm]['team_ra'];
		$division['run_support'] = $division['run_support'] + $division['offence'][$tm]['run_support'];
		$division['team_whip'] = $division['team_whip'] + $division['defence'][$tm]['team_whip'];
		$division['ops'] = $division['ops'] + $division['offence'][$tm]['ops'];
		$division['team_drat'] = $division['team_drat'] + $division['defence'][$tm]['team_drat'];
		$division['team_srat'] = $division['team_srat'] + $division['offence'][$tm]['team_srat'];
	}

	// Set clinched status.
	if (!is_null($clinch_count) and ($division['totalmembers'] - $clinch_count) == 1) $division['advanced'][0]['games_back'] = 'clinch';

	$division['win_percent'] = round($division['win_percent'] / $division['totalmembers'], 3);
	$division['team_rdiff'] = round($division['team_rdiff'] / $division['totalmembers'], 2);
	$division['team_ra'] = round($division['team_ra'] / $division['totalmembers'], 2);
	$division['run_support'] = round($division['run_support'] / $division['totalmembers'], 1);
	$division['team_whip'] = round($division['team_whip'] / $division['totalmembers'], 2);
	$division['ops'] = round($division['ops'] / $division['totalmembers'], 3);
	$division['team_drat'] = round($division['team_drat'] / $division['totalmembers'], 2);
	$division['team_srat'] = round($division['team_srat'] / $division['totalmembers'], 2);

	// unset a bunch of unnecessary crap
	unset($division['team_id']);
	unset($division['team_name']);
	unset($division['firstseen']);
	unset($division['lastseen']);
	unset($division['games_back']);
	unset($division['pythag']);
	unset($division['pythag_plus']);
	unset($division['team_n']);

	return $division;
}

// Returns an array of team profiles that are members of the division specified, regardless of rank.
function get_division_members($divisionname) {
	$cmd = "SELECT p.*,pp.* FROM $this->t_team_adv p, $this->t_team_profile pp WHERE pp.team_id=p.team_id AND p.divisionname=" . 
		$this->db->escape($divisionname, true) . " ORDER BY name ASC";
	$list = $this->db->fetch_rows(1, $cmd);
	return $list;
}

// Returns an array of teams with the number of division titles or league championships they have won.
function get_tc_count($type, $limit) {

	$values = "name.*,adv.season,adv.team_id team_n,adv.divisionname,adv.games_back";

	$cmd  = "SELECT $values FROM $this->t_team_adv adv ";
	$cmd .= "JOIN (SELECT DISTINCT team_name,team_id,lastseen FROM $this->t_team_ids_names JOIN (SELECT MAX(lastseen) max_ls FROM $this->t_team_ids_names) n ON n.max_ls = lastseen WHERE team_name <> '' GROUP BY team_id) name ON adv.team_id = name.team_id ";
	$cmd .= "HAVING games_back='$type' OR games_back='dtlc' ";
	$cmd .= "ORDER BY adv.season DESC ";

	$results = array();
	$results = $this->db->fetch_rows(1, $cmd);
	
	// Count the number of division titles.
	foreach ($results as $r => $v1) {
		$titles[$results[$r]['team_id']]['count'] ??= 0;
		if (!isset($titles[$results[$r]['team_id']]['lastseason'])) $titles[$results[$r]['team_id']]['lastseason'] = $results[$r]['season'];
		if (!isset($titles[$results[$r]['team_id']]['team_id'])) $titles[$results[$r]['team_id']]['team_id'] = $results[$r]['team_id'];
		if (!isset($titles[$results[$r]['team_id']]['team_name'])) $titles[$results[$r]['team_id']]['team_name'] = psss_table_team_link($results[$r]['team_name'], $titles[$results[$r]['team_id']]);
		if (!isset($titles[$results[$r]['team_id']]['divisionname'])) $titles[$results[$r]['team_id']]['divisionname'] = $results[$r]['divisionname'];
		if ($titles[$results[$r]['team_id']]['team_id'] == $results[$r]['team_id']) $titles[$results[$r]['team_id']]['count']++;
	}
	unset ($results);

	// Sort the array by count.
	$count = array_column($titles, 'count');

	array_multisort($count, SORT_DESC, array_column($titles, 'lastseason'), SORT_DESC, $titles);
	unset ($count);

	// Return the first keys of the array by $limit.
	$titles = array_slice($titles, 0, $limit);

	return $titles;

}

function get_award($args = array()) {
	global $cms;
	$args += array(
		'id'			=> 0,
		'enabled'		=> 1,
		'idx'			=> 0,
		'negative'		=> 0,
		'award_name'			=> '',
		'groupname'		=> '',
		'phrase'		=> '',
		'expr'			=> '',
		'format'		=> '',
		'description'	=> '',
		'limit'			=> 5,
		'sort'			=> '',
		'order'			=> 'desc',
		'fields'		=> '',
		'where'			=> '',
//		'joinccinfo'	=> true,
	);

	$values = "";
	if (trim($args['fields']) == '') {
		$values .= "name.*,adv.season season_n,adv.team_id team_n,adv.*,def.*,off.*";
//		if ($args['joinccinfo']) $values .= ",c.* ";
	} else {
		$values = $args['fields'];
	}
	
	$expr = simple_interpolate($args['expr'], array(), true);

	if ($args['where'] != '') {
		$where = "HAVING " . simple_interpolate($args['where'], array(), true) . " ";
	} else {
		$where = '';
	}

	$order = $args['order'];
	$limit = $args['limit'];

	$cmd  = "SELECT $expr awardvalue,$values FROM $this->t_team_adv adv ";
	$cmd .= "LEFT JOIN $this->t_team_def def ON adv.team_id = def.team_id AND adv.season = def.season ";
	$cmd .= "LEFT JOIN $this->t_team_off off ON adv.team_id = off.team_id AND adv.season = off.season ";
	$cmd .= "JOIN (SELECT DISTINCT team_name,team_id,lastseen FROM $this->t_team_ids_names JOIN (SELECT MAX(lastseen) max_ls FROM $this->t_team_ids_names) n ON n.max_ls = lastseen WHERE team_name <> '' GROUP BY team_id) name ON adv.team_id = name.team_id ";
    
    // to get rid of duplicate team listings
	$cmd  .= "GROUP BY team_n, season_n ";

	$cmd .= $where;

	$cmd .= "ORDER BY 1 $order, adv.season DESC ";
	$cmd .= "LIMIT $limit ";
	
	$award = array(
		'idx'			=> $args['idx'],
		'negative'		=> $args['negative'],
		'award_name'	=> $args['award_name'],
		'phrase'		=> $args['phrase'],
		'format'		=> $args['format'],
		'description'	=> $args['description'],
		'order'			=> $args['order'],
	);

	$results = array();
	$results = $this->db->fetch_rows(1, $cmd);

	foreach ($results as $tm => $val) {
		$results[$tm] = array(
			'format'		=> $args['format'],
			'awardvalue'	=> $results[$tm]['awardvalue'],
			'team_id'		=> $results[$tm]['team_id'],
			'team_name'		=> $results[$tm]['team_name'],
			'season'	=> $results[$tm]['season_n'],
		);
	}
	$award['team_id'] = $results[0]['team_id'];
	$award['topteamname'] = $results[0]['team_name'];
	$award['topteamvalue'] = $results[0]['awardvalue'];
	$award['awardseason'] = $results[0]['season'];

	$award = array_merge($award, $results);
	unset ($results);

	return $award;
}

function get_award_team_list($args = array()) {
	$args += array(
		'id'		=> 0,
		'fields'	=> '',
		'where'		=> '',
		'sort'		=> 'idx',
		'order'		=> 'desc',
		'start'		=> 0,
		'limit'		=> 5,
	);
	$id = $args['id'];
	if (!is_numeric($id)) $id = 0;
	$fields = $args['fields'] ? $args['fields'] : "ap.*, ac.format, ac.desc, team.*, pp.*";

	$cmd  = "SELECT $fields ";
	$cmd .= "FROM ($this->t_awards_teams ap, $this->t_awards a, $this->t_config_awards ac) ";
	$cmd .= "LEFT JOIN $this->t_team_adv team ON team.team_id=ap.team_id ";
	$cmd .= "LEFT JOIN $this->t_team_profile pp ON pp.team_id=team.team_id ";
	$cmd .= "WHERE ap.awardid=a.id AND a.awardid=ac.id AND ap.awardid=" . $this->db->escape($id) . " ";
	if ($args['where'] != '') $cmd .= "AND (" . $args['where'] . ") ";
	$cmd .= $this->getsortorder($args);
	$list = array();
	$list = $this->db->fetch_rows(1, $cmd);
//	print $this->db->lastcmd;

	return $list;
}

function get_help($args = array()) {
	global $cms;
	$args += array(
		'id'			=> 0,
		'enabled'		=> 1,
		'idx'			=> 0,
		'title'			=> '',
		'content'		=> '',
		'img'			=> '',
		'weight'		=> 0,
	);

	$id = $args['id'];
	$cmd  = "SELECT * FROM $this->t_config_help ";
	$cmd .= "WHERE id=$id";

	$results = array();
	$results = $this->db->fetch_rows(1, $cmd);
	$results = $results[0];

	return $results;
}

function get_top_help() {
	$cmd  = "SELECT `title` FROM $this->t_config_help ORDER BY weight DESC, idx ASC LIMIT 50";

	$results = array();
	$results = $this->db->fetch_rows(1, $cmd);

	return $results;
}

function get_team_list($args = array()) {
	global $cms;
	$args += array(
		'allowall'	=> false,
		'season'	=> null,
		'start'		=> 0,
		'limit'		=> 100,
		'sort'		=> 'win_percent',
		'order'		=> 'desc',
		'fields'	=> '',
		'where'		=> '',
		'filter'	=> '',
//		'joinccinfo'	=> true,
		'results'	=> null,
		'search'	=> null
	);
	$season = $args['season'];

	# Get season length.
	$season_l = $this->get_season_length($season);

	$values = "";
	if (trim($args['fields']) == '') {
		$values .= "name.*,adv.team_id team_n,adv.*,team.*,def.*,off.*,prof.icon,prof.cc ";
//		if ($args['joinccinfo']) $values .= ",c.* ";
	} else {
		$values = $args['fields'];
	}

	$cmd  = "SELECT $values FROM $this->t_team_adv adv ";
	$cmd .= "LEFT JOIN $this->t_team team ON adv.team_id = team.team_id ";
	$cmd .= "LEFT JOIN $this->t_team_def def ON adv.team_id = def.team_id AND def.season=$season ";
	$cmd .= "LEFT JOIN $this->t_team_off off ON adv.team_id = off.team_id AND off.season=$season ";
	$cmd .= "LEFT JOIN $this->t_team_profile prof ON adv.team_id = prof.team_id ";
	$cmd .= "JOIN (SELECT DISTINCT team_name,team_id,lastseen FROM $this->t_team_ids_names JOIN (SELECT MAX(lastseen) max_ls FROM $this->t_team_ids_names) n ON n.max_ls = lastseen WHERE team_name <> '' GROUP BY team_id) name ON adv.team_id = name.team_id AND adv.season=$season ";

	if (trim($args['where']) != '') $cmd .= $args['where'] . " ";
	
	$list = array();
	// limit list to search results
	$results = $args['results'];
	if ($args['search']) {
		$results = $this->get_search($args['search']);
	}
	if ($results) {
//		$args['start'] = 0;	// override start since we sliced the array
//		$team_ids = array_slice(explode(',',$results['results']), $args['start'], $args['limit']);
		$team_ids = explode(',',$results['results']);
		if (count($team_ids)) {
			$cmd .= "AND team.team_id IN (" . join(',', $team_ids) . ") ";
		}
	}
    
    // to get rid of duplicate team listings
	$cmd  .= "GROUP BY name.team_id ";
	
	// only do a query if we are not searching or if our current search
	// actually has some data to return.
	if (!$results or $results['results']) {
		$cmd .= $this->getsortorder($args);
		//echo $cmd . "<br>";
		//exit;
		$list = $this->db->fetch_rows(1, $cmd);

		// Generate games_played and games_remaining
		$list[0]['games_played'] ??= 0;
		$list[0]['games_remaining'] = $season_l - $list[0]['games_played'];

		// Get playoff status if the season has not ended.
		if ($list[0]['games_remaining'] != 0) {
			$clinch_count = array();
			$div_count = array();
			foreach ($list as $tm => $val) {
				$list[$tm]['games_back'] ??= null;
				$list[$tm]['divisionname'] ??= null;
				$list[$tm]['games_back'] = $this->get_playoff_status($season, $list[$tm]['games_played'], $list[$tm]['games_back']);
				$clinch_count[$list[$tm]['divisionname']] ??= null;
				if ($list[$tm]['games_back'] == 'elim')
					$clinch_count[$list[$tm]['divisionname']]++;
				$div_count[$list[$tm]['divisionname']] ??= null;
				$div_count[$list[$tm]['divisionname']]++;
			}

			// Get clinched status.
			$div_status = array();
			foreach ($clinch_count as $div => $val) {
				if (($div_count[$div] - $clinch_count[$div]) == 1) $div_status[$div] = 'clinch';
			}

			// Assign clinch status to team array.
			foreach ($div_status as $div => $val) {
				foreach ($list as $tm => $val1) {
					if ($list[$tm]['divisionname'] == $div) {
						$list[$tm]['games_back'] = 'clinch';
						break;
					}
				}
			}
		} else {
			// Assign season rank to team array.
			foreach ($list as $tm => $val) {
				$list[$tm]['rank'] = $this->get_rank($list[$tm]['team_id'], $season);
			}
		}

		// Generate games_played and games_remaining
		$list[0]['games_played'] ??= 0;
		$list[0]['games_remaining'] = $season_l - $list[0]['games_played'];

		// Set divisionname if it has not been set.
		$list[0]['divisionname'] ??= 'na';

	}

	return $list;
}

// Loads a list of team information (no stats) including their profile and assoicated user information
function get_basic_team_list($args = array()) {
	global $cms;
	$args += array(
		'allowall'	=> false,
		'season'	=> null,
		'start'		=> 0,
		'limit'		=> 100,
		'sort'		=> 'win_percent',
		'order'		=> 'desc',
		'fields'	=> '',
		'where'		=> '',
		'filter'	=> '',
//		'joinccinfo'	=> true,
		'results'	=> null,
		'search'	=> null
	);
	$season = $args['season'];
	$values = "";
	if (trim($args['fields']) == '') {
		$values .= "name.*,adv.team_id team_n,adv.*,prof.userid,user.username ";
//		if ($args['joinccinfo']) $values .= ",c.* ";
	} else {
		$values = $args['fields'];
	}

	$cmd  = "SELECT $values FROM $this->t_team_adv adv ";
	$cmd .= "LEFT JOIN $this->t_team_profile prof ON adv.team_id = prof.team_id ";
	$cmd .= "LEFT JOIN $this->t_user user ON prof.userid = user.userid ";
	$cmd .= "JOIN (SELECT DISTINCT team_name,team_id,lastseen FROM $this->t_team_ids_names JOIN (SELECT MAX(lastseen) max_ls FROM $this->t_team_ids_names) n ON n.max_ls = lastseen WHERE team_name <> '' GROUP BY team_id) name ON adv.team_id = name.team_id AND adv.season=$season ";

	if (trim($args['where']) != '') $cmd .= $args['where'] . " ";

	// basic filter
	$args['filter'] ??= '';
	if (trim($args['filter']) != '') {
		$cmd .= " AND (name.team_name LIKE '%" . $this->db->escape(trim($args['filter'])) . "%') ";		
	}

	$list = array();
	// limit list to search results
	$results = $args['results'];
	if ($args['search']) {
		$results = $this->get_search($args['search']);
	}
	if ($results) {
		$team_ids = explode(',',$results['results']);
		if (count($team_ids)) {
			$cmd .= "AND adv.team_id IN (" . join(',', $team_ids) . ") ";
		}
	}
    
    // to get rid of duplicate team listings
	$cmd  .= "GROUP BY name.team_id ";
	
	// only do a query if we are not searching or if our current search
	// actually has some data to return.
	if (!$results or $results['results']) {
		$cmd .= $this->getsortorder($args);
		$list = $this->db->fetch_rows(1, $cmd);
	}

	// Set divisionname if it has not been set.
	if ($list) $list[0]['divisionname'] ??= 'na';

	return $list;
}

function get_division_list($args = array()) {
	$args += array(
		'season'	=> null,
		'start'		=> 0,
		'limit'		=> 20,
		'sort'		=> 'win_percent',
		'order'		=> 'desc',
		'fields'	=> '',
		'where'		=> '',
		'allowall'	=> 0,
	);

	$cmd  = "SELECT COUNT(DISTINCT adv.team_id) totalmembers,adv.season,adv.team_id,adv.divisionname,SUM(adv.win_percent) win_percent,SUM(adv.team_rdiff) team_rdiff,";
	$cmd .= "def.season,def.team_id,SUM(def.team_ra) team_ra,SUM(def.team_whip) team_whip,SUM(def.team_drat) team_drat,";
	$cmd .= "off.season,off.team_id,SUM(off.run_support) run_support,SUM(off.ops) ops,SUM(off.team_srat) team_srat ";
	
	$cmd .= "FROM $this->t_team_adv adv, $this->t_team_def def, $this->t_team_off off ";

	$cmd .= "WHERE adv.season=" . $args['season'] . " ";
	$cmd .= "AND def.season=adv.season AND def.team_id=adv.team_id ";
	$cmd .= "AND off.season=adv.season AND off.team_id=adv.team_id ";

	$cmd .= "GROUP BY adv.divisionname ";
	$cmd .= $this->getsortorder($args);

	$list = array();
	$list = $this->db->fetch_rows(1, $cmd);

	foreach ($list as $div => $val) {
        $list[$div]['win_percent'] = round($list[$div]['win_percent'] / $list[$div]['totalmembers'], 3);
        $list[$div]['team_rdiff'] = round($list[$div]['team_rdiff'] / $list[$div]['totalmembers'], 2);
        $list[$div]['team_ra'] = round($list[$div]['team_ra'] / $list[$div]['totalmembers'], 2);
        $list[$div]['run_support'] = round($list[$div]['run_support'] / $list[$div]['totalmembers'], 1);
        $list[$div]['team_whip'] = round($list[$div]['team_whip'] / $list[$div]['totalmembers'], 2);
        $list[$div]['ops'] = round($list[$div]['ops'] / $list[$div]['totalmembers'], 3);
        $list[$div]['team_drat'] = round($list[$div]['team_drat'] / $list[$div]['totalmembers'], 2);
        $list[$div]['team_srat'] = round($list[$div]['team_srat'] / $list[$div]['totalmembers'], 2);
    }
//	print "explain " . $this->db->lastcmd;

	return $list;
}

function get_wc_list($args = array()) {
	global $cms;
	$args += array(
		'season_c'	=> null,	
		'allowall'	=> false,
		'start'		=> 0,
		'limit'		=> 100,
		'sort'		=> 'win_percent',
		'order'		=> 'desc',
		'fields'	=> '',
		'where'		=> '',
		'filter'	=> '',
//		'joinccinfo'	=> true,
		'results'	=> null,
		'search'	=> null
	);
	$season = $args['season_c'];

	# Get season length.
	$season_l = $this->get_season_length($season);
	
	$values = "";
	if (trim($args['fields']) == '') {
		$values .= "wc.*,team.*,name.*,prof.*,adv.team_id team_n,adv.*,def.*,off.* ";
	} else {
		$values = $args['fields'];
	}

	$cmd  = "SELECT $values FROM $this->t_team_wc wc ";
	$cmd .= "LEFT JOIN $this->t_team_adv adv ON wc.team_id = adv.team_id AND adv.season=$season ";
	$cmd .= "LEFT JOIN $this->t_team team ON wc.team_id = team.team_id ";
	$cmd .= "LEFT JOIN $this->t_team_def def ON wc.team_id = def.team_id AND def.season=$season ";
	$cmd .= "LEFT JOIN $this->t_team_off off ON wc.team_id = off.team_id AND off.season=$season ";
	$cmd .= "LEFT JOIN $this->t_team_profile prof ON wc.team_id = prof.team_id ";
	$cmd .= "JOIN (SELECT DISTINCT team_name,team_id,lastseen FROM $this->t_team_ids_names JOIN (SELECT MAX(lastseen) max_ls FROM $this->t_team_ids_names) n ON n.max_ls = lastseen WHERE team_name <> '' GROUP BY team_id) name ON wc.team_id = name.team_id ";

	if (!$args['allowall']) $cmd .= "AND team.allowrank=1 ";
	if (trim($args['where']) != '') $cmd .= "AND (" . $args['where'] . ") ";

	$filter = trim($args['filter']);
	if ($filter != '') {
		$f = '%' . $this->db->escape($filter) . '%';
		$cmd .= "AND (name.team_name LIKE '$f') ";
	}
	
	$list = array();
	// limit list to search results
	$results = $args['results'];
	if ($args['search']) {
		$results = $this->get_search($args['search']);
	}
	if ($results) {
//		$args['start'] = 0;	// override start since we sliced the array
//		$team_ids = array_slice(explode(',',$results['results']), $args['start'], $args['limit']);
		$team_ids = explode(',',$results['results']);
		if (count($team_ids)) {
			$cmd .= "AND team.team_id IN (" . join(',', $team_ids) . ") ";
		}
	}
    
    // to get rid of duplicate team listings
	$cmd  .= "GROUP BY name.team_id ";
	
	// only do a query if we are not searching or if our current search
	// actually has some data to return.
	if (!$results or $results['results']) {
		$cmd .= $this->getsortorder($args);
		$list = $this->db->fetch_rows(1, $cmd);
	}

	// Generate games_played and games_remaining
	$list[0]['games_played'] ??= null;
	$list[0]['games_remaining'] = $season_l - $list[0]['games_played'];

	// Get playoff status.
	$clinch_count = 0;
	$in_count = 0;
	foreach ($list as $tm => $val) {
		$list[$tm]['games_back_wc'] ??= null;
		if ($list[$tm]['games_back_wc'] == '-') $in_count++;
		$list[$tm]['games_back_wc'] = $this->get_playoff_status($season, $list[$tm]['games_played'], $list[$tm]['games_back_wc']);
		if ($list[$tm]['games_back_wc'] == 'elim') $clinch_count++;
	}

	// How many teams are in the race?
	$total_wc = $this->get_total_wc();

	// Get clinched status.
	$list[0]['wins'] ??= null;
	$prev_wins = $list[0]['wins'];
	$prev_tm = 0;

	foreach ($list as $tm => $val) {
		if (($list[$tm]['games_back_wc'] == '-') && (($total_wc - $clinch_count) == $in_count)) $list[$tm]['games_back_wc'] = 'clinch';
		if (($list[$tm]['games_back_wc'] == 'clinch') && ($prev_wins == $list[$tm]['wins']) && ($tm != 0)) {
			$list[$prev_tm]['games_back_wc'] = 'tie';
			$list[$tm]['games_back_wc'] = 'tie';
		}
		$prev_wins = $list[$tm]['wins'];
		$prev_tm = $tm;
	}

	// Set divisionname if it has not been set.
	$list[0]['divisionname'] ??= 'na';
	
	return $list;
}

function get_team_roster($args = array(), $minimal = false) {
	if (!is_array($args)) {
		$id = $args;
		$args = array( 'team_id' => $id );
	}
	$args += array(
		'season'		=> null,
		'team_id'		=> 0,
		'loadpitcher'	=> 1,
		'loadposition'	=> 1,
		'loadcounts'	=> 1,
		'pitchersort'	=> 'pi_innings_pitched',
		'pitcherorder'	=> 'desc',
		'pitcherstart'	=> 0,
		'pitcherlimit'	=> 24,
		'positionsort'	=> 'po_at_bats',
		'positionorder'	=> 'desc',
		'positionstart'	=> 0,
		'positionlimit'	=> 24,
	);
	$roster = array();
	$id = $args['team_id'];
	if (!is_numeric($id)) $id = 0;

	$season = $args['season'];

	if ($args['loadcounts']) {
		$roster['totalpitcher'] 	= $this->db->count($this->t_team_rpi, '*', "team_id='$id' AND season='$season'");
		$roster['totalposition'] 	= $this->db->count($this->t_team_rpo, '*', "team_id='$id' AND season='$season'");
	}

	// Load pitcher stats for the team.
	if ($args['loadpitcher']) {
		$cmd  = "SELECT * FROM $this->t_team_rpi ";
		$cmd .= "WHERE team_id='$id' AND season='$season' ";
		$cmd .= $this->getsortorder($args, 'pitcher');
		$roster['pitcher'] = $this->db->fetch_rows(1, $cmd);
	}

	// Load offensive stats for the team.
	if ($args['loadposition']) {
		$cmd  = "SELECT * FROM $this->t_team_rpo ";
		$cmd .= "WHERE team_id='$id' AND season='$season' ";
		$cmd .= $this->getsortorder($args, 'position');
		$roster['position'] = $this->db->fetch_rows(1, $cmd);
	}
	
	return $roster;
}

// returns some basic summarized stats from the table
function get_sum($args = array(), $table = null) {
	if ($table === null) $table = $this->t_team_adv;	// best table to summarize from
	$cmd = "SELECT ";
	foreach ($args as $key) {
		$key = $this->db->qi($key);
		$cmd .= "SUM($key) $key,";
	}
	$cmd = substr($cmd,0,-1);
	$cmd .= " FROM " . $this->db->qi($table);
	return $this->db->fetch_row(1,$cmd);
}

function get_total_teams($args = array()) {
	$args += array(
		'allowall'	=> false,
		'filter'	=> '',
	);
	$cmd = "";
	$filter = trim($args['filter'] ?? '');
	if ($filter == '') {
		$cmd = "SELECT count(*) FROM $this->t_team team WHERE 1 ";
	} else {

	///////////


		$cmd  = "SELECT count(*) FROM $this->t_team_adv adv ";
		$cmd .= "LEFT JOIN $this->t_team_profile prof ON adv.team_id = prof.team_id ";
		$cmd .= "LEFT JOIN $this->t_user user ON prof.userid = user.userid ";
		$cmd .= "JOIN (SELECT DISTINCT team_name,team_id,MAX(lastseen) FROM $this->t_team_ids_names GROUP BY team_id) name ON adv.team_id = name.team_id AND adv.season=" . $args['season'] . " ";
    
    	// to get rid of duplicate team listings
		$cmd  .= "GROUP BY name.team_id ";




	//////////
	}
	// basic filter
	if ($filter != '') {
		$f = '%' . $this->db->escape($filter) . '%';
		$cmd .= " AND (name.team_name LIKE '$f')";	// I don't like using OR logic, queries run much slower.
	}
	$this->db->query($cmd);
	list($total) = $this->db->fetch_row(0);
	return $total;
}

function get_total_help() {
	$cmd = "";
	$cmd = "SELECT count(*) FROM $this->t_config_help";
	$this->db->query($cmd);
	list($total) = $this->db->fetch_row(0);
	return $total;
}

function get_rank($team_id, $season) {
	$args = array(
		'sort'		=> 'win_percent, pythag',
		'order'		=> 'desc',
	);
	// Rank for requested season.
	$cmd  = "SELECT * FROM $this->t_team_adv WHERE season=$season ";
	$cmd .= $this->getsortorder($args);
	$list = $this->db->fetch_rows(1, $cmd);

	(!$list) ? $rank = null : $rank = array_search($team_id, array_column($list, 'team_id')) + 1;

	return $rank;
}

function get_prevrank($team_id, $season) {
	// Rank for season previous to requested season.
	$season_p = $season - 1;
	$prevrank = $this->get_rank($team_id, $season_p);

	return $prevrank;
}

function get_season_length($season) {
	$cmd = "SELECT season_l FROM $this->t_seasons_h WHERE season_h=$season LIMIT 1";
	$season_l = $this->db->fetch_row(1, $cmd);
	if (is_array($season_l)) $season_l = implode($season_l);
	$season_l ??= '162';

	return $season_l;
}

function get_total_divisions() {
	$cmd  = "SELECT count(DISTINCT divisionname) FROM psss_team_adv";
	$this->db->query($cmd);
	list($total) = $this->db->fetch_row(0);
	$total ??= 0;

	return $total;
}

function get_total_wc() {
	$cmd  = "SELECT count(*) total FROM $this->t_team_wc wc ";
	$this->db->query($cmd);
	list($total) = $this->db->fetch_row(0);

	return $total;
}

function get_playoff_status($season, $gp = 0, $gb = 0) {

	# Get season length.
	$season_l = $this->get_season_length($season);

	$gr = $season_l - $gp;
	if (is_numeric($gb)) {
		$gb_i = $gb;
		$playoff_status = (($gr - $gb_i) < 0) ? "elim:" . $gb_i : $gb;
	} else {
		$playoff_status = $gb;
	}

	return $playoff_status;
}

function get_total_awards($args = array()) {
	$args += array(
		'type'		=> '',
	);
	return 0; #########################################################
	$where = $args['type'] ? "WHERE type='" . $this->db->escape($args['type']) . "' " : "";
	$cmd  = "SELECT count(distinct awardid) FROM $this->t_awards $where LIMIT 1";
	$this->db->query($cmd);
	list($total) = $this->db->fetch_row(0);

	return $total;
}

// deletes a team profile only, not team stats
function delete_team_profile($team_id) {
	global $cms;
	$_id = $this->db->escape($team_id, true);
	list($userid) = $this->db->fetch_row(0,"SELECT userid FROM $this->t_team_profile WHERE team_id=$_id");
	$this->db->delete($this->t_team_profile, 'team_id', $team_id);
	$cms->user->delete_user($userid);
}

function getsortorder($args, $prefix='') {
	return $this->db->sortorder($args, $prefix);
}

function getlimit($args, $prefix='') {
	return $this->db->limit($args, $prefix);
}

// return's a SQL filter based on the parameters given.
// the returned SQL should be used on any WHERE clause that is selecting teams.
function create_team_filter() {
/* This is an example of how the final SQL might look for a team search (minus the @q var)
SET @q := '%a%';
(select p.win_percent sorted,p.team_id,pp.team_id,pp.name FROM psss_team p, psss_team_profile pp WHERE p.team_id=pp.team_id AND pp.name like @q)
UNION
(select p.win_percent,p.team_id,pp.team_id,pp.name FROM (psss_team p, psss_team_profile pp) LEFT JOIN psss_team_ids_name i ON (i.team_id=p.team_id) 
	WHERE p.team_id=pp.team_id AND i.name like @q)
UNION
(select p.win_percent,p.team_id,pp.team_id,pp.name FROM (psss_team p, psss_team_profile pp) LEFT JOIN psss_team_ids_team_id i ON (i.team_id=p.team_id) 
	WHERE p.team_id=pp.team_id AND i.team_id like @q)
UNION
(select p.win_percent,p.team_id,pp.team_id,pp.name FROM (psss_team p, psss_team_profile pp) LEFT JOIN psss_team_ids_ipaddr i ON (i.team_id=p.team_id) 
	WHERE p.team_id=pp.team_id AND INET_ATON(@q) = i.ipaddr)
ORDER BY sorted DESC
*/
}

// loads a portion of config into memory.
// This is optimized to only load the variables of the config, not the extra layout information.
// see the load_config_layout() function if config layout info is needed.
function load_config($type) {
	$conflist = !is_array($type) ? $conflist = array($type) : $type;
	$c = array();
	$cmd = "SELECT conftype,section,var,value FROM $this->t_config WHERE var IS NOT NULL AND conftype IN (";
	foreach ($conflist as $conftype) {
		$this->conf[$conftype] = array();
		$c[] = $this->db->escape($conftype, true);
	}
	$cmd .= join(', ', $c) . ")";
	$list = $this->db->fetch_rows(1, $cmd);
	foreach ($list as $row) {
		if (empty($row['section'])) {
			$this->_assignvar($this->conf[$row['conftype']], $row['var'], $row['value']);
		} else {
			$this->_assignvar($this->conf[$row['conftype']][$row['section']], $row['var'], $row['value']);
		}
	}
}

// loads the full config and it's layout. This WILL NOT overwrite the currently loaded config.
function load_config_layout($type, $where = "") {
	$conflist = !is_array($type) ? $conflist = array($type) : $type;
	$c = array();
	$cmd = "SELECT * FROM $this->t_config WHERE var IS NOT NULL AND conftype IN (";
	foreach ($conflist as $conftype) {
		$this->conf_layout[$conftype] = array();
		$c[] = $this->db->escape($conftype, true);
	}
	$cmd .= join(', ', $c) . ")";
	if ($where != '') $cmd .= " AND $where";
	$cmd .= " ORDER BY label,section,var";
	$list = $this->db->fetch_rows(1, $cmd);
	foreach ($list as $row) {
		if (empty($row['section'])) {
			$this->_assignvar($this->conf_layout[$row['conftype']], $row['var'], $row);
		} else {
			$this->_assignvar($this->conf_layout[$row['conftype']][$row['section']], $row['var'], $row);
		}
	}
	return $this->conf_layout;
}

// returns the entire config keyed on ID
function load_config_by_id($fields = '*', $where = "") {
	$cmd = "SELECT $fields FROM $this->t_config";
	if ($where != '') $cmd .= " $where";
	$list = $this->db->fetch_rows(1, $cmd);
	$c = array();
	foreach ($list as $row) {
		$c[ $row['id'] ] = $row;
	}
	return $c;
}

// returns a single config variable (with full layout) based on it's ID
// returns false if the row was not found
function load_conf_var($id, $key = 'id') {
	$row = $this->db->fetch_row(1, "SELECT * FROM $this->t_config WHERE " . $this->db->qi($key) . "=" . $this->db->escape($id, true));
	return $row ? $row : false;
}


// writes the error message to the error log
// trims the log if it grows too large (unless $notrim is true)
function errlog($msg, $severity='warning', $userid=NULL, $notrim=false) {
	if (!in_array($severity, array('info','warning','fatal'))) {
		$severity = 'warning';
	}
	$msg = trim($msg);
	if ($msg == '') return;		// do nothing if there is no message
	$this->db->insert($this->t_errlog, array(
		'id'		=> $this->db->next_id($this->t_errlog), 
		'timestamp'	=> time(),
		'severity'	=> $severity,
		'userid'	=> $userid,
		'msg'		=> $msg
	));

	if (!$notrim) {
		$this->trim_errlog();
	}
}

// trims the errlog size to the configured settings. 
// if $all is true then the errlog table is truncated
function trim_errlog($all=false) {
	$maxrows = $this->conf['main']['errlog']['maxrows'];
	$maxdays = $this->conf['main']['errlog']['maxdays'];
	if ($maxrows == '') $maxrows = 5000;
	if ($maxdays == '') $maxdays = 30;
	if (intval($maxrows) + intval($maxdays) == 0) return;		// nothing to trim
	$deleted = 0;
	if ($maxdays) {
		$this->db->query("DELETE FROM $this->t_errlog WHERE " . $this->db->qi('timestamp') . " < " . (time()-60*60*24*$maxdays));
		$deleted++;
	}
	if ($maxrows) {
		$total = $this->db->count($this->t_errlog);
		if ($total <= $maxrows) return;
		$diff = $total - $maxrows;
		$list = $this->db->fetch_list("SELECT id FROM $this->t_errlog ORDER BY " . $this->db->qi('timestamp') . " LIMIT $diff");
		if (is_array($list) and count($list)) {
			$this->db->query("DELETE FROM $this->t_errlog WHERE id IN (" . implode(',', $list) . ")");
			$deleted++;
		}
	}
	if ($deleted) {
		if (mt_rand(1,20) == 1) {	// approximately 20% chance of optimizing the table
			$this->db->optimize($this->t_errlog);
		}
	}
}

function get_types($prefix, $mod=1) {
	$var = $prefix . "_TYPES";
	$modvar = $prefix . "_MODTYPES";
	if ($mod and is_array($this->$modvar)) {
		return $this->$var + $this->$modvar;
	} else {
		return $this->$var;
	}
}

// internal function for load_config. do not call outside of class
function _assignvar(&$c,$var,$val) {
	if (!is_array($c)) $c = array();
	if (array_key_exists($var, $c)) {
		if (!is_array($c[$var])) {
			$c[$var] = array( $c[$var] );
		}
		$c[$var][] = $val;
	} else {
		$c[$var] = $val	;
	}
}

// returns a value string used for certain non-division statistics (like team sessions)
function _calcvalues($fields, $types) {
	$values = "";
	foreach ($fields as $key) {
		if (array_key_exists($key, $types)) {
			$type = $types[$key];
			if (is_array($type)) {
				$func = "_soloexpr_" . array_shift($type);
				if (method_exists($this->db, $func)) {
					$values .= $this->db->$func($type) . " $key, ";
				}
			} else {
				$values .= "$key, ";
			} 
		} else {
			$values .= "$key, ";
		}
	}
	$values = substr($values, 0, -2);		// trim trailing comma: ", "
	return $values;
}

// returns a value string used in the division statistics
function _values($fields, $types) {
	$values = "";
	foreach ($fields as $key) {
		if (array_key_exists($key, $types)) {
			$type = $types[$key];
			if (is_array($type)) {
				$func = "_expr_" . array_shift($type);
				if (method_exists($this->db, $func)) {
					$values .= $this->db->$func($type) . " $key, ";
				} else {
					# ignore key
				}
			} else {
				if ($type == '>') {
					$values .= "MAX($key) $key, ";
				} elseif ($type == '<') {
					$values .= "MIN($key) $key, ";
				} elseif ($type == '~') {
					$values .= "AVG($key) $key, ";
				} else {	# $type == '+'
					$values .= "SUM($key) $key, ";
				}
			}
		} else {
			$values .= "$key, ";
		}
	}
	$values = substr($values, 0, -2);		// trim trailing comma: ", "
	return $values;
}

// read a config from a file or string.
// If the TYPE can not be determined the imported variables are ignored.
// set $forcetype to a conftype if you know the type of the config you're loading.
// returns 'FALSE' if no errors, otherwise returns an array of all invalid config options that were ignored.
// *** FIX ME ***
function import_config($source, $forcetype = false, $opts = array()) {
	$opts += array(
		'replacemulti'	=> 1,
		'ignorenew'	=> 1,
	);
	$SEP = "^";
	if (is_array($source)) {
		$lines = $source;
	} elseif (strlen($source)<=255 and @is_file($source) and @is_readable($source)) {
		$lines = file($source);
	} else {
		$lines = explode("\n", $source);
	}
	$lines = array_map('trim', $lines);	// normalize all lines

	$section = '';
	$errors = array();
	$type = $forcetype !== false ? $forcetype : '';
	if ($type and !array_key_exists($type, $this->conf)) $this->load_config($type);

	$this->_layout = array();
	$this->_import_errors = array();
	$this->_import_multi = array();
	$this->_import_opts = $opts;

	foreach ($lines as $line) {
		if ($forcetype === false and preg_match('/^#\\$TYPE\s*=\s*([a-zA-Z_]+)/', $line, $m)) {
			$type = $m[1];
			if (!array_key_exists($type, $this->conf)) $this->load_config($type);
			$this->_update_layout($type);
			$section = '';
		} 
		if ($line[0] == '#') continue; 		// ignore comments;

		if (preg_match('/^\[([^\]]+)\]/', $line, $m)) {
			$section = $m[1];
			if (strtolower($section) == 'global') $section = '';
		} elseif (preg_match('/^([\w\d_]+)\s*=\s*(.*)/', $line, $m)) {
			if ($type) {
				$this->_import_var($type, $section, $m[1], $m[2]);
			} else {
				$this->_import_errors['unknown_types'][] = $section ? $section . "." . $m[1] : $m[1];
			}
		}
	}

	return count($this->_import_errors) ? $this->_import_errors : false;
}

// *** FIX ME ***
function _import_var($type, $section, $var, $val) {
#	print "$type:: $section.$var = $val<br>\n";
	$key = $section ? $section . "." . $var : $var;

	// do not allow changes to locked variables
	if ($this->_layout[$key]['locked']) {
		$this->_import_errors['locked_vars'][] = $key;
		return false;
	}

	// verify the variable is 'sane' according to the layout rules
	$field = array( 'val' => $this->_layout[$key]['verifycodes'], 'error' => '' );
	form_checks($val, $field);
	if ($field['error']) {
		$this->_import_errors['invalid_vars'][$key] = $field['error'];
		return false;
	}

	// do not accept NEW vars if 'ignorenew' is enabled
	$exists = (($section and array_key_exists($var, $this->conf[$type][$section])) or 
		(!$section and array_key_exists($var, $this->conf[$type])));
	if ($this->_import_opts['ignorenew'] and !$exists) {
		$this->_import_errors['ignored_vars'][] = $key;
		return false;
	}

	// save the imported settings. Take special care of 'multi' options.
	// first: find the matching ID of the current variable (might be more than 1).
	$id = $this->db->fetch_list(sprintf("SELECT id FROM $this->t_config WHERE conftype='%s' AND section='%s' AND var='%s'",
		$this->db->escape($type),
		$this->db->escape($section),
		$this->db->escape($var)
	));
	// if there's no ID, then this is a new option
	$new = false;
	if (!is_array($id) or !count($id)) {
		$new = true;
		$id = array( $this->db->next_id($this->t_config) );
	}
//	print "ID=" . implode(',',$id) . " ($var) == $val<br>";

	// single options can be simply inserted or updated
	// if a non-multi option ends up having more than 1, only the first fetched from the DB is updated
	if (!$this->_layout[$key]['multiple']) {
		if ($new) {
			$this->db->insert($this->t_config, array( 
				'id' 		=> $id[0],
				'conftype' 	=> $type,
				'section' 	=> $section,
				'var' 		=> $var,
				'value' 	=> $val
			));
		} else {
			$this->db->update($this->t_config, array( 'value' => $val ), 'id', $id[0]);
		}
	} else {
		// remove all multi options related to the variable the first time we see it
		if ($this->_import_opts['replacemulti'] and !$this->_import_multi[$key]) {
			$this->_import_multi[$key] = 1;
			$this->db->query("DELETE FROM $this->t_config WHERE id IN (" . implode(',', $id) . ")");
		}
		// now insert the option
		$this->db->insert($this->t_config, array( 
			'id' 		=> $this->db->next_id($this->t_config),
			'idx'		=> $this->_import_multi[$key]++,
			'conftype' 	=> $type,
			'section' 	=> $section,
			'var' 		=> $var,
			'value' 	=> $val
		));
	}
}

// *** FIX ME ***
function _update_layout($type) {
	if (array_key_exists($type, $this->_layout)) return;

	$t = $this->db->escape($type);
	$this->db->query("SELECT c.*,l.* FROM $this->t_config c " . 
		"LEFT JOIN $this->t_config_layout l ON (l.conftype='$t' AND l.section=c.section AND l.var=c.var) " . 
		"WHERE c.conftype='$t' AND (isnull(l.locked) OR !l.locked) " 
	);
	while ($r = $this->db->fetch_row()) {
		$key = $r['var'];
		if ($r['section']) $key = $r['section'] . $SEP . $key;
		$this->_layout[$key] = $r;
	}
}

// returns the config as a string to be imported with import_config
// only exports a single config type at a time.
// *** FIX ME ***
function export_config($type) {
	if (!array_key_exists($type, $this->conf)) $this->load_config($type);

	$config  = "# Configuration exported on " . date("D M j G:i:s T Y") . "\n";
	$config .= "#\$TYPE = $type # do not remove this line\n\n";

	$globalkeys = array();
	$nestedkeys = array();
	$this->_layout = array();
	$this->_update_layout($type);

	foreach (array_keys($this->conf[$type]) as $key) {
		// watch out for items that can be repeated, so we dont treat them like a [section]
		if (is_array($this->conf[$type][$key]) and !$this->_layout[$key]['multiple']) {
			$nestedkeys[$key] = $this->conf[$type][$key];
			ksort($nestedkeys[$key]);
		} else {
			if (is_array($this->conf[$type][$key])) {
				// add each repeated key into the array. 1+ values
				foreach ($this->conf[$type][$key] as $i) {
					$globalkeys[$key][] = $i;
				} 
			} else {
				// there will always only be 1 value in the array
				$globalkeys[$key][] = $this->conf[$type][$key];
			}
		}
	}
	ksort($globalkeys);
	ksort($nestedkeys);

	$width = 1;
	foreach ($globalkeys as $k => $v) if (strlen($k) > $width) $width = strlen($k);
	foreach ($globalkeys as $k => $values) {
		foreach ($values as $v) {
			$config .= sprintf("%-{$width}s = %s\n", $k, $v);
		}
	}

	$config .= "\n";
	foreach ($nestedkeys as $conf => $group) {
		$config .= "[$conf]\n";
		$width = 1;
		foreach ($group as $k => $v) if (strlen($k) > $width) $width = strlen($k);
		foreach ($group as $k => $v) $config .= sprintf("  %-{$width}s = %s\n", $k, $v);
		$config .= "\n";
	}

	return $config;
}

// Takes a source record and returns a string that represents it. 
// Which will be an html URL. 
function parse_source($lp) {
	$str = $lp['source']; 
	return $str;
}

// allows the PS object to initialize some theme related variables, etc...
function theme_setup(&$theme) {
	global $cms;
	global $basename;
	$is_admin = $cms->user->is_admin();
	$cms->input['loggedin'] = $cms->input['loggedin'] ?? null;
	$theme->assign(array(
		//'show_ips'			=> $this->conf['theme']['permissions']['show_ips'] || $is_admin,
		//'show_team_ids'		=> $this->conf['theme']['permissions']['show_team_ids'] || $is_admin,
		'basename'				=> $basename ?? null,
		'maintenance'			=> $this->conf['main']['maintenance_mode']['enable'],
		'lastupdate'			=> $this->get_lastupdate(),
		'show_login'			=> $this->conf['theme']['permissions']['show_login'] || $is_admin,
		'show_register'			=> $this->conf['theme']['permissions']['show_register'] || $is_admin,
		'show_version'			=> $this->conf['theme']['permissions']['show_version'] || $is_admin,
		'show_admin'			=> $this->conf['theme']['permissions']['show_admin'],
		'show_privacy_policy'	=> $this->conf['main']['security']['show_privacy_policy'],
		'show_benchmark'		=> $this->conf['theme']['permissions']['show_benchmark'],
		'show_team_icons'		=> $this->conf['theme']['permissions']['show_team_icons'],
		'show_team_flags'		=> $this->conf['theme']['permissions']['show_team_flags'],
		'loggedin'				=> ($cms->input['loggedin'] and $cms->user->logged_in()),
		'shades'				=> $cms->session->opt('shades'),
		'team_id_noun'			=> $this->team_id_noun(),
		'team_id_noun_plural'	=> $this->team_id_noun(true),
	));
	$theme->assign_by_ref('conf', $this->conf);

	// allow templates to access some PS methods
	$theme->register_object('ps', $this, 
		array( 'version', 'team_id_noun' ),
		false
	);

	$theme->load_styles();
	if ($cms->input['loggedin'] and $cms->user->logged_in()) {
		$theme->add_js('js/loggedin.js');
	}

	// setup the elapsedtime_str static vars once, so all other calls to
	// it will automatically use the translated strings.
	// we ignore the return value.
	elapsedtime_str(array(),0,
			// note the leading space on each word
			array(
				$cms->trans(' years'),
				$cms->trans(' months'),
				$cms->trans(' weeks'),
				$cms->trans(' days'),
				$cms->trans(' hours'),
				$cms->trans(' minutes'),
				$cms->trans(' seconds')
			),
			array(
				$cms->trans(' year'),
				$cms->trans(' month'),
				$cms->trans(' week'),
				$cms->trans(' day'),
				$cms->trans(' hour'),
				$cms->trans(' minute'),
				$cms->trans(' second')
			),
			' ' . $cms->trans('and')
	);
	
	$this->ob_start();
}

// Start the output buffer only if headers have not been sent. If the headers
// have been sent that indicates some sort of error occurred and I don't want
// anything to be obfuscated due to buffering.
function ob_start() {
	if (!headers_sent()) {
		if ($this->conf['theme']['enable_gzip']) {
			ob_start('psss_ob_gzhandler');
		} else {
			ob_start('psss_ob_handler');
		}
	}
}

// Erase all output buffers and discard them
function ob_clean() {
	while (@ob_end_clean());
}

// Erase and restart the output buffer
function ob_restart() {
	$this->ob_clean();
	$this->ob_start();
}

// returns the noun used to describe the 'team_id' for teams.
// For example, ss uses a "TEAM_ID" to identify/describe a team.
// If $plural is true the plural form of the noun will be returned.
function team_id_noun($plural = false) {
	global $cms;
	return $plural ? $cms->trans('Worldids') : $cms->trans('Worldid');
}

// returns the version of PsychoStats
// If theme.show.psss_version is false this returns an empty string unless $force is true
function version($force = false) {
	$v = '';
	if ($this->conf['theme']['permissions']['show_version'] or $force) {
		$v = $this->conf['info']['version'];
		// if the DB version and class_PS version differ show both versions
		if ($v != PSYCHOSTATS_VERSION) {
			$v = "$v-db (" . PSYCHOSTATS_VERSION . "-php)";
		}
//		$v = 'v' . $v;
	}
	return $v;
}

// returns a full <img/> tag for an icon
// $icon is the filename of the icon to display (no path)
function iconimg($icon, $args = array()) {
	$args += array(
		'alt'		=> NULL,
		'height'	=> NULL,
		'width'		=> NULL,
		'path'		=> '',		// add $path to the end of basedir? (eg: 'large/')
		'noimg'		=> '',		// if no img is found then return this instead of the name
		'urlonly'	=> false,	// if true, only the url of the image is returned

		'style'		=> '',		// extra styles
		'class'		=> '',		// class for the image
		'id'		=> '',		// ID for the image
		'extra'		=> '',		// extra paramaters
	);
	if (empty($icon)) return '';
	$icon = basename($icon);		// remove any potential path
	$path = !empty($args['path']) ? $args['path'] : '';
	$basedir = catfile($this->conf['theme']['icons_dir'], $path);
	$baseurl = catfile($this->conf['theme']['icons_url'], $path);

	$alt = psss_escape_html(($args['alt'] !== NULL) ? $args['alt'] : $icon);
	$label = $alt;
	$ext = array_map('trim', explode(',', str_replace('.','', $this->conf['theme']['images']['search_ext'])));

	$name = rawurlencode($icon);
	$img = "";
	$file = "";
	$url = "";
	$file = catfile($basedir,$icon);
	$url  = catfile($baseurl,$name);
	if (!@file_exists($file)) {
		// we're done... 
		return $args['noimg'] !== NULL ? $args['noimg'] : $label;
	}

	if ($args['urlonly']) return $url;

	$attrs = "";
	if (is_numeric($args['width'])) $attrs .= " width='" . $args['width'] . "'";
	if (is_numeric($args['height'])) $attrs .= " height='" . $args['height'] . "'";
	if (!empty($args['style'])) $attrs .= " style='" . $args['style'] . "'";
	if (!empty($args['class'])) $attrs .= " class='" . $args['class'] . "'";
	if (!empty($args['id'])) $attrs .= " id='" . $args['id'] . "'";
	if (!empty($args['extra'])) $attrs .= " " . $args['extra'];
	$img = "<img src='$url' title='$label' alt='$alt'$attrs>";

	return $img;
}

// returns a full <img/> tag for a flag
// $flag is the filename of the flag to display (no path)
function flagimg($cc, $args = array()) {
	$args += array(
		'alt'		=> NULL,
		'height'	=> NULL,
		'width'		=> NULL,
		'path'		=> '',		// add $path to the end of basedir? (eg: 'large/')
		'noimg'		=> '',		// if no img is found then return this instead of the name
		'urlonly'	=> false,	// if true, only the url of the image is returned
		'style'		=> '',		// extra styles
		'class'		=> '',		// class for the image
		'id'		=> '',		// ID for the image
		'extra'		=> '',		// extra paramaters
	);

	if (empty($cc)) return '';
	$cc = strtolower($cc);
	$path = !empty($args['path']) ? $args['path'] : '';
	$basedir = catfile($this->conf['theme']['flags_dir'], $path);
	$baseurl = catfile($this->conf['theme']['flags_url'], $path);

	$alt = psss_escape_html(($args['alt'] !== NULL) ? $args['alt'] : $cc);
	$label = $alt;
	$ext = array_map('trim', explode(',', str_replace('.','', $this->conf['theme']['images']['search_ext'])));

	$name = rawurlencode($cc);
	$img = "";
	$file = "";
	$url = "";
	foreach ($ext as $e) {
		$file = catfile($basedir,$cc) . '.' . $e;
		$url  = catfile($baseurl,$name) . '.' . $e;
		if (@file_exists($file)) break;
		$file = "";
	}
	if (!@file_exists($file)) {
		// we're done... 
		return $args['noimg'] !== NULL ? $args['noimg'] : $label;
	}

	if ($args['urlonly']) return $url;

	$attrs = "";
	if (is_numeric($args['width'])) $attrs .= " width='" . $args['width'] . "'";
	if (is_numeric($args['height'])) $attrs .= " height='" . $args['height'] . "'";
	if (!empty($args['style'])) $attrs .= " style='" . $args['style'] . "'";
	if (!empty($args['class'])) $attrs .= " class='" . $args['class'] . "'";
	if (!empty($args['id'])) $attrs .= " id='" . $args['id'] . "'";
	if (!empty($args['extra'])) $attrs .= " " . $args['extra'];
	$img = "<img src='$url' title='$label' alt='$alt'$attrs>";

	return $img;
}

// reset the stats database, deleting everything. team and division profiles can be saved if 
// specified in the $keep array. By default all optional data is kept.
// config tables are never touched.
// returns TRUE if no errors were encountered, or an array of error strings that occured.
function reset_stats($keep = array()) {
	$keep += array(
		'team_rosters'	=> false,
		'team_profiles'	=> false,
		'team_names'	=> false,
		'users'			=> false,
	);
	$errors = array();
    
	$empty = array( 
		't_errlog',
		't_team',
		't_team_adv', 
		't_team_def', 
		't_team_off', 
		't_team_wc',
		't_search_results',
		't_state', 
		't_seasons_h',
	);

	// delete most of everything
	foreach ($empty as $t) {
		$tbl = $this->$t;
		if (!$this->db->truncate($tbl) and !preg_match("/exist/", $this->db->errstr)) {
			$errors[] = "$tbl: " . $this->db->errstr;
		}
	}

	// delete optional data ...
	$empty_extra = array();
	if (!$keep['team_rosters']) array_push($empty_extra, 't_team_rpi', 't_team_rpo');
	foreach ($empty_extra as $t) {
		$tbl = $this->$t;
		if (!$this->db->truncate($tbl) and !preg_match("/exist/", $this->db->errstr)) {
			$errors[] = "$tbl: " . $this->db->errstr;
		}
	} 

	// delete team profiles ...
	if (!$keep['team_profiles']) array_push($empty_extra, 't_team_profile');
	foreach ($empty_extra as $t) {
		$tbl = $this->$t;
		if (!$this->db->truncate($tbl) and !preg_match("/exist/", $this->db->errstr)) {
			$errors[] = "$tbl: " . $this->db->errstr;
		}
	} 

	// delete team names ...
	if (!$keep['team_names']) array_push($empty_extra, 't_team_ids_names');
	foreach ($empty_extra as $t) {
		$tbl = $this->$t;
		if (!$this->db->truncate($tbl) and !preg_match("/exist/", $this->db->errstr)) {
			$errors[] = "$tbl: " . $this->db->errstr;
		}
	} 

	// delete users (except those that are admins)
	if (!$keep['users']) {
		$ok = true;
		$users = $this->db->fetch_list("SELECT userid FROM $this->t_user WHERE accesslevel < 99");
		$this->db->begin();
		if ($users) {
			$ok = $this->db->query("UPDATE $this->t_team_profile SET userid=NULL WHERE userid IN (" . implode(',', $users) . ")");
			if ($ok) $ok = $this->db->query("DELETE FROM $this->t_user WHERE accesslevel < 99");
		}
		if (!$ok) {
			$errors[] = "$this->t_user: " . $this->db->errstr;
			$this->db->rollback();
		} else {
			$this->db->commit();
		}
	}

	return count($errors) ? $errors : true;
}

function award_format($value, $format = '%s') {
	if (substr($format,0,1) == '%') return sprintf($format, $value);
	switch ($format) {
		case "commify": 	return commify($value);
		case "compacttime": 	return compacttime($value);
		case "date":		return psss_date_stamp($value);
		case "datetime":	return psss_datetime_stamp($value);
		case "remzerodecimal":	return rem_zero_decimal($value);
	}
	// the [brackets] will help troubleshoot issues when a invalid format is specified
	return "[ $value ]";
}

function gametype() {
	return $this->conf['main']['gametype'];
}
function modtype() {
	return $this->conf['main']['modtype'];
}

// mod sub-classes override these to modify various tables within the stats.
// this allows mods to add custom variables to tables specific to each mod.
function index_table_mod(&$table) {}
function wc_table_mod(&$table) {}
function divisions_table_mod(&$table) {}
function division_teams_table_mod(&$table) {}
function team_advanced_table_mod(&$table) {}
function team_defence_table_mod(&$table) {}
function team_offence_table_mod(&$table) {}
function team_pitcher_table_mod(&$table) {}
function team_position_table_mod(&$table) {}
function division_advanced_table_mod(&$table) {}
function division_defence_table_mod(&$table) {}
function division_offence_table_mod(&$table) {}

// add a block of stats to the left side of the stats page.
// this is useful for mods to add their team specific stats.
function team_left_column_mod(&$team, &$theme) {}
function division_left_column_mod(&$division, &$theme) {}

}  // end of PS class

?>
