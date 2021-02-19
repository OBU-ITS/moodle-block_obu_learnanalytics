<?php
/**
 * Used to get help text for modal popup, can be called for student, tutor or SSC help
 */
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
?>
<?php
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $helpType = $_POST["helpType"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

header('Content-type: application/json');
// TODO hunt for other languages
$title = get_string("{$helpType}-help-title", 'block_obu_learnanalytics');
$helpUrl = new moodle_url("/blocks/obu_learnanalytics/lang/en/{$helpType}_explain.html");
$popupbodyhtml = file_get_contents($helpUrl, false);
// Now send all that back
echo json_encode(array('success' => true, 'title' => $title, 'popupbodyhtml' => $popupbodyhtml));
exit;
