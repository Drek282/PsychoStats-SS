<?php
/**
 * Smarty plugin	-- Stormtrooper
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty PS3 negpos function plugin
 *
 * Type:     function<br>
 * Name:     negpos<br>
 * Purpose:  returns a different css class for below 0 or higher
 * @param string
 * @return string
 */
function smarty_function_negpos($val, &$smarty)
{
	return neg_pos($val);
}

?>
