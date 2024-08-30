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
    $sName = $_POST["sName"] ?? "";
    $advisor = $_POST["advisor"] ?? "";
    $semester = $_POST["semester"] ?? "";
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

try {
    $params = 'student/modules/' . $sid . '/' . $semester . '/';
    $studentModules = $curl_common->send_request($params);
} catch (Exception $ex) {
    $curl_common->echo_error_console_log($ex);
    exit;
}

$aaName = $advisor;
$aaemail = strtolower($advisor) . "@brookes.ac.uk";
if ($advisor == "") {
    $aaName = "Unknown";
} else {
    $aaUserObj = $DB->get_record("user", array('username' => strtolower($advisor)));
    if ($aaUserObj != false) {
        $aaName = $aaUserObj->firstname . ' ' . $aaUserObj->lastname;
        $aaemail = $aaUserObj->email;
    }
}
$email = $sid . "@brookes.ac.uk";
$studentUserObj = $DB->get_record("user", array('username' => $sid));
$lAccess = "Never";
if ($studentUserObj != false) {
    $email = $studentUserObj->email;
    $last = $studentUserObj->lastaccess;
    if ($last != null) {
        $lAccess = date('d-M-Y h:i:s', $last);
    }
}

$moduleRows = '<tr><th>Module Code</th><th>Module</th><th>Credits</th><th>Compulsory</th>';
$moduleRows .= '<th>Programme</th><th>Study Path</th><th>Campus</th></tr>';
foreach ($studentModules as $module) {
    $comp = ($module["compulsory_code"] == 'COMP') ? 'Yes' : 'No';
    $moduleRows .= '<tr><td>' . $module["module_id"] . '</td>';
    $moduleRows .= '<td>' . $module["module"] . '</td>';
    $moduleRows .= '<td>' . $module["credits"] . '</td>';
    $moduleRows .= '<td>' . $comp . '</td>';
    $moduleRows .= '<td>' . $module["programme_code"] . '</td>';
    $moduleRows .= '<td>' . $module["study_path"] . '</td>';
    $moduleRows .= '<td>' . $module["campus_code"] . '</td></tr>';
}
;

try {
    $params = 'student/attendance/' . $sid . '/' . $semester . '/';
    $studentAttendance = $curl_common->send_request($params);
} catch (Exception $ex) {
    $curl_common->echo_error_console_log($ex);
    exit;
}

$attRows = '<tr><th>Week Commencing</th><th>Module Code</th><th>Module</th>';
$attRows .= '<th>Attended</th><th>Out of</th></tr>';
$lastWc = "";
foreach ($studentAttendance as $attendance) {
    if ($lastwc != $attendance["week_commencing"]) {
        $attRows .= '<tr><td>' . $attendance["week_commencing"] . '</td>';
    } else {
        $attRows .= '<tr><td></td>';
    }
    $lastwc = $attendance["week_commencing"];
    $attRows .= '<td>' . $attendance["module_id"] . '</td>';
    $attRows .= '<td>' . $attendance["module"] . '</td>';
    $attRows .= '<td>' . $attendance["attended_total"] . '</td>';
    $attRows .= '<td>' . $attendance["attended_out_of"] . '</td></tr>';
}
;

header('Content-type: application/json');
$title = $sName;
// TODO hunt for other languages
$url = "lang/en/student_info.html";

$fileLines = file($url);
$html = "";
foreach ($fileLines as $line) {
    if ($advisor != "" || !strpos($line, "id=obula_aaemail")) {
        $html .= $line;
    }
}
// Next line does not use ", because we don't want PHP to try and replace the variables yet
// If adding more detail then change student_info.html as well
$from = array('{$studentNumber}', '{$sName}', '{$email}', '{$aaName}', '{$aaemail}', '{$lAccess}', '{$moduleRows}', '{$attRows}');
$to = array($sid, $sName, $email, $aaName, $aaemail, $lAccess, $moduleRows, $attRows);
$popupbodyhtml = str_replace($from, $to, $html);

// Now send all that back
echo json_encode(array('success' => true, 'title' => "Student Information", 'popupbodyhtml' => $popupbodyhtml));
exit;
