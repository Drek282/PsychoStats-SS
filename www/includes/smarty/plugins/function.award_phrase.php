<?php

function smarty_function_award_phrase($params, &$smarty) {
	global $ps, $cms;
	$award = $params['award'];		// combined array of the award and team data
	$phrase = $cms->trans($award['phrase']);

	// if 'desc' is true then we print the award description
	$params['desc'] ??= null;
	if ($params['desc']) {
		$phrase = $cms->trans($award['description']);
		if (empty($phrase)) {
			$phrase = $cms->trans("No description available");
		}
	}

	// create some dynamic values for this award
	$award['topteamvalue'] = $ps->award_format($award['topteamvalue'], $award['format']);
	$award['link'] = psss_table_team_link($award['topteamname'], $award);

	$tokens = array(
		'award' 		=> &$award,
		'team' 			=> &$award,
	);

	return simple_interpolate($phrase, $tokens);
}
?>
