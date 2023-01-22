<?php
/**
 * Smarty plugin	-- Stormtrooper
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty PS3 rankchange function plugin
 *
 * Type:     function<br>
 * Name:     rankchange<br>
 * Purpose:  outputs the proper img tag for the change in rank
 * @param string
 * @return string
 */
function smarty_function_standings($val, &$smarty)
{
	return gb_status($val);
}

?>
