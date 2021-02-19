<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
if ($laRole == "STUDENT") {
    die("Permission Denied");
}
// End of protective code
?>
<?php
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png
//if (count($_GET) == 0) {
//    return '';      // If there are no parameters we don't know what to do, occurs when page isn't visible
//}

require_once './jpgraph/src/jpgraph.php';
require_once './jpgraph/src/jpgraph_scatter.php';

$util_dates = new \block_obu_learnanalytics\util\date_functions();

// TODO check it's been called from page
$posted = true;
$excludeZeros = false;           // Hard coded for now
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $programme = $_POST["programme"];
    $studyStage = $_POST["sStage"] ?? '*';
    $ids2chart = $_POST['ids2Chart'];
    $currentWeek = $_POST["currentWeek"];
    if ($currentWeek == "" || $currentWeek == null) {
        // Posted from the student dashboard doesn't currently have a date, so just get this week
        $current = $util_dates->get_current_week();
    } else {
        $current = $util_dates->json_2_current_week($currentWeek);
    }
    $durationCode = $_POST["duration"] ?? '1wk';     // 1wk, 4wks or sem(ester)
} else {
    exit("Brookes Learning Analytics - GET not supported for cohort chart");
}
if ($programme != '') {             // Just a safety check, should never get called with no programme
    $thisStudyStage = $studyStage;
    global $USER;

    $weeks = 1;
    switch ($durationCode) {
        case '4wks':
            $weeks = 4;
        break;

        case 'sem':
            $weeks = 99;        //TODO calculate it
        break;
        
        default:
        $weeks = 1;
    break;
    }

    //$studentsData = $db_cohort->engagement_and_marks($programme, $thisStudyStage, $ids2chart, $current, $weeks, false);

    // Now process the results
    $width = 500;
    $height = 400;

    // Create a graph instance
    $graph = new Graph($width, $height, "auto");
    $plotsx = array();
    $plotsy = array();

    foreach ($studentsData as $studentNumber => $studentData) {
        // Ignore any with undefined mark
        if (isset($studentData["average_mark_percentage"])) {
            $plotsx[] = $studentData["engagement_score"];
            $plotsy[] = $studentData["average_mark_percentage"];
        }
    }

    $graph->img->SetMargin(80, 15, 5, 80); // 1st margin is left, Last margin is bottom so stops plot legend overwriting scale
    $graph->SetScale('linlin');
    $graphTitle = "Students Marks v Engagement";
    $graph->title->Set($graphTitle);

    $sp1 = new ScatterPlot($plotsy, $plotsx);
    $graph->Add($sp1);

    $graph->xaxis->SetTitle('Engagement', "middle");
    $graph->yaxis->SetTitle('Mark', "center");
    $graph->yaxis->SetTitleMargin(30);

    // Return the graph, we need to encode the returned image
    $contentType = 'image/png';
    // Prepare the image
    $gdImgHandler = $graph->Stroke(_IMG_HANDLER);
    // Use the output buffer and imagepng
    ob_start();
    imagepng($gdImgHandler); // Write to OB, I know this works because if I save it to a file it's OK
    $image_data = ob_get_contents(); // grab the contents of the buffer
    ob_end_clean(); // Empty the buffer as we have what we want
    // Now encode and return it
    echo "data:$contentType;base64," . base64_encode($image_data);
}
