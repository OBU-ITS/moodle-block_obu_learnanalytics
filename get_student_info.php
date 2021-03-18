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
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

/* don't need anymore data from EDW yet
try {
    $params = 'student/marks/' . $sid . '/';
    //$studentMarks = $curl_common->send_request($params);
} catch (Exception $ex) {
    $curl_common->echo_error_console_log($ex);
    exit;
}
*/
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
if ($studentUserObj != false) {
    $email = $studentUserObj->email;
}

header('Content-type: application/json');
$title = $sName;
// TODO hunt for other languages
$url = new moodle_url("/blocks/obu_learnanalytics/lang/en/student_info.html");
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
$from = array('{$studentNumber}', '{$sName}', '{$email}', '{$aaName}', '{$aaemail}');
$to = array($sid, $sName, $email, $aaName, $aaemail);
$popupbodyhtml = str_replace($from, $to, $html);

// Now send all that back
echo json_encode(array('success' => true, 'title' => "Student Information", 'popupbodyhtml' => $popupbodyhtml));
exit;
