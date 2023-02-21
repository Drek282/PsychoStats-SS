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
 * Name:     tablesearchlink<br>
 * Purpose:  returns a help search link
 * @param string
 * @return string
 */
function smarty_function_tablesearchlink($val, &$smarty)
{
	return psss_table_search_link($val);
}

?>
