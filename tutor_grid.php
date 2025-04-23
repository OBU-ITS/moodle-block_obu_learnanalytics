<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
require_once(__DIR__ . '/vendor/autoload.php');

$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
if ($laRole == "STUDENT") {
    die("Permission Denied");
}
// End of protective code
?>

<?php
$util_dates = new \block_obu_learnanalytics\util\date_functions();
$data_tutor = new \block_obu_learnanalytics\data\tutor_functions();
//xdebug_break();
// Drop down event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // The request is using the POST method
    $programme = $_POST["programme"];
    $programmeText = $_POST["programmeText"];
    $modLevel = $_POST["modLevel"];
    $studyType = ($_POST["studyType"]);
    $cohortSort = $_POST["cohortSort"];
    $studentSort = $_POST["studentSort"];
    $currentWeek = $_POST["currentWeek"]; // In JSON
    $onlyMyAdvisees = ($_POST["onlyMyAdvisees"] ?? "true");
    $bandingCalcOptions = "MED-30-4"; //($_POST["bandingCalc"]);
    $semester = $_POST["semester"] ?? "";
    $campusCode = $_POST["campusCode"] ?? "*";
    $option = ($_POST["option"]);
    $oldProgramme = $_POST["oldProgramme"] ?? "";
} else {
    exit("Brookes Learning Analytics - GET not supported for tutor grid");
}

$fullDataSet = ($modLevel == "*" && $studyType == "*" && $campusCode == "*");

// So if we've been passed the semester or other instruction, recalculate the date
switch ($option) {
    case 'semester':
        // Nothing todo
        break;
    
    case 'getcurrent':
        $current = $util_dates->get_current_week();
        // NO not needed $dateRecalculated = true;
        break;

    case 'programme':
        // And log the event
        $context = context_system::instance();       // Swapped to using system context as page threw error on Poodle
        $other = array("New" => $programme, "Old" => $oldProgramme);
        $event = \block_obu_learnanalytics\event\tutor_programme_changed::create(array(
            'context' => $context, 'other' => json_encode($other)
        ));
        $event->trigger();
        $current = $util_dates->json_2_current_week($currentWeek);
        $semester = $current["semester"];
        break;
    default:
        $current = $util_dates->json_2_current_week($currentWeek);
        $semester = $current["semester"];
        break;
}

//global $SESSION;
global $USER;
$username = $USER->username;
global $PAGE;
$context = $PAGE->context;

// Log last accessed
//date_default_timezone_set('UTC');     Just use users timezone
$today = new DateTime();
set_user_preference('obula_last_tutor_grid_date', serialize($today));
set_user_preference('obula_last_tutor_grid_pgm', $programme);
set_user_preference('obula_last_tutor_grid_pgm_desc', $programmeText);

$success = true;        // Hopefully
try {

    $simpleCurrent = $util_dates->createSimpleCurrentParam($current);
    // We don't want to filter by academic advisor, because cohort/modLevel averages etc should include all students
    // Had to encode programme as it can have / for example BA/BSH-PKPO, but that wasn't enough because decode happened before htaccess
    // so swap / to ~ (and back in web service)
    $enc_pgm = htmlspecialchars(urlencode(str_replace('/','~',$programme)));
    $params = "tutor/studentsgridv3/$enc_pgm/$bandingCalcOptions/$simpleCurrent/$modLevel/$studyType/*/$semester/$campusCode/";
    $curl_common = new \block_obu_learnanalytics\guzzle\common(); // or call it $guzzle_common, up to you
    $result = $curl_common->send_request($params);
    $studentsComparitives = $result["data"];
    $headings = $result["header"];  
} catch (Exception $e) {
    // Just output it in big bold red, shouldn't happen so no CSS for this
    $html = "<br><b><font size='6'><style='color:red'>Exception from students_comparitive_grid: {$e}</style></font></b>";
    // Now let it send all that back
    $success = false;
}
if ($success && (!isset($studentsComparitives) || count($studentsComparitives) == 0)) {

    $html = "<br><b><font size='+2'><style='color:blue'>No Active Students for this Selection Criteria</style></font></b>";
    // Now let it send all that back
    $success = false;
}

if ($success) {
    // Now work out if we should show the various study_ fields
    $typesCountValues = array_count_values(array_column($studentsComparitives, "study_type"));
    $levelsCountValues = array_count_values(array_column($studentsComparitives, "module_level"));
    $mLevelColumn = 1;      // Always show
    $typeColumn = (count($typesCountValues) > 1) ? 1 : 0;
    $studyColumns = $mLevelColumn + $typeColumn;

    $campusCountValues = array_count_values(array_column($studentsComparitives, "campus_code"));
    $campusColumn = (count($campusCountValues) > 1) ? 1 : 0;

    $html = "<td><table>";
    // Output a 2 line heading, but with Show x selection drop down to save vertical space
    $html .= "<tr>";

    // show rows drop down
    // But if we could filter advisees, we need to loop through and count
    // So count anything we might need
    $count = count($studentsComparitives);
    $stypes = array();
    $sstages = array();
    $modlevels = array();
    $campuscodes = array();
    $adviseesCount = 0;
    $enrolledCount = 0;
    $notEnrolledCount = 0;
    foreach ($studentsComparitives as $studentKey => $data) {
        $eStatus = $data['enrolment_status_code'];
        if ($eStatus == 'AT' || $eStatus == 'UT') {
            $notEnrolledCount++;
            continue;
        }
        $enrolledCount++;
        if ($data["advisor_number"] == strtoupper($username)) {
            $adviseesCount++;
        }
        // Now save actual study types and stages that we have (even if not shown)
        $stypes[$data["study_type"]] = 1;
        $sstages[$data["study_stage"]] = 1;
        $modlevels[$data["module_level"]] = 1;
        $campuscodes[$data["campus_code"]] = 1;
    }
    $outOfCount = ($onlyMyAdvisees == "true") ? $adviseesCount : $enrolledCount;

    $html .= "<td class='key-fact' style='min-width:240px'>";
    $html .= "{$outOfCount} - Students</label>";
    $html .= '</td>';
    $html .= '<td></td>';       // for the info button

    $headerText = "Engagement";
    // For now put the show scatter chart here, but for now taken away :)
    // TODO see if we want this back $schart = '<a href="javascript:showMarksvEng()" id="obula_chart_scatter" name="obula_chart_scatter">Plot</a>';
    //$html .= "<th class='students' colspan='3'>$headerText $schart</th>";
    $html .= "<th class='students th-span' colspan='4'>$headerText</th>";
    // was a gap $html .= "<th class='students'></th>";
    $headerText = "Average Mark";
    $html .= "<th class='students-hideable th-span' colspan='2'>$headerText</th>";
    if ($studyColumns > 0) {
        $headerText = "Study";
        $html .= "<th class='students-hideable th-span' colspan='$studyColumns'>$headerText</th>";
    }
    $html .= "</tr>";
    // line 2 (Defaults to not show, js will show it if advisee count > 0)
    $html .= "<tr>";
    $html .= "<td><span style='display:none' id='obula_advisor'>";
    $html .= "<input type='checkbox' id='obula_myacc' name='obula_myacc' value='myacc'";
    if ($onlyMyAdvisees == "true") {
        $html .= " checked";
    }
    $html .= " onchange='myaccChanged()'</input>";
    $html .= "<label for='obula_myacc' style='padding-left:8px'>My Academic Advisees ($adviseesCount)</label>";
    $html .= "</span></td>";
    $html .= '<td></td>';       // for the info button

    $imageUrl = get_image_url("Actions-go-" . $cohortSort . "-view-icon");
    // tip won't go away on click $studyStageCell = "<th class='students' style='min-width:75px' data-toggle='tooltip' title = 'Compared to Average' onclick='clickCohortHeading()'>Position";
    $studyStageCell = "<th class='students-clickable' style='min-width:75px' onclick='clickCohortHeading()'>Rank";
    $studyStageCell .= "<img src = $imageUrl style = 'max-height:20px' id='obula_cohort_sort'  name='obula_cohort_{$cohortSort}'>";
    $studyStageCell .= "</th>";
    $studentCell = "<th class='students-hideable' style='min-width:75px'>Trend</<th>";
    $html .= $studyStageCell;
    $html .= $studentCell;
    $html .= "<th class='students-hideable'>Alert</th>";
    $html .= "<th class='students-hideable'>Att %</th>";

    // Now a dividing cell
    // $html .= "<td>&nbsp</td>";
    // And a header for marks
    $html .= "<th class='students-hideable'>" . $headings['lastTermHeading'] . "</th>";
    $html .= "<th class='students-hideable'>" . $headings['thisTermHeading'] . "</th>";
    if ($mLevelColumn > 0) {
        $html .= "<th class='students-hideable'>Level</th>";
    }
    if ($typeColumn > 0) {
        $html .= "<th class='students-hideable'>Mode</th>";
    }
    $html .= "<th class='students-hideable'>ISP</th>";
    $html .= "<th class='students-hideable'>Modules</th>";
    if ($campusColumn) {
        $html .= "<th class='students-hideable'>Campus</th>";
    }

    $html .= "</tr>";

    // Sort is here rather than in the web service for now, check if we could use local storage to save trip
    // TODO If not move this to web service
    try {
        $data_tutor->sort_student_comparitives($studentsComparitives, ($cohortSort == 'down'), ($studentSort == 'down'), true);
    } catch (Exception $e) {
        // Just output it in big bold red, shouldn't happen so no CSS for this
        $html .= "<br><b><font size='6'><style='color:red'>Exception from sort_student_comparitives: {$e}</style></font></b>";
    }

    // Loop through sorted students data and create rows
    $ids2chart = '';
    $loopCount = 0;
    foreach ($studentsComparitives as $studentKey => $data) {
        // array will contain all students for the cohort/studystage/studytype so averages etc make sense
        // so we may need to not show some if filtering by academic advisor
        if ($onlyMyAdvisees == "true" && $data["advisor_number"] != strtoupper($username)) {
            continue;
        }
        $eStatus = $data['enrolment_status_code'];
        $wStatus = $data['enrolment_withdrawal_status_code'];
        if ($eStatus == 'AT' || $eStatus == 'UT') {
            continue;
        }
        
        $loopCount++;

        $imageUrlISP = $util_odds->get_image_url4Comparison("isp", 't');
        if (($eStatus == 'EN' || $eStatus == 'EL') && $wStatus === null) {
            $cssClass = 'students-name';
        } else {
            $cssClass = 'students-name-ne';
        }
        $html .= "<tr class='students' id='sid_" . $studentKey . "'><td class='{$cssClass}'>";
        // Various articles on best way to make a link to javascript
        // such as https://stackoverflow.com/questions/10070232/how-to-make-a-cell-of-table-hyperlink
        //$studentAtts = array("href"=>"javascript:void(0);","onclick"=>"clickStudent('$studentKey','{$estatus}','{$wstatus}')");
        $sname = $data["student_name"];
        // Note tried various urlencode functions and &apos; but that get swapped back n the browser and it still wouldn't work
        $urlName = addslashes($sname);
        $advisor = $data["advisor_number"];
        // Do not try simplifying the following verbose lines of code unless you have time to spare
        // Seems to be a problem with the 's inside the "'s
        // $html .= "<a href='javascript:clickStudent('{$programme}','{$studyStage}','{$studentKey}','{$sname}','{$eStatus}','{$wStatus}')'>{$sname}</a></td>";
        $temp = $data["study_stage"];
        $html .= '<a href="javascript:clickStudent(';
        $html .= "'$programme',";
        $html .= "'$temp',";
        $html .= "'$studentKey',";
        $html .= "'$urlName',";
        $html .= "true)";
        $html .= '">'; // Note the closing "
        $html .= "{$sname}</a></td>";
        $onclick = "showStudentInfo('{$studentKey}','{$urlName}','{$advisor}','{$eStatus}','{$wStatus}')";
        $class = "material-icons students-info";
        if ($advisor == "") {
            $class .= " students-warning";
        }
        $html .= '<td class="' . $class . '" title="Student Info" onclick="' . $onclick . '">info</td>';       // the info button, preview is good too

        //$imageUrl = $util_odds->get_image_url4Comparison("sStage", $data["cohort_comparison"]);
        $posText = '?';
        if ($data["student_engagement"] == 0) {
            $posText = "Zero";
        } else {
            switch ($data["cohort_comparison"]) {
                case 'Red':
                    $posText = 'Low';
                    break;
                case 'Amber':
                    $posText = 'Medium';
                    break;
                case 'Green':
                    $posText = 'High';
                    break;
            }
        }
        $studyStageCell = "<td class='students' data-toggle='tooltip' title = '$hint'>"; // Simple hint for now TODO one using CSS
        $studyStageCell .= $posText;
        //$studyStageCell .= " (" . sprintf('%.0f', $data["student_engagement"]) . "/" . sprintf('%.0f', $data["student_weighted_engagement"]) . ")";
        $studyStageCell .= "</td>";

        $imageUrl0 = $util_odds->get_image_url4Comparison("student", $data["student_comparison_prev0"]);
        $imageUrl1 = $util_odds->get_image_url4Comparison("student", $data["student_comparison_prev1"]);
        $imageUrl2 = $util_odds->get_image_url4Comparison("student", $data["student_comparison_prev2"]);
        $imageUrl3 = $util_odds->get_image_url4Comparison("student", $data["student_comparison_prev3"]);
        $studentCell = "<td class='students'>";
        // Simple hints for now TODO one using CSS
        $hint = $data['student_comparison_prev3_hint'];
        $studentCell .= "<img src = $imageUrl3 style = 'max-height:18px' data-toggle='tooltip' title = '$hint'>";
        $hint = $data['student_comparison_prev2_hint'];
        $studentCell .= "<img src = $imageUrl2 style = 'max-height:18px' data-toggle='tooltip' title = '$hint'>";
        $hint = $data['student_comparison_prev1_hint'];
        $studentCell .= "<img src = $imageUrl1 style = 'max-height:18px' data-toggle='tooltip' title = '$hint'>";
        $hint = $data['student_comparison_prev0_hint'];
        $studentCell .= "<img src = $imageUrl0 style = 'max-height:18px' data-toggle='tooltip' title = '$hint'>";
        $studentCell .= "</td>";
        $html .= $studyStageCell;
        $html .= $studentCell;
        if ($data['alert_level'] != null) {
            $alertCell = "<td class='students-hideable'><a href='javascript:showStudentAlerts({$studentKey})'>" . $data['alert_level'] . "</a></td>";
        }
        else {
            $alertCell = "<td class='students-hideable'></td>";
        }
        $html .= $alertCell;
        $xx = $data["attended_percentage"];
        $html .= "<td class='students-hideable'>{$xx}</td>";

        // And cells for marks
        for ($i = 0; $i < 2; $i++) {
            $html .= "<td class='students-hideable'>";      // TODO right justify mark
            $mark = ($i == 0) ? $data["student_average_mark_lt"] : $data["student_average_mark_tt"];
            // Do not try simplifying the following verbose lines of code unless you have time to spare
            // Seems to be a problem with the 's inside the "'s
            $temp = $data["study_stage"];
            $html .= '<a href="javascript:clickStudentsMark(';
            $html .= "'$studentKey',";
            $html .= "'$urlName',";
            $html .= "'$temp',";
            $html .= "'$programme')";
            $html .= '">'; // Note the closing "
            $html .= "{$mark}</a></td>";
        }

        // Now study stage/mode/level if needed
        if ($mLevelColumn > 0) {
            $html .= "<td class='students-hideable'>" . $data["module_level"] . "</td>";
        }
        if ($typeColumn > 0) {
            $html .= "<td class='students-hideable'>" . $data["study_type"] . "</td>";
        }
        $html .= "<td class='students'>";
        if ($data["isp_flag"] != null && $data["isp_flag"] == 't') {
            $html .= "<img class='students-icon' src = $imageUrlISP style = 'max-height:18px'>";
            // $html .= "<td class='students-hideable'>" . $data["isp_flag"] . "</td>";
       }
        $html .= "</td>";
        $html .= "<td class='students' data-toggle='tooltip' title = 'No of registered modules'>"; // Simple hint for now TODO one using CSS
        $mc = $data['module_count'];
        $html .= "$mc";
        $html .= "</td>";
        if ($campusColumn) {
            $html .= "<td class='students'>";       // data-toggle='tooltip' title = 'No of registered modules'>"; // Simple hint for now TODO one using CSS
            $cc = $data['campus_code'];
            $html .= "$cc";
            $html .= "</td>";
        }
        $html .= "<td class='obula-block-hidden'>{$eStatus}</td>";        // or obula-block-hidden
        $html .= "<td class='obula-block-hidden'>{$wStatus}</td>";

        // Row done
        $html .= "</tr>";

        if ($loopCount > 1) {
            $ids2chart .= ",";
        }
        $ids2chart .= "'" . $studentKey . "'";
    }

    $html .= "</table></td>";
    $html .= '<input type = "hidden" id = "obula_ids2chart" value = "' . $ids2chart . '">';

    // Now send that back
    $stypesFilter = "";
    // TODO conditionally send filters for performance
    foreach ($stypes as $key => $value) {
        $stypesFilter .= $key . "|";
    }
    $sstagesFilter = "";
    foreach ($sstages as $key => $value) {
        $sstagesFilter .= $key . "|";
    }
    $modlevelsFilter = "";
    foreach ($modlevels as $key => $value) {
        $modlevelsFilter .= $key . "|";
    }
    $campusCodeFilter = "";
    foreach ($campuscodes as $key => $value) {
        $campusCodeFilter .= $key . "|";
    }
} else {
    $count = $adviseesCount = 0;
    $stypesFilter = $sstagesFilter = $modlevelsFilter = $campusCodeFilter= "";
}

header('Content-type: application/json');
// student_count is used to determine if we should show charting link
$json = json_encode(array('success' => $success, 'html' => $html
                            , 'full_data_set' => $fullDataSet
                            , 'students_count' => $count, 'advisees_count' => $adviseesCount
                            , 'study_stages' => $sstagesFilter, 'study_types' => $stypesFilter
                            , 'mod_levels' => $modlevelsFilter, "campus_codes" => $campusCodeFilter
                        ));
if ($json) {
    echo $json;
} else {
    $json_error = json_last_error_msg();
    echo json_encode(array('success' => false, 'json_error' => "{$json_error}"));
}
exit;

/**
 * Routine to get the url to reference a .png image
 *
 * @param  string $imageName The image name without it's extension
 * @return string The relative url for the image
 */
function get_image_url(string $imageName)
{
    // TODO I think there is an approved way of getting an url to an image that will then use cache etc
    // But looking at the network traffic, the browser is already doing some optimisation
    $ret = '../blocks/obu_learnanalytics/pix/' . $imageName . '.png';
    //image_url($imageName, "obu_learnanalytics");
    // or resolve_image_location
    return $ret;
}

