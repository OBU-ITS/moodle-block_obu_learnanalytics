<?php
/**
 * Just a Page to get the latest date/times for data
 */

require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
?>
<?php
$util_dates = new \block_obu_learnanalytics\util\date_functions();
$curl_common = new \block_obu_learnanalytics\curl\common();
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pick up any data if passed
    //$dashboard = $_POST["dashboard"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

try {
    $params = 'utils/datacurrency/';
    $dataCurrency = $curl_common->send_request($params);
} catch (Exception $ex) {
    $curl_common->echo_error_console_log($ex);
    exit;
}

$text = "";
foreach ($dataCurrency as $table) {
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $table["latest_data"]);
    if ($table["table_name"] == "la_moodle_sessions_f") {
        $text .= ($text == "") ? "" : ", ";
        $text .= "Moodle Activity as of " . $date->format('d-M-Y H:i');
    }
    if ($table["table_name"] == "ezproxy_logs_resource_sessions_f") {
        $text .= ($text == "") ? "" : ", ";
        $text .= "Electronic Library Activity as of " . $date->format('d-M-Y h:i');
    }
}

// Now send it back
header('Content-type: application/json');
echo json_encode(array('success' => true, 'footerhtml' => $text));

exit;
