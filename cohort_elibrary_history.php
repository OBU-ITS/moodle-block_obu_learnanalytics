<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
die("Not yet implemented");
?>
<?php
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png
//if (count($_GET) == 0) {
//    return '';      // If there are no parameters we don't know what to do, occurs when page isn't visible
//}

require_once './jpgraph/src/jpgraph.php';
require_once './jpgraph/src/jpgraph_bar.php';

$util_dates = new \block_obu_learnanalytics\util\date_functions();

// TODO check it's been called from page
$posted = true;
$excludeZeros = true;           // Hard coded for now
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $programme = $_POST["programme"];
    $ids2chart = $_POST['ids2Chart'];
    $width = $_POST['width'] ?? 600;
    $currentWeek = $_POST["currentWeek"];
    if ($currentWeek == "" || $currentWeek == null) {
        // Posted from the student dashboard doesn't currently have a date, so just get this week
        $current = $util_dates->get_current_week();
    } else {
        $current = $util_dates->json_2_current_week($currentWeek);
    }
    $durationCode = $_POST["duration"];     // 1wk, 4wks or sem(ester)
} else {
    exit("Brookes Learning Analytics - GET not supported for elibrary chart");
}
if ($programme != '') {             // Just a safety check, should never get called with no programme
    global $USER;
    $thisSid = $USER->username;     // May clear it later
    // If we have come from the students page then there won't be any student ids, so calculate them
    if ($ids2chart == "*") {
        $studentDashboard = true;
    //$excludeZeros = false;
    } else {
        $studentDashboard = false;
        $thisSid = "";
    }
    $weeks = $util_dates->interprete_duration_code($durationCode);
    //$elibraryHistory = $db_cohort->cohort_elibrary_history($ids2chart, $current, $weeks);
    $plotCounts = [];
    $domains = [];
    // Now loop through the data
    $count = 0;
    foreach ($elibraryHistory as $row) {
        $plotCounts[] = $row["minutes"] ?? 0;
        $domains[] = $row["resource_name"];
        if (++$count >= 10) {
            break;
        }
    }
    // jpgraph seems to get very upset if you only have a couple of rows
    while ($count < 4) {
        // Add some empty 0 plots
        $plotCounts[] = 0;
        $domains[] = $count == 0 ? 'No Usage for Period' : '';
        $count++;
    }

    // Create a graph instance
    $height = count($domains) * 30;
    $graph = new Graph($width, $height, "auto");
    // Setup a title for the graph   (Make sure you leave enough margin for it)
    $graphTitle = "Cohort Library History (Top 10)";
    if ($studentDashboard) {
        $graphTitle .= "  Week Commencing " . $current["first_day_week"]->format("d-M-Y");
    }
    $graph->title->Set($graphTitle);

    // Now create plots and add them
    $barplot = new BarPlot($plotCounts);
    $allbarplots = new AccBarPlot(array($barplot));
    $graph->Add($allbarplots);

    // Specify what scale we want to use,
    // text = text scale for the X-axis
    // int = integer scale for the Y-axis
    $graph->SetScale('textint');                // Even though text will get swapped to yaxis, it's still the xaxis to jpgraph
    // TODO Calculate left margin, and/or Wrap the longer domains
    $graph->Set90AndMargin(450, 50, 40, 30);     // Sets margin as well so don't need $graph->img->SetMargin(80, 5, 80, 80)
    $graph->yscale->ticks->SupressZeroLabel(true); // Don't Show 0 label on Y-axis
    // So you have changed the orientation, so the xaxis is effectively the yaxis now
    //$graph->yaxis->HideLabels();              // This would hide the count
    $graph->xaxis->SetTickLabels($domains);
    //TODO persevere $graph->xaxis->SetLabelSide(SIDE_TOP);

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
