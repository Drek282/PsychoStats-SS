<?php
/**
	PS::ss::bb
	$Id: bb.php 475 2008-06-01 14:20:09Z lifo $

	ss::bb mod support for PsychoStats front-end
*/
if (!defined("PSYCHOSTATS_PAGE")) die("Unauthorized access to " . basename(__FILE__));
if (defined("CLASS_PS_SS_BB_PHP")) return 1;
define("CLASS_PS_SS_BB_PHP", 1);

include_once(rtrim(dirname(__DIR__), '/\\') . '/ss.php');

class PS_ss_bb extends PS_ss {

var $class = 'PS::ss::bb';

var $DIVISION_MODTYPES = array( 
);

var $WC_MODTYPES = array( 
);

var $ADV_MODTYPES = array( 
);

var $DEF_MODTYPES = array( 
);

var $OFF_MODTYPES = array( 
);

function __construct(&$db) {
	parent::PS_ss($db);
}

function PS_ss_bb(&$db) {
    self::__construct($db);
}

function team_left_column_mod(&$team, &$theme) {
	global $cms;
	static $strings = array();
	if (!$strings) {
		$strings = array(
			'wins'			=> $cms->trans("Wins"),
			'losses'			=> $cms->trans("Losses"),
		);
	}
	$tpl = 'team_left_column_mod';
	if ($theme->template_found($tpl, false)) {
        
		$actions = array();
		$games_played = $team['games_played'];
		if ($games_played) {
			$pct1 = sprintf('%0.02f', $team['wins'] / $games_played * 100);
			$pct2 = sprintf('%0.02f', $team['losses'] / $games_played * 100);
		} else {
			$pct1 = $pct2 = 0;
		}
		
		$actions['games_played'] = array(
			'label'	=> $cms->trans("Wins / Losses"),
			'type'	=> 'dual_bar',
			'value'	=> array(
				'pct1'	 	=> $pct1,
				'pct2'	 	=> $pct2,
				'title1'	=> $team['wins'] . ' ' . $cms->trans('Wins') . ' (' . $pct1 . '%)',
				'title2'	=> $team['losses'] . ' ' . $cms->trans('Losses') . ' (' . $pct2 . '%)',
				'color1'	=> '00cc00',
				'color2'	=> 'cc0000',
				'width'		=> 130
			)
		);

		$cms->filter('left_column_actions', $actions);
		$actions['games_played']['value'] = dual_bar( $actions['games_played']['value'] );
		

		$theme->assign(array(
			'mod_actions' => $actions,
			'mod_actions_title' => $cms->trans("Record Profile"),
		));
		$output = $theme->parse($tpl);
		$theme->assign('team_left_column_mod', $output);
	}
}

function division_left_column_mod(&$division, &$theme) {
	$this->team_left_column_mod($division, $theme);
	$theme->assign('division_left_column_mod', $theme->get_template_vars('team_left_column_mod'));
	
/*	global $cms;
	static $strings = array();
	if (!$strings) {
		$strings = array(
			'wins'			=> $cms->trans("Wins"),
			'losses'			=> $cms->trans("Losses"),
		);
	}
	$tpl = 'division_left_column_mod';
	if ($theme->template_found($tpl, false)) {
        
		$actions = array();
		$games_played = $division['games_played'];
		if ($games_played) {
			$pct1 = sprintf('%0.02f', $division['wins'] / $games_played * 100);
			$pct2 = sprintf('%0.02f', $division['losses'] / $games_played * 100);
		} else {
			$pct1 = $pct2 = 0;
		}
		
		$actions['games_played'] = array(
			'label'	=> $cms->trans("Wins / Losses"),
			'type'	=> 'dual_bar',
			'value'	=> array(
				'pct1'	 	=> $pct1,
				'pct2'	 	=> $pct2,
				'title1'	=> $division['wins'] . ' ' . $cms->trans('Wins') . ' (' . $pct1 . '%)',
				'title2'	=> $division['losses'] . ' ' . $cms->trans('Losses') . ' (' . $pct2 . '%)',
				'color1'	=> '00cc00',
				'color2'	=> 'cc0000',
				'width'		=> 130
			)
		);

		$cms->filter('left_column_actions', $actions);
		$actions['games_played']['value'] = dual_bar( $actions['games_played']['value'] );
		

		$theme->assign(array(
			'mod_actions' => $actions,
			'mod_actions_title' => $cms->trans("Division Record Profile"),
		));
		$output = $theme->parse($tpl);
		$theme->assign('division_left_column_mod', $output);
	}*/
}

} // end of ps::ss::bb

?>
