<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {statgradient} function plugin
 *
 * Type:     function<br>
 * Name:     statgradient<br>
 * Purpose:  returns the color gradient to use for a team stat on the compare.php page
 * @version  1.0
 * @param array
 * @param Smarty
 */
function smarty_function_statgradient($args, &$smarty)
{
	global $gradientlist,$comparelist;
	$args += array(
		'var'		=> '',
		'team_id'		=> '',
		'stat'		=> '',
		'reverse'	=> false,
	);
	$color = '#000000';
	if ($args['stat'] and $args['team_id'] and array_key_exists($args['stat'], $comparelist)) {
		$list = $comparelist[$args['stat']];
		$uniq = count(array_unique($list));
		$idx = $list[$args['team_id']];
//		$color = sprintf("#%06x", $gradientlist[$idx]);
		$color = $gradientlist[$idx];
	}

	if (!$args['var']) return $color;
	$smarty->assign($args['var'], $color);
}

?>
