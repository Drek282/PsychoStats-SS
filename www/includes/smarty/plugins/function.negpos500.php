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
 * Name:     negpos500<br>
 * Purpose:  returns a different css class for below .500 or higher
 * @param string
 * @return string
 */
function smarty_function_negpos500($val, &$smarty)
{
	return neg_pos_500($val);
}

?>
