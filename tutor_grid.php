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
$util_dates = new \block_obu_learnanalytics\util\date_functions();
$data_tutor = new \block_obu_learnanalytics\data\tutor_functions();
//xdebug_break();

// Drop down event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // The request is using the POST method
    $programme = $_POST["programme"];
    $studyStage = $_POST["sStage"];
    $studyType = ($_POST["studyType"]);
    $maxShow = $_POST["maxShow"];
    if ($maxShow == '*') {
        $maxShow = 999999;
    }
    $cohortSort = $_POST["sStageSort"];
    $studentSort = $_POST["studentSort"];
    $cohortFirst = $_POST["cohortfirst"];
    $currentWeek = $_POST["currentWeek"]; // In JSON
    $myAdvisees = ($_POST["myAdvisees"] ?? "true");
    $bandingCalcOptions = ($_POST["bandingCalc"]);
    $semester = $_POST["semester"] ?? "";
    $option = ($_POST["option"]);
    $oldProgramme = $_POST["oldProgramme"] ?? "";
} else {
    exit("Brookes Learning Analytics - GET not supported for tutor grid");
}

$fullDataSet = ($studyStage == "*" && $studyType == "*");

// So if we've been passed the semester or other instruction, recalculate the date
$dateRecalculated = false;
switch ($option) {
    case 'semester':
        $current = $util_dates->get_semester_wc($semester);
        $dateRecalculated = true;
        break;
    
    case 'getcurrent':
        $current = $util_dates->get_current_week();
        // NO not needed $dateRecalculated = true;
        break;

    case 'nextweek':
        $current = $util_dates->json_2_current_week($currentWeek);
        $current = $util_dates->get_next_week($current);
        $dateRecalculated = true;
        break;

    case 'prevweek':
        $current = $util_dates->json_2_current_week($currentWeek);
        $current = $util_dates->get_prev_week($current);
        $dateRecalculated = true;
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
        break;
    default:
        $current = $util_dates->json_2_current_week($currentWeek);
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

$success = true;        // Hopefully
try {
    $simpleCurrent = $util_dates->createSimpleCurrentParam($current);
    // We don't want to filter by academic advisor, because cohort/studyStage averages etc should include all students
    $params = "tutor/studentsgrid/$programme/$bandingCalcOptions/$simpleCurrent/$studyStage/$studyType/*/";
    $curl_common = new \block_obu_learnanalytics\curl\common();
    $studentsComparitives = $curl_common->send_request($params);
} catch (Exception $e) {
    // Just output it in big bold red, shouldn't happen so no CSS for this
    $html = "<br><b><font size='6'><style='color:red'>Exception from students_comparitive_grid: {$e}</style></font></b>";
    // Now let it send all that back
    $success = false;
}
if ($success && $studentsComparitives == null || count($studentsComparitives) == 0) {
    $html = "<br><b><font size='+2'><style='color:blue'>No Active Students for this Selection Criteria</style></font></b>";
    // Now let it send all that back
    $success = false;
}

if ($success) {
    // Now work out if we should show the various study_ fields
    $stagesCountValues = array_count_values(array_column($studentsComparitives, "study_stage"));
    $typesCountValues = array_count_values(array_column($studentsComparitives, "study_type"));
    $levelsCountValues = array_count_values(array_column($studentsComparitives, "study_level"));
    $stageColumn = (count($stagesCountValues) > 1) ? 1 : 0;
    $typeColumn = (count($typesCountValues) > 1) ? 1 : 0;
    $levelColumn = (count($levelsCountValues) > 1) ? 1 : 0;
    $studyColumns = $stageColumn + $typeColumn + $levelColumn;

    $html = "<td><table>";
    // Output a 2 line heading, but with Show x selection drop down to save vertical space
    $html .= "<tr>";

    // show rows drop down
    // But if we are filtering advisees, we need to loop through and count
    if ($myAdvisees === true) {
        $count = 0;
        foreach ($studentsComparitives as $studentKey => $data) {
            if ($data["advisor_number"] == strtoupper($username)) {
                $count++;
            }
        }
    } else {
        $count = count($studentsComparitives);
    }

    $html .= "<td class='parameters' style='min-width:240px'>";
    $html .= '<label for "selMaxShow" style="min-width:100px">Show</label>';
    $html .= '<select name="showMax" id="selMaxShow" onchange="maxShowChanged()">';
    if ($count <= 10) {
        $html .= "<option value='10' selected='selected'>$count</option>";
    } else {
        $selatt = ($maxShow == 10) ? "selected='selected'" : "";
        $html .= "<option value='10' $selatt>10</option>";
        if ($count >= 20) {
            $selatt = ($maxShow == 20) ? "selected='selected'" : "";
            $html .= "<option value='20' $selatt>20</option>";
        }
        if ($count >= 50) {
            $selatt = ($maxShow == 50) ? "selected='selected'" : "";
            $html .= "<option value='50' $selatt>50</option>";
        }
        $selatt = ($maxShow > 999) ? "selected='selected'" : "";
        $html .= "<option value='*' $selatt>All</option>";
    }
    $html .= '</select>';
    $html .= '<label style="padding-left:8px">of ' . $count . '</label>';
    $html .= '</td>';
    $html .= '<td></td>';       // for the info button

    $headerText = "Engagement";
    // For now put the show scatter chart here, but for now taken away :)
    // TODO see if we want this back $schart = '<a href="javascript:showMarksvEng()" id="obula_chart_scatter" name="obula_chart_scatter">Plot</a>';
    //$html .= "<th class='students' colspan='3'>$headerText $schart</th>";
    $html .= "<th class='students th-span' colspan='3'>$headerText</th>";
    // was a gap $html .= "<th class='students'></th>";
    $headerText = "Average Mark";
    $html .= "<th class='students-hideable th-span' colspan='2'>$headerText</th>";
    if ($studyColumns > 0) {
        $headerText = "Study";
        $html .= "<th class='students-hideable th-span' colspan='$studyColumns'>$headerText</th>";
    }
    $html .= "</tr>";
    // line 2
    $html .= "<tr>";
    $html .= "<td><span style='display:none' id='obula_advisor'>";
    $html .= "<input type='checkbox' id='obula_myacc' name='obula_myacc' value='myacc' onchange='myaccChanged()</input>";
    $html .= "<label for='obula_myacc' style='padding-left:8px'>My Academic Advisees ($count)</label>";
    $html .= "</span></td>";
    $html .= '<td></td>';       // for the info button

    $imageUrl = get_image_url("Actions-go-" . $cohortSort . "-view-icon");
    // tip won't go away on click $studyStageCell = "<th class='students' style='min-width:75px' data-toggle='tooltip' title = 'Compared to Average' onclick='clickCohortHeading()'>Position";
    $studyStageCell = "<th class='students-clickable' style='min-width:75px' onclick='clickCohortHeading()'>Position";
    $studyStageCell .= "<img src = $imageUrl style = 'max-height:20px' id='obula_cohort_sort'  name='obula_cohort_{$cohortSort}'>";
    $studyStageCell .= "</th>";
    $imageUrl = get_image_url("column-swap-icon");
    $swapCell = "<th class='students-clickable' style='min-width:25px' title = 'Click to swap columns' onclick='clickSwapHeading()'>";
    $swapCell .= "<img src = $imageUrl style = 'max-height:20px' id='obula_swap_sort' name='obula_cohortfirst_{$cohortFirst}'>";
    $swapCell .= "</th>";
    $imageUrl = get_image_url("Actions-go-" . $studentSort . "-view-icon");
    // tip won't go away on click$studentCell = "<th class='students' style='min-width:75px' data-toggle='tooltip' title = 'Compared to Students Average (over 6 weeks)' onclick='clickStudentHeading()'>Trend";
    $studentCell = "<th class='students-clickable' style='min-width:75px' onclick='clickStudentHeading()'>Trend";
    $studentCell .= "<img src = $imageUrl style = 'max-height:20px' id='obula_student_sort' name='obula_student_{$studentSort}'>";
    $studentCell .= "</th>";
    if ($cohortFirst == 1) {
        $html .= $studyStageCell;
        $html .= $swapCell;
        $html .= $studentCell;
    } else {
        $html .= $studentCell;
        $html .= $swapCell;
        $html .= $studyStageCell;
    }

    // Now a dividing cell
    // $html .= "<td>&nbsp</td>";
    // And a header for marks
    $html .= "<th class='students-hideable'>2020/21 Sem 1</th>";
    $html .= "<th class='students-hideable'>2019/20 Sem 2</th>";
    if ($stageColumn > 0) {
        $html .= "<th class='students-hideable'>Stage</th>";
    }
    if ($typeColumn > 0) {
        $html .= "<th class='students-hideable'>Mode</th>";
    }
    if ($levelColumn > 0) {
        $html .= "<th class='students-hideable'>Level</th>";
    }

    $html .= "</tr>";

    // Sort it here rather than in the web service for now, check if we could use local storage to save trip
    // TODO If not move this to web service
    try {
        $data_tutor->sort_student_comparitives($studentsComparitives, ($cohortSort == 'down'), ($studentSort == 'down'), ($cohortFirst == 1));
    } catch (Exception $e) {
        // Just output it in big bold red, shouldn't happen so no CSS for this
        $html .= "<br><b><font size='6'><style='color:red'>Exception from sort_student_comparitives: {$e}</style></font></b>";
    }

    // Loop through sorted students data and create rows
    $ids2chart = '';
    $stypes = array();
    $sstages = array();
    $count = $adviseesCount = 0;
    foreach ($studentsComparitives as $studentKey => $data) {
        // array will contain all students for the cohort/studystage/studytype so averages etc make sense
        // so we may need to not show some if filtering by academic advisor
        if ($data["advisor_number"] == strtoupper($username)) {
            $adviseesCount++;
        } else {
            if ($myAdvisees === true) {
                continue;
            }
        }
        
        // So can't break from loop anymore when we've done enough as we need the filtered count
        $count++;
        if ($count <= $maxShow) {
//            $html .= "<tr class='students'><td class='students-name'>";
            $html .= "<tr class='students' id='sid_" . $studentKey . "'><td class='students-name'>";
            // Various articles on best way to make a link to javascript
            // such as https://stackoverflow.com/questions/10070232/how-to-make-a-cell-of-table-hyperlink
            //$studentAtts = array("href"=>"javascript:void(0);","onclick"=>"clickStudent('$studentKey')");
            $sname = $data["student_name"];
            $advisor = $data["advisor_number"];
            // Do not try simplifying the following verbose lines of code unless you have time to spare
            // Seems to be a problem with the 's inside the "'s
            // $html .= "<a href='javascript:clickStudent('{$programme}','{$studyStage}','{$studentKey}','{$sname}')'>{$sname}</a></td>";
            $temp = $data["study_stage"];
            $html .= '<a href="javascript:clickStudent(';
            $html .= "'$programme',";
            $html .= "'$temp',";
            $html .= "'$studentKey',";
            $html .= "'$sname',";
            $html .= "true)";
            $html .= '">'; // Note the closing "
            $html .= "{$sname}</a></td>";
            $onclick = "showStudentInfo('{$studentKey}','{$sname}','{$advisor}')";
            $class = "material-icons students-info";
            if ($advisor == "") {
                $class .= " students-warning";
            }
            $html .= '<td class="' . $class . '" title="Student Info" onclick="' . $onclick . '">info</td>';       // the info button, preview is good too

            $imageUrl = get_image_url4Comparison("sStage", $data["cohort_comparison"]);
            $hint = $data['cohort_comparison_hint'];
            $studyStageCell = "<td class='students students-pos' data-toggle='tooltip' title = '$hint'>"; // Simple hint for now TODO one using CSS
            $studyStageCell .= "<img src = $imageUrl style = 'max-height:18px'>";
            //$studyStageCell .= " (" . sprintf('%.0f', $data["student_engagement"]) . "/" . sprintf('%.0f', $data["student_weighted_engagement"]) . ")";
            $studyStageCell .= "</td>";

            $emptyCell = "<td class='students'/>";

            $imageUrl0 = get_image_url4Comparison("student", $data["student_comparison_prev0"]);
            $imageUrl1 = get_image_url4Comparison("student", $data["student_comparison_prev1"]);
            $imageUrl2 = get_image_url4Comparison("student", $data["student_comparison_prev2"]);
            $studentCell = "<td class='students'>";
            // Simple hints for now TODO one using CSS
            $hint = $data['student_comparison_prev0_hint'];
            $studentCell .= "<img src = $imageUrl0 style = 'max-height:18px' data-toggle='tooltip' title = '$hint'>";
            $hint = $data['student_comparison_prev1_hint'];
            $studentCell .= "<img src = $imageUrl1 style = 'max-height:18px' data-toggle='tooltip' title = '$hint'>";
            $hint = $data['student_comparison_prev2_hint'];
            $studentCell .= "<img src = $imageUrl2 style = 'max-height:18px' data-toggle='tooltip' title = '$hint'>";
            $studentCell .= "</td>";

            if ($cohortFirst == 1) {
                $html .= $studyStageCell;
                $html .= $emptyCell;
                $html .= $studentCell;
            } else {
                $html .= $studentCell;
                $html .= $emptyCell;
                $html .= $studyStageCell;
            }

            // Now a dividing cell
            // $html .= "<td>&nbsp</td>";
            // And cells for marks
            for ($i = 0; $i < 2; $i++) {
                $html .= "<td class='students-hideable'>";      // TODO right justify mark
                $mark = ($i == 0) ? $data["student_average_mark_tt"] : $data["student_average_mark_lt"];
                // Do not try simplifying the following verbose lines of code unless you have time to spare
                // Seems to be a problem with the 's inside the "'s
                $temp = $data["study_stage"];
                $html .= '<a href="javascript:clickStudentsMark(';
                $html .= "'$studentKey',";
                $html .= "'$sname',";
                $html .= "'$temp')";
                $html .= '">'; // Note the closing "
                $html .= "{$mark}</a></td>";
            }

            // Now study stage/mode/level if needed
            if ($stageColumn > 0) {
                $html .= "<td class='students-hideable'>" . $data["study_stage"] . "</td>";
            }
            if ($typeColumn > 0) {
                $html .= "<td class='students-hideable'>" . $data["study_type"] . "</td>";
            }
            if ($levelColumn > 0) {
                $html .= "<td class='students-hideable'>" . $data["study_level"] . "</td>";
            }

            $html .= "</tr>";
        }
        if ($count > 1) {
            $ids2chart .= ",";
        }
        $ids2chart .= "'" . $studentKey . "'";
        // Now save actual study types and stages that we have (even if no shown)
        $stypes[$data["study_type"]] = 1;
        $sstages[$data["study_stage"]] = 1;
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
} else {
    $count = $adviseesCount = 0;
    $stypesFilter = $sstagesFilter = "";
}

$returnDate = ($dateRecalculated) ? json_encode($current) : "";     // No htmlspecialchars
header('Content-type: application/json');
$json = json_encode(array('success' => $success, 'html' => $html, 'date' => $returnDate
                            , 'full_data_set' => $fullDataSet
                            , 'students_count' => $count, 'advisees_count' => $adviseesCount
                            , 'study_stages' => $sstagesFilter, 'study_types' => $stypesFilter
                        ));
if ($json) {
    echo $json;
} else {
    $json_error = json_last_error_msg();
    echo json_encode(array('success' => false, 'json_error' => "{$json_error}"));
}
exit;

function get_image_url($imageName)
{
    // TODO I think there is an approved way of getting an url to an image that will then use cache etc
    // But looking at the network traffic, the browser is already doing some optimisation
    $ret = '../blocks/obu_learnanalytics/pix/' . $imageName . '.png';
    //image_url($imageName, "obu_learnanalytics");
    // or resolve_image_location
    return $ret;
}

function get_image_url4Comparison($type, $colour)
{
    $imageName = "";

    if ($type == "sStage") {
        $imageName = $colour . "Circle";
    } else {
        switch ($colour) {
            case 'Red':
                $imageName = "RedArrowDown";
                break;
            case 'Green':
                $imageName = "GreenArrowUp";
                break;
            default:
                $imageName = "BlueEquals";
                break;
        }
    }

    // TODO I think there is an approved way of getting an url to an image that will then use cache etc
    // But looking at the network traffic, the browser is already doing some optimisation
    $ret = '../blocks/obu_learnanalytics/pix/' . $imageName . '.png';
    //image_url($imageName, "obu_learnanalytics");
    // or resolve_image_location
    return $ret;
}