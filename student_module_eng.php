<?php ob_start();?><?php
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png

require_once './jpgraph/src/jpgraph.php';
require_once './jpgraph/src/jpgraph_bar.php';
//echo __DIR__;
//require_once '../../config.php';
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything

$util_dates = new \block_obu_learnanalytics\util\date_functions();

$posted = true;
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
    exit("Brookes Learning Analytics - GET not supported for student modules");
}

// OK So now we shouldn't let a non tutor to anyone else's data
if ($laRole == "STUDENT" && $sid != $USER->username) {
    die("Permission to others students data denied");
}

$simpleCurrent = $util_dates->createSimpleCurrentParam($current);
$params = "student/modgraphdata/$sid/$simpleCurrent/";
$curl_common = new \block_obu_learnanalytics\curl\common();
$studentModuleData = $curl_common->send_request($params);
if ($studentModuleData == null) {
    header('Content-type: application/json');
    $http_status = $curl_common->get_last_http_status();
    if ($http_status == 204) {
        // Empty data set is a success of sorts
        $json = json_encode(array('success' => true, 'http_status' => $http_status));
        if ($json) {
            echo $json;
        } else {
            $json_error = json_last_error_msg();
            echo json_encode(array('success' => false, 'json_error' => "{$json_error}"));
        }
    } else {
        $json = json_encode(array('success' => false, 'http_status' => $http_status));
        if ($json) {
            echo $json;
        } else {
            $json_error = json_last_error_msg();
            echo json_encode(array('success' => false, 'json_error' => "{$json_error}"));
        }
    }
    return;
}
if (count($studentModuleData) != 2) {
    header('Content-type: application/json');
    $json = json_encode(array('success' => false, 'http_status' => $http_status
            , 'message' => 'Unexpected data structure returned'));
    if ($json) {
        echo $json;
    } else {
        $json_error = json_last_error_msg();
        echo json_encode(array('success' => false, 'json_error' => "{$json_error}"));
    }
    return;
}
$studentModules = $studentModuleData["Modules"];
$studentGraphData = $studentModuleData["GraphData"];

// Now process the results, module by module
$firstMod = true;
$weeks = [];
foreach ($studentModules as $course_id => $module) {
    $plotCounts = array();
    // Now week by week
    foreach ($studentGraphData as $key => $row) {
        if ($firstMod) {
            $weeks[] = $row["first_day_week"];
        }
        // Now look for module
        $modules = $row["modules"];
        if (count($modules) == 0) {
            // No point searching
            $plotCounts[] = 0;
        } else {
            // Try and find the course in the week
            if (array_key_exists($course_id, $modules)) {
                $entry = $modules[$course_id];
                $plotCounts[] = $entry["duration_total"];
            } else {
                $plotCounts[] = 0;
            }
        }
    }
    // Now store the graph plots back in modules
    $module["plots"] = $plotCounts;
    $studentModules[$course_id] = $module;
    $firstMod = false;
}

// Now we've prepared the data create the graph
// Width and height of the graph
$width = 800;
$height = 420;

// Setup a title for the graph
$graphTitle = "Student Duration by Module (Minutes)";
$plotType = "bar";

// Create a graph instance
$graph = new Graph($width, $height, "auto");

$graph->img->SetMargin(60, 5, 5, 140); // 1st margin is left, Last margin is bottom so stops plot legend overwriting scale

// Specify what scale we want to use,
// text = text scale for the X-axis
// int = integer scale for the Y-axis
$graph->SetScale('textint');

$graph->title->Set($graphTitle);

// Setup titles and X-axis labels
// not important and clashes with axis labels $graph->xaxis->title->Set('Week');
$graph->xaxis->SetLabelAngle(50);
$graph->xaxis->SetTickLabels($weeks);

// Setup Y-axis title
// not needed and clashes with labels$graph->yaxis->title->Set($chartType);
$graph->yscale->ticks->SupressZeroLabel(true); // Don't Show 0 label on Y-axis
// Create the plots
$plots = array();
foreach ($studentModules as $course_id => $module) {
    $plot = new BarPlot($module["plots"]);
    // let it do it$plot->SetColor("blue");
    $plot->SetLegend($module["course_shortname"]);
    $plots[] = $plot;
}

//$gbplot = new GroupBarPlot($plots);       // Alongside
$gbplot = new AccBarPlot($plots);           // Stacked
$graph->Add($gbplot);

// Return the graph, as it was posted we need to encode the returned image
// So prepare the image
$gdImgHandler = $graph->Stroke(_IMG_HANDLER);
// Use the output buffer and imagepng
ob_start();
imagepng($gdImgHandler); // Write to OB, I know this works because if I save it to a file it's OK
//        $graph->img->Stream();            // Output from this looks the same to the naked eye as imagepng
$image_data = ob_get_contents(); // grab the contents of the buffer
ob_end_clean(); // Empty the buffer as we have what we want
// Now encode and return it
$contentType = 'image/png';
echo "data:$contentType;base64," . base64_encode($image_data);
