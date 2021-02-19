<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
if (!$laRole == "STUDENT") {
    die("Permission Denied");
}
die("Not Implemented (Yet)");
// End of protective code
?>
<?php
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png
//if (count($_GET) == 0) {
//    return '';      // If there are no parameters we don't know what to do, occurs when page isn't visible
//}

require_once './jpgraph/src/jpgraph.php';
require_once './jpgraph/src/jpgraph_line.php';

$util_dates = new \block_obu_learnanalytics\util\date_functions();

$posted = true;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $programme = $_POST["programme"];
    $ids2chart = $_POST['ids2Chart'];
    $width = $_POST['width'] ?? 500;
    $currentWeek = $_POST["currentWeek"];
    $current = $util_dates->json_2_current_week($currentWeek);
} else {
    exit("Brookes Learning Analytics - GET not supported for tutor graph");
}
if ($programme != '') {
    //$studentsGraphData = $db_tutor->students_comparitive_graphdata($programme, $ids2chart, 10, $current);

    // Now process the results
    if ($width == 0) {
        $width = 500;
    }
    $height = ($width > 600) ? 500 : 400;

    // Create a graph instance
    $graph = new Graph($width, $height, "auto");
    $graph->img->SetMargin(80, 5, 5, 180); // 1st margin is left, Last margin is bottom so stops plot legend overwriting scale

    // Specify what scale we want to use,
    // text = text scale for the X-axis
    // int = integer scale for the Y-axis
    $graph->SetScale('textint');
    // Setup a title for the graph
    $graphTitle = "Comparison of total duration";
    $graph->title->Set($graphTitle);

    $weeks = [];
    $count = 0;
    foreach ($studentsGraphData as $studentNumber => $studentData) {
        // So each student get's a plot
        $plotCounts = [];

        // Now loop through the data
        $data = $studentData["data"];
        foreach ($data as $weekKey => $weekValues) {
            $plotCounts[] = $weekValues["duration"] ?? 0;
            // Once through we need to capture the W/C dates
            if ($count == 0) {
                $weeks[] = $weekValues["first_day_week"];
            }
        }

        // Now create a line plot and add it
        $plot = new LinePlot($plotCounts);
        $plot->SetWeight(22); // Doesn't seem to do anything
        //$plot->SetColor("blue");
        $legend = explode(' ', $studentData["name"], 2)[0];
        //$legend = $studentData["name"];
        $plot->SetLegend($legend); // Appears over months
        $graph->Add($plot);
        $count++;

    }

    // Setup titles and X-axis labels
    // not important and clashes with axis labels $graph->xaxis->title->Set('Week');
    $graph->xaxis->SetLabelAngle(50);
    $graph->xaxis->SetTickLabels($weeks);

    // Setup Y-axis title
    // not needed and clashes with labels$graph->yaxis->title->Set($chartType);
    $graph->yscale->ticks->SupressZeroLabel(true); // Don't Show 0 label on Y-axis

    // // Create the Average line
    // $p2 = new LinePlot($plot2Counts);
    // $p2->SetWeight(1);
    // $p2->SetColor("orange");
    // $p2->SetLegend("Cohort Mean Average");
    // $graph->Add($p2);

    // Return the graph, if it was posted we need to encode the returned image
    if ($posted) {
        $contentType = 'image/png';
        // At one point I could only get it working by flushing the buffer
        // ob_start();
        // ob_end_clean();

        // So prepare the image
        $gdImgHandler = $graph->Stroke(_IMG_HANDLER);
        // Use the output buffer and imagepng
        ob_start();
        imagepng($gdImgHandler); // Write to OB, I know this works because if I save it to a file it's OK
        //        $graph->img->Stream();            // Output from this looks the same to the naked eye as imagepng
        $image_data = ob_get_contents(); // grab the contents of the buffer
        ob_end_clean(); // Empty the buffer as we have what we want
        // Now encode and return it
        echo "data:$contentType;base64," . base64_encode($image_data);
    } else {
        $graph->Stroke();
    }
}
