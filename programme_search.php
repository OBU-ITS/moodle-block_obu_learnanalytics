<?php
/**
 * Used to get help text for modal popup, can be called for student, tutor or SSC help
 */
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
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $oldValue = $_POST["oldValue"];
    $oldName = $_POST["oldName"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

// Now create the form
$html = "";
$html .= '<label for "obula_search_str" style="min-width:100px">Programme</label>';
$html .= '<input type="text" id="obula_search_str" onkeyup="showSearchProgrammeResults(this.value)">';
$html .= '<br><div id="obula_results"><div>';

// Now send all that back
header('Content-type: application/json');
$title = "Change Programme from {$oldValue}: {$oldName}";
$json = json_encode(array('success' => true, 'popupbodyhtml' => $html
                            , 'title' => $title
                        ));
if ($json) {
    echo $json;
} else {
    $json_error = json_last_error_msg();
    echo json_encode(array('success' => false, 'json_error' => "{$json_error}"));
}
exit;
