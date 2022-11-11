<?php
/***
Plugin to remove hit location, damage, friendly fire kills, and rounds from stats.
File: plugins/mod_hldff_ns.php

$Id$
***/

class mod_nodiv extends PsychoPlugin {
var $version = '1.0';
var $errstr = '';

function load(&$cms) {
$cms->register_filter($this, 'teams_table_object');
return true;
}

function install(&$cms) {
$info = array();
$info['version'] = $this->version;
$info['description'] = "Plugin to remove division names from stats.";
return $info;
}

// index.php
function filter_teams_table_object(&$table, &$cms, $args = array()) {
$table->remove_columns(array('divisionname'));
}

} // end of mod_hldff_natural


?>
