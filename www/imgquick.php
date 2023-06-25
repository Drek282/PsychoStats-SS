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
 *	Version: $Id: imgquick.php 367 2008-03-17 17:47:45Z lifo $
 */

/*
	Displays a graph that shows the win_percent history of the player ID given
*/
define("PSYCHOSTATS_PAGE", true);
include(__DIR__ . "/includes/imgcommon.php");
include(JPGRAPH_DIR . '/jpgraph_line.php');
include(JPGRAPH_DIR . '/jpgraph_regstat.php');

$team_id = is_numeric($_GET['id']) ? $_GET['id'] : 0;
$_GET['v'] ??= null;
$var = in_array(strtolower($_GET['v']), array('win_percent','wins')) ? strtolower($_GET['v']) : 'win_percent';
$_GET = array( 'id' => $team_id, 'v' => $var );

//list($base,$ext) = explode('.', GenImgName());
//$imgfilename = $base . "_" . $team_id . '.' . $ext;
$imgfilename = 'auto';
$datay = array();
$datax = array();
$labels = array();
$sum = 0;
$avg = 0;
$interval = 0;
$minlimit = 0;
$maxlimit = 0;

$smooth = 3;
$max = 10;	// seasons to include in graph

$imgconf = array();
$q = array();

if (!isImgCached($imgfilename)) {
	$imgconf = array();
	//$imgconf = load_img_conf();
	$q =& $imgconf['quickimg'];

	$field = 'win_percent';
	$ps->db->query("SELECT season,$field FROM $ps->t_team_adv WHERE team_id='" . $team_id . "' ORDER BY season");
	$i = 1;
	$skip = $ps->db->num_rows(0) - $max;
	if ($skip < 0) $skip = 0;
	while (list($season,$win_percent) = $ps->db->fetch_row(0)) {
		if ($skip >= $i ) {
			$i++;
			continue;
		}
		$win_percent = round($win_percent, 3);
		$sum += $win_percent;
		$datay[] = $win_percent;
		$datax[] = $i++;
		$labels[] = $season;
	}

	// DEBUG
/*
	while (count($data) < 30) {
		$totalforday = rand(5000,10000);
		$sum += $totalforday;
		$data[] = $totalforday;
		$labels[] = $labels[0];
	}
/**/
}

// Not enough data to produce a proper graph
// jpgraph will crash if we give it an empty array
if (!count($datay)) {
	$sum = 0;
	$datay[] = 0;
} elseif (count($datay) == 1) {
	$datay[1] = $datay[0];
	$datax[1] = $datax[0] + 1;
}

// calculate the average of our dataset
if (count($datay)) {
	$avg = $sum / count($datay);

	if ($var == 'win_percent') {
		//$interval = imgdef($q['interval'], 3000);
		$q['interval'] ??= 0.1;
		$interval = $q['interval'];
	}

	if ($interval) {
		$minlimit = floor(min($datay) / $interval) * $interval;
		$maxlimit = ceil(max($datay) / $interval) * $interval;
	}
}

// Setup the graph.
//$graph = new Graph(imgdef($q['width'], 287), imgdef($q['height'], 180) , $imgfilename, CACHE_TIMEOUT);
$graph = new Graph($q['width'] = 287, $q['height'] = 180, $imgfilename, CACHE_TIMEOUT);
if ($interval) {
	$graph->SetScale("textlin", $minlimit, $maxlimit);
} else {
	$graph->SetScale("textlin");
}
$graph->SetMargin(45,10,10,20);
$graph->title->Set($q['frame']['title']['_content'] = 'Quick History');

// Define yaxis label 
$graph->yaxis->SetLabelFormat('%.3f');

$q['frame']['xgrid']['show'] ??= null;
if (count($datay)<2 or $q['frame']['xgrid']['show'] != true) {
	$graph->xaxis->Hide();
}
$graph->xaxis->HideLabels();
$graph->xaxis->HideTicks();

$graph->ygrid->SetFill((bool)$q['frame']['ygrid']['show'] = true,
	$q['frame']['ygrid']['color1'] = 'whitesmoke',
	$q['frame']['ygrid']['color2'] = 'azure2'
);
$graph->ygrid->Show((bool)$q['frame']['ygrid']['show'] = true);

$font1 = constant($q['frame']['title']['font'] = 'FF_FONT1');
$legendfont = constant($q['legend']['font'] = 'FF_FONT0');
$graph->title->SetFont($font1, FS_BOLD);
$graph->yaxis->title->SetFont($font1,FS_BOLD);
$graph->legend->SetFont($legendfont,FS_NORMAL);
#$graph->xaxis->title->SetFont($font1,FS_BOLD);
#$graph->xaxis->SetFont(FF_FONT0,FS_NORMAL);

$graph->SetMarginColor($q['frame']['margin'] = '#d7d7d7'); 
$graph->SetFrame(true,$q['frame']['color'] = 'gray', $q['frame']['width'] = 1); 
//if (imgdef($q['antialias'], false)) $graph->img->SetAntiAliasing();

$s = new Spline($datax, $datay);
list($x,$y) = $s->Get(count($datay) * $smooth);

$p1 = new LinePlot($y);
$p1->SetLegend('Win %');
$p1->SetWeight($q['frame']['plot'][0]['weight'] = 1);
$p1->SetFillColor($q['frame']['plot'][0]['color'] = 'blue@0.90');

$avg = intval($avg);
if ($avg) {
	for ($i=0; $i < count($datay) * $smooth; $i++) {
		$avgdata[] = $avg;
	}

	$p2 = new LinePlot($avgdata);
//	$p2->SetStyle('dashed');
	$p2->SetLegend('Historical Seasons');
//	$p2->SetLegend($q['frame']['plot'][1]['title'] = 'Average');
	$p2->SetWeight($q['frame']['plot'][1]['weight'] = 2);
	$p2->SetColor($q['frame']['plot'][1]['color'] = 'khaki4');
//	$p2->SetBarCenter();
	$graph->Add($p2);
}

$graph->legend->SetAbsPos(
	$q['legend']['x'] = 5,
	$q['legend']['y'] = 5,
	$q['legend']['halign'] = 'right',
	$q['legend']['valign'] = 'top'
);
$graph->legend->SetFillColor($q['legend']['color'] = 'lightblue@0.5');
$graph->legend->SetShadow(
	$q['legend']['shadow']['color'] = 'gray@0.5',
	$q['legend']['shadow']['width'] = 2
);

$graph->Add($p1);

if (count($datay) < 2) {
	$t = new Text("Not enough history\navailable\nto chart graph");
	$t->SetPos(0.5,0.5,'center','center');
	$t->SetFont(FF_FONT2, FS_BOLD);
	$t->ParagraphAlign('centered');
	$t->SetBox('lightyellow','black','gray');
	$t->SetColor('orangered4');
	$graph->yaxis->HideLabels();
	$graph->xaxis->HideLabels();
	$graph->legend->Hide();
	$graph->AddText($t);
}

stdImgFooter($graph);
$graph->Stroke();

?>
