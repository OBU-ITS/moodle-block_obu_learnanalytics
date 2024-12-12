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
    $currentWeek = $_POST["currentWeek"]; // In JSON
    $semester = $_POST["semester"] ?? "";
} else {
    exit("Brookes Learning Analytics - GET not supported for tutor grid");
}

// So if we've been passed the semester or other instruction, recalculate the date
switch ($option) {
    case 'semester':
        // Nothing todoadv
        break;

    case 'getcurrent':
        $current = $util_dates->get_current_week();
        // NO not needed $dateRecalculated = true;
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

$success = true;        // Hopefully
try {
    $params = "tutor/adviseesgrid/$semester/$USER->username/";
    $curl_common = new \block_obu_learnanalytics\curl\common();
    $results = $curl_common->send_request($params);
} catch (Exception $e) {
    // Just output it in big bold red, shouldn't happen so no CSS for this
    $html = "<br><b><font size='6'><style='color:red'>Exception from advisees_grid: {$e}</style></font></b>";
    // Now let it send all that back
    $success = false;
}
if ($success && !isset($results) || count($results) == 0) {
    $html = "<br><b><font size='+2'><style='color:blue'>No Active Students for Academic Advisor</style></font></b>";
    // Now let it send all that back
    $success = false;
}

if ($success) {
    $html = "<td><table>";
    // Output a 2 line heading, but with Show x selection drop down to save vertical space
    $html .= "<tr>";

    // show rows drop down
    // But if we could filter advisees, we need to loop through and count
    // So count anything we might need
    $count = count($results);
    $enrolledCount = 0;
    $enrollingCount = 0;
    $notEnrolledCount = 0;
    foreach ($results as $data) {
        $eStatus = $data['enrolment_status_code'];
        if ($eStatus == 'EN' || $eStatus == 'EL') {
            $enrolledCount++;
            // Now save actual study types and stages that we have (even if not shown)
            $stypes[$data["study_type"]] = 1;
            $modlevels[$data["module_level"]] = 1;
            $campuscodes[$data["campus_code"]] = 1;
            if ($eStatus == 'EL') {
                $enrollingCount++;
            }
        } else {
            $notEnrolledCount++;
        }
    }

    $html .= "<td class='key-fact th-span' colspan='4' style='min-width:240px'>";
    if ($enrollingCount > 0) {
        $html .= "<label>{$count} - Enrolled/Enrolling Advisees</label>";
    } else {
        $html .= "<label>{$count} - Enrolled Advisees</label>";
    }
    $html .= '</td>';

    $html .= outputGrid($results, $util_odds, 1);

    $htmlGrid2 = "";
    if ($notEnrolledCount > 0) {
        $htmlGrid2 = "<td><table>";
        // Output a 2 line heading, but with Show x selection drop down to save vertical space
        $htmlGrid2 .= "<tr>";
        $htmlGrid2 .= "<td class='key-fact th-span' colspan='4' style='min-width:240px'>";
        $htmlGrid2 .= "<label>{$notEnrolledCount} - Advisee";
        $htmlGrid2 .= ($notEnrolledCount == 1) ? " not enrolled</label>" : "s not enrolled<label>";
        $htmlGrid2 .= '</td>';
        $htmlGrid2 .= outputGrid($results, $util_odds, 2);
    }

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
    $stypesFilter = $sstagesFilter = $modlevelsFilter = $campusCodeFilter = "";
}

header('Content-type: application/json');
// student_count is used to determine if we should show charting link
$json = json_encode(array(
    'success' => $success,
    'html' => $html,
    'html2' => $htmlGrid2,
    'full_data_set' => $fullDataSet,
    'students_count' => $count
));
if ($json) {
    echo $json;
} else {
    $json_error = json_last_error_msg();
    echo json_encode(array('success' => false, 'json_error' => "{$json_error}"));
}
exit;

function outputGrid($results, $util_odds, $gridNo)
{
    // row already started, just add headers, but then close row
    $html = "<th class='students th-span' colspan='2'>Attendance</th>";
    $html .= "<th class='students-hideable th-span' colspan=2>Study</th>";
    $html .= "</tr>";

    // 2nd header row
    $html .= "<tr>";
    $html .= "<th class='students-hideable'>Programme</th>";
    $html .= "<th class='students-hideable'>Code</th>";
    $html .= "<th class='students-hideable th-span' colspan='2'>Student</th>";
    $html .= "<th class='students-hideable'>Alert</th>";
    $html .= "<th class='students-hideable'>Att %</th>";

    $html .= "<th class='students-hideable'>Level</th>";        // Study level, not alert
    $html .= "<th class='students-hideable'>Mode</th>";
    $html .= "<th class='students-hideable'>ISP</th>";
    $html .= "<th class='students-hideable'>Modules</th>";
    $html .= "<th class='students-hideable'>Campus</th>";
    if ($gridNo != 1) {
        $html .= "<th class='students-hideable'>En Status</th>";
        $html .= "<th class='students-hideable'>Reason</th>";
        $html .= "<th class='students-hideable'>Date</th>";
    }

    $html .= "</tr>";

    // Loop through sorted students data and create rows
    $imageUrlISP = $util_odds->get_image_url4Comparison("isp", 't');
    foreach ($results as $data) {
        $eStatus = $data['enrolment_status_code'];
        $wStatus = $data['enrolment_withdrawal_reason_code'];
        $wHint = $data['enrolment_withdrawal_reason'];
        if ($gridNo == 1) {
            if ($eStatus != 'EN' && $eStatus != 'EL') {
                continue;
            }
        } else {
            if ($eStatus == 'EN' || $eStatus == 'EL') {
                continue;
            }
        }
        $studentKey = $data["student_number"];

        // Programme and code
        $html .= "<tr class='students' id='sid_" . $studentKey . "'>";
        $html .= "<td class='students-hideable'>" . $data["programme"] . "</td>";
        $html .= "<td class='students-hideable'>" . $data["programme_code"] . "</td>";
        // Student
        $html .= "<td class='students-name'>";
        $sname = $data["student_name"];
        // Note tried various urlencode functions and &apos; but that get swapped back n the browser and it still wouldn't work
        $urlName = addslashes($sname);
        // Do not try simplifying the following verbose lines of code unless you have time to spare
        // Seems to be a problem with the 's inside the "'s
        // $html .= "<a href='javascript:clickStudentAdvisee('{$programme}','{$studyStage}','{$studentKey}','{$sname}','{$eStatus}','{$wStatus}')'>{$sname}</a></td>";
        $html .= '<a href="javascript:clickStudentAdvisee(';
        // $html .= "'$programme_code',";
        // $html .= "'$temp',";
        $html .= "'$studentKey',";
        // $html .= "'$urlName',";
        // $html .= "true)";
        $html .= ")";
        $html .= '">'; // Note the closing "
        $html .= "{$sname}</a></td>";
        $onclick = "showStudentInfo('{$studentKey}','{$urlName}','{$advisor}','{$eStatus}','{$wStatus}')";
        $class = "material-icons students-info";
        $html .= '<td class="' . $class . '" title="Student Info" onclick="' . $onclick . '">info</td>';       // the info button, preview is good too

        // Alert level
        if ($data['alert_level'] != null) {
            $alertCell = "<td class='students-hideable'><a href='javascript:showStudentAlerts({$studentKey})'>" . $data['alert_level'] . "</a></td>";
        } else {
            $alertCell = "<td class='students-hideable'>-</td>";
        }
        $html .= $alertCell;
        // Att %
        $html .= "<td class='students-hideable'>" . $data["attendance_percentage"] . "</td>";
        // Now study level and mode
        $html .= "<td class='students-hideable'>" . $data["module_level"] . "</td>";
        $html .= "<td class='students-hideable'>" . $data["study_mode_code"] . "</td>";
        // ISP column
        $html .= "<td class='students'>";
        if ($data["inclusive_support_plan"] != null && $data["inclusive_support_plan"] == 't') {
            $html .= "<img class='students-icon' src = $imageUrlISP style = 'max-height:18px'>";
        }
        $html .= "</td>";
        // Modules
        $html .= "<td class='students' data-toggle='tooltip' title = 'No of registered modules'>"; // Simple hint for now TODO one using CSS
        $html .= $data['module_total'] . "</td>";
        // Campus code
        $html .= "<td class='students-hideable'>" . $data['campus_code'] . "</td>";
        if ($gridNo != 1) {
            $fdate = date_format(date_create($data["end_date"]), "d-M-Y");
            $html .= "<td class='students-hideable'>{$eStatus}</td>";
            $html .= "<td class='students-hideable' title='{$wHint}'>{$wStatus}</td>";
            $html .= "<td class='students-hideable'>{$fdate}</td>";
        }

        // Row done
        $html .= "</tr>";
    }

    $html .= "</table></td>";

    return $html;
}
