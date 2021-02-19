<?php

/**
 * Page used by Tutor Summary dashboard to show full tutor dashboard
 * so not really become but it's very similar to the others
 */

ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role("TUTOR");    // Protects against attacks, wrong roles and everything
?>
<?php
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pick up any data if passed
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

try {
    // NOT NEEDED YET
} catch (Exception $e) {
    // Just output it in big bold red, shouldn't happen so no CSS for this
    echo "<br><b><font size='6'><style='color:red'>Exception from ??: {$e}</style></font></b>";
}

header('Content-type: application/json');

global $DB;
global $PAGE;
$summaryMessage = "<h3>Learning Analytics";
$summaryMessage .= "<a href='javascript:collapseTutor()' class = 'link-right'>Close</a></h5>";

// TODO find way to pick up programme for Tutor or last viewed
$userPrefs = get_user_preferences();
if (array_key_exists("obula_last_tutor_grid_pgm", $userPrefs)) {
    $pgm = $userPrefs["obula_last_tutor_grid_pgm"];
} else {
    $pgm = "BAH-AF";       //TODO make blank work or pick up from tutor tables see SSCSECT, SSRMEET, SSBSECT, SSASECT
}

// Now let's get the renderer class so I can call functions from it
$renderer = $PAGE->get_renderer('block_obu_learnanalytics');
try {
    $dashboard = $renderer->tutor_dashboard($pgm, true);
} catch (\Exception $ex) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(array('success' => false, 'dashboardhtml' => 'BIGGG Bang :)'));
    //        $this->content->text = $renderer->error_page('Error Creating Student Dashboard', $ex);
    exit;
}
// Log that
// And log the event
$context = context_system::instance();       // Swapped to using system context as page threw error on Poodle
$other = array("From" => "Tutor_Summary", "Programme" => $pgm, "HTTP_USER_AGENT" => $_SERVER['HTTP_USER_AGENT']);
$event = \block_obu_learnanalytics\event\tutor_dashboard_opened::create(array(
    'context' => $context, 'other' => json_encode($other)
));
$event->trigger();

// Now send all that back
echo json_encode(array('success' => true, 'summaryhtml' => $summaryMessage, 'dashboardhtml' => $dashboard));

exit;
