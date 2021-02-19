<?php ob_start();?><?php
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png
//THIS VERSION JUST SHOWS DURATION NOT ROTATED, BUT WITH AS LITTLE CHANGE AS POSS

require_once './jpgraph/src/jpgraph.php';
require_once './jpgraph/src/jpgraph_radar.php';
//echo __DIR__;
//require_once '../../config.php';
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything

$util_dates = new \block_obu_learnanalytics\util\date_functions();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // The request is using the POST method
    $currentWeek = $_POST["currentWeek"] ?? "";
    if ($currentWeek == "" || $currentWeek == null) {
        $current = $util_dates->get_current_week();
    } else {
        $current = $util_dates->json_2_current_week($currentWeek);
    }
    $sid = $_POST["studentNumber"] ?? ""; //TODO error handling if no student id
    $sname = $_POST["studentName"] ?? "";
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

if ($sid == "") {
    exit("Brookes Learning Analytics - Student Number not passed");
}

// OK So now we shouldn't let a non tutor to anyone else's data
if ($laRole == "STUDENT" && $sid != $USER->username) {
    die("Permission to others students data denied");
}

// Now hard code some results
// Get the data
$titles = array('Moodle','Rank','eLibrary','Consistency','Trend');
$data = array(44, 98, 70, 90, 42);

// Create graph instances
$height = $width = 240;
$graph = new RadarGraph($width, $height, "auto");
$graph->SetScale('lin');
// Little effect that I can see - $graph->img->SetMargin(50, 50, 50, 50); // 1st margin is left, Last margin 

$graph->title->Set('Engagement Summary');
$graph->SetTitles($titles);
$graph->SetCenter(0.5,0.5);             // Fraction of width and height
$graph->HideTickMarks();
//$graph->SetColor('lightgreen@0.7');
$graph->axis->SetColor('darkgray');
$graph->grid->SetColor('darkgray');
$graph->grid->Show();
 
//$graph->axis->title->SetFont(FF_ARIAL,FS_NORMAL,12);
$graph->axis->title->SetMargin(1);      // Controls how far the Titles are from Radar
$graph->SetGridDepth(DEPTH_BACK);
$graph->SetSize(0.6);                   // 0.6 = 60% of min($weight,$height) and indicates the length of the axis. 
 
$plot = new RadarPlot($data);
//$plot->SetColor('red@0.2');
$plot->SetLineWeight(1);
//$plot->SetFillColor('red@0.7');
 
$plot->mark->SetType(MARK_IMG_SBALL,'red');
 
$graph->Add($plot);

// Return the graph, encode the returned image
// TODO move this to a common routine and use from here and tutor_graph, but watch out for chart number
// Prepare the image
$gdImgHandler = $graph->Stroke(_IMG_HANDLER);
// Use the output buffer and imagepng
ob_start();
imagepng($gdImgHandler); // Write to OB, I know this works because if I save it to a file it's OK
$image_data = ob_get_contents(); // grab the contents of the buffer
ob_end_clean(); // Empty the buffer as we have what we want
// Now encode and return it
$contentType = 'image/png';
echo "data:$contentType;base64," . base64_encode($image_data);

exit;
