<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
?>
<?php
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png
//if (count($_GET) == 0) {
//    return '';      // If there are no parameters we don't know what to do, occurs when page isn't visible
//}

require_once './jpgraph/src/jpgraph.php';
require_once './jpgraph/src/jpgraph_line.php';
require_once './jpgraph/src/jpgraph_bar.php';

$util_dates = new \block_obu_learnanalytics\util\date_functions();
$curl_common = new \block_obu_learnanalytics\curl\common();
$data_afuncs = new \block_obu_learnanalytics\data\array_functions();

// TODO check it's been called from page
$posted = true;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $programme = $_POST["programme"];
    $studyStage = $_POST["sStage"] ?? '*';
    $studyType = $_POST["sType"] ?? '*';
    $currentWeek = $_POST["currentWeek"];
    $studentNumber = $_POST["studentNumber"] ?? "";
    $studentName = $_POST["studentName"] ?? "Selected Student";
    $studentDashboard = $_POST["studentDashboard"] ?? "true";
    $studentDashboard = ($studentDashboard == "true");
    if ($currentWeek == "" || $currentWeek == null) {
        // Posted from the student dashboard doesn't currently have a date, so just get this week
        $current = $util_dates->get_current_week();
    } else {
        $current = $util_dates->json_2_current_week($currentWeek);
    }
    $durationCode = $_POST["duration"] ?? '13wks';     // 1wk, 4wks, 13wks or sem(ester)
} else {
    exit("Brookes Learning Analytics - GET not supported for cohort chart");
}
if ($programme != '') {             // Just a safety check, should never get called with no programme
    $thisStudyStage = $studyStage;
    $thisSid = $studentNumber;
    $thisSid = "";      //TODO balance numbers with this set
    // If we have come from the students page then there won't be a study stage, so get it
    if ($studentDashboard) {
        $params = "student/details/$USER->username/";       // Temp, this should be passed in to save trip
        $studentDetails = $curl_common->send_request($params);
        if ($studentDetails != null && $studentDetails["study_stage"] != '') {
            $studyStage = $studentDetails["study_stage"];
        }
        $studentName = "You";
    }

    $weeks = $util_dates->interprete_duration_code($durationCode);
    $simpleCurrent = $util_dates->createSimpleCurrentParam($current);
    $enc_pgm = htmlspecialchars(urlencode(str_replace('/','~',$programme)));
    $params = "tutor/cohorteng/$enc_pgm/$studyStage/$studyType/$weeks/$simpleCurrent/";
    $studentsData = $curl_common->send_request($params);
    
    $excludeZeros = false;
    $mean = $data_afuncs->calculate_mean($studentsData, "engagement_score", !$excludeZeros);
    $median = $data_afuncs->calculate_median($studentsData, "engagement_score", !$excludeZeros);
    $quartiles = $data_afuncs->calculate_quartile_positions($studentsData, "engagement_score", !$excludeZeros);

    // Now process the results
    $width = 600;       // Just let it resize
    $height = 250;

    // Create a graph instance
    $graph = new Graph($width, $height, "auto");

    $plotCountsQ1 = $plotCountsQ4 = $plotCountsIQ = $plotCountsStudent = $meanPlotCounts = $medianPlotCounts = [];
    // Now loop through the data, we want a straight line for averages
    $thisEngagement = $count = 0;
    foreach ($studentsData as $studentNumber => $studentData) {
        $meanPlotCounts[] = $mean;
        $medianPlotCounts[] = $median;
        switch (true) {
            case ($studentNumber == $thisSid):
                $plotCountsQ1[] = 0;
                $plotCountsIQ[] = 0;
                $plotCountsQ4[] = 0;
                $plotCountsStudent[] = $studentData["engagement_score"] ?? 0;
                $thisEngagement = $studentData["engagement_score"] ?? 0;
                break;
            case ($count < $quartiles["q1_pos"]):
                $plotCountsQ1[] = $studentData["engagement_score"] ?? 0;
                $plotCountsIQ[] = 0;
                $plotCountsQ4[] = 0;
                $plotCountsStudent[] = 0;
                break;
            case ($count > $quartiles["q3_pos"]):
                $plotCountsQ4[] = $studentData["engagement_score"] ?? 0;
                $plotCountsIQ[] = 0;
                $plotCountsQ1[] = 0;
                $plotCountsStudent[] = 0;
                break;
            default:
                $plotCountsIQ[] = $studentData["engagement_score"] ?? 0;
                $plotCountsQ1[] = 0;
                $plotCountsQ4[] = 0;
                $plotCountsStudent[] = 0;
                break;
        }
        $count++;
    }
    //$meanPlotCounts[] = $mean;
    //$medianPlotCounts[] = $median;

    $graph->img->SetMargin(80, 5, 5, 50); // 1st margin is left, Last margin is bottom so stops plot legend overwriting scale

    // Specify what scale we want to use,
    // text = text scale for the X-axis
    // int = integer scale for the Y-axis
    $graph->SetScale('textint');
    // Setup a title for the graph
    $graphTitle = "Student Engagement Distribution";
    if ($studentDashboard) {
        $graphTitle .= "  Week Commencing " . $current["first_day_week"]->format("d-M-Y");
    }
    $graph->title->Set($graphTitle);
    // Not on Poodle $graph->title->SetFont(FF_ARIAL, FS_BOLD, 16);      // Because at the size we are creating we need a larger font
    // See http://blog.camilord.com/2013/12/06/solution-jpgraph-error-25128-the-function-imageantialias-is-not-available-in-your-php-installation-use-the-gd-version-that-comes-with-php-and-not-the-standalone-version/#:~:text=1-,JpGraph%20Error%3A%2025128%20The%20function%20imageantialias()%20is%20not%20available,and%20not%20the%20standalone%20version.&text=install%20the%20required%20library,or%20simply%20comment%20certain%20line
    // but I'm worried about the writable cache folder

    $graph->yscale->ticks->SupressZeroLabel(true); // Don't Show 0 label on Y-axis
    // Now create plots and add them
    // 1st Quartile
    $barplotq1 = new BarPlot($plotCountsQ1);
    // Interquartile range
    $barplotiq = new BarPlot($plotCountsIQ);
    // 4th Quartile
    $barplotq4 = new BarPlot($plotCountsQ4);
    if ($thisSid == "") {
        // Seems legend appears in reverse sequence, so load 4 then iq then 1 so it's Red/Amber/Green
        $allbarplots = new AccBarPlot(array($barplotq4, $barplotiq, $barplotq1));
    } else {
        $barplotstudent = new BarPlot($plotCountsStudent);
        $allbarplots = new AccBarPlot(array($barplotstudent, $barplotq4, $barplotiq, $barplotq1));
    }
    $graph->Add($allbarplots);
    // Now set the colours, because if you do it before the add they get overwritten
    $barplotq1->SetLegend("Lowest Quartile");
    //$barplotq1->SetFont(FF_ARIAL, FS_NORMAL, 12);     //Blows up
    $barplotq1->SetFillColor('#ff0000');       //Red
    $barplotq1->SetColor('#ff0000');           // border
    $barplotiq->SetLegend("Middle Quartiles");
    $barplotiq->SetFillColor('#ffbf00');        //Amber
    $barplotiq->SetColor('#ffbf00');           // border
    $barplotq4->SetLegend("Top Quartile");
    //$barplotq4->SetWeight(8);           // black border
    $barplotq4->SetFillColor('#33a532');        // Benjamin Moore Traffic Light Green
    $barplotq4->SetColor('#33a532');           // border
    if ($thisSid != "") {
        $fthisEng = sprintf('%.0f', $thisEngagement);
        $barplotstudent->SetLegend("{$studentName} ({$fthisEng})");
        $barplotstudent->SetFillColor('#0000ff');
        $barplotstudent->SetColor('#0000ff');           // border
    }

    $meanlplot = new LinePlot($meanPlotCounts);
    //$meanlplot->SetColor("green");
    $meanlplot->SetWeight(8);
    $fmean = sprintf('%.0f', $mean);
    $meanlplot->SetLegend("Mean Average ({$fmean})");
    $graph->Add($meanlplot);

    $medianlplot = new LinePlot($medianPlotCounts);
    //$medianlplot->SetColor("orange");
    $medianlplot->SetWeight(8);
    $fmedian = sprintf('%.0f', $median);
    $medianlplot->SetLegend("Median Average ({$fmedian})");
    $graph->Add($medianlplot);

    $graph->xaxis->HideLabels();
    //$graph->yaxis->HideLabels();

    // Return the graph, as it was posted we need to encode the returned image
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
}
