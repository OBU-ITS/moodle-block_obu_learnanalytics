<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
?>

<?php
$util_dates = new \block_obu_learnanalytics\util\date_functions();
$curl_common = new \block_obu_learnanalytics\curl\common();

// Drop down event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sid = $_POST["studentNumber"] ?? ""; //TODO error handling if no student id
    $semester = $_POST["semester"] ?? "";
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

try {
    $params = 'student/attendancealerts/' . $sid . '/' . $semester . '/';
    $studentAttendance = $curl_common->send_request($params);
} catch (Exception $ex) {
    $curl_common->echo_error_console_log($ex);
    exit;
}

$alertRows = '<tr><th>Week</th><th>From</th><th>To</th>';
$alertRows .= '<th>Level</th><th>Reason</th><th>Attended</th><th>Out of</th>';
$alertRows .= '<th>Att Rank</th><th>Online Rank</th><th>Out of</th>';
$alertRows .= '</tr>';
$lastWc = "";
foreach ($studentAttendance as $attendance) {
    $alertRows .= '<tr><td>' . $attendance["academic_week"] . '</td>';
    $alertRows .= '<td>' . $attendance["from_date"] . '</td>';
    $alertRows .= '<td>' . $attendance["to_date"] . '</td>';
    $alertRows .= '<td>' . $attendance["alert_level"] . '</td>';
    $alertRows .= '<td>' . $attendance["alert_reason"] . '</td>';
    $alertRows .= '<td>' . $attendance["attendance_present"] . '</td>';
    $alertRows .= '<td>' . $attendance["attendance_out_of"] . '</td>';
    $alertRows .= '<td>' . $attendance["attendance_rank"] . '</td>';
    $alertRows .= '<td>' . $attendance["online_rank"] . '</td>';
    $alertRows .= '<td>' . $attendance["rank_out_of"] . '</td>';
    $alertRows .= "</tr>";
}
;

header('Content-type: application/json');
$title = $sName;
// TODO hunt for other languages
$url = new moodle_url("/blocks/obu_learnanalytics/lang/en/student_alerts.html");
//$popupbodyhtml = file_get_contents($url, false);
// get it as an array so I can exclude lines
$fileLines = file($url);
$html = "";
foreach ($fileLines as $line) {
    if ($advisor != "" || !strpos($line, "id=obula_aaemail")) {
        $html .= $line;
    }
}

// Next line does not use ", because we don't want PHP to try and replace the variables yet
// If adding more detail then change student_info.html as well
$from = array('{$alertRows}');
$to = array($alertRows);
$popupbodyhtml = str_replace($from, $to, $html);

// Now send all that back
echo json_encode(array('success' => true, 'title' => "Student Attendance Alerts", 'popupbodyhtml' => $popupbodyhtml));
exit;
