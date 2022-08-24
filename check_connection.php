<?php

/**
 * Just a Page to get and show data connection status
 * NOTE - the two ?php blocks are important - no idea why
 */
require_once __DIR__ . '/../../config.php';
?>      
<?php
defined('MOODLE_INTERNAL') || die();
$curl_common = new \block_obu_learnanalytics\curl\common();
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pick up any data if passed
    //$dashboard = $_POST["dashboard"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

global $USER;

try {
    $params = "utils/checkconnection/";
    $curl_common->setCheckConnection(true);
    $status = $curl_common->send_request($params);
} catch (Exception $ex) {
    $status = array();
    $status["Status"] = "WS";
    $status["code"] = "WSEXCEPTION";
    $status["message"] = $ex->getMessage();
    $status["consolehtml"] = $curl_common->echo_error_console_log($ex, false);
    exit;
}
//TODO handle 404 etc
if ($status["Status"] != "OK") {
    if (is_null($status)) {
        $status = $curl_common->get_status_details();
    }
    $problemType = substr($status["code"], 0, 3);
    switch ($problemType) {
        case "WST":
            $status["problemMessageSml"] = "LA Services Unavailable (timeout)";
            $status["problemMessageMed"] = "LA Services Unavailable (timeout), please try later";
            break;
        case "WSO":
            $status["problemMessageSml"] = "LA Services error";
            $status["problemMessageMed"] = "LA Services unexpected error, please try later";
            break;
        case "WSN":
            $status["problemMessageSml"] = "LA Services Unavailable";
            $status["problemMessageMed"] = "LA Services Unavailable, please try later";
            break;
        case "DBN":
            $status["problemMessageSml"] = "EDW unavailable";
            $status["problemMessageMed"] = "Enterprise Data Warehouse unavailable, please try later";
            break;
        default:
            $status["problemMessagSml"] = "Connection issue";
            $status["problemMessageMed"] = "Unexpected connection issue, please try later";
            break;
    }
}

$isAdmin = is_siteadmin() || $USER->username == "p0090268";
$popup = "";
if ($isAdmin) {
    $clipButton = '<a class="material-icons copyclip" onclick="copyErrorTextToClipboard()" data-toggle="ztooltip" title="Copy to Clipboard">content_copy</a>';
    $tip = "Error" . $clipButton . "<div id='error-msg'><br>Code:" . $status["code"] . "<br>Message:" . $status["message"] . "</div>";
    $popup .= html_writer::tag("span", $tip, array("class" => "error-text"));
}
$status["popup"] = $popup;

// Now send it back
header('Content-type: application/json');
echo json_encode(array('success' => true, 'ccStatus' => $status));

exit;
