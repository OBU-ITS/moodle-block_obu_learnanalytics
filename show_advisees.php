<?php

/**
 * Page used Academic Advisor Summary dashboard to show list of their advisees
 * similar to various become_xxx pages
 */

ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
require_once(__DIR__ . '/vendor/autoload.php');

$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role("TUTOR");    // Protects against attacks, wrong roles and everything //TODO do we want a new role
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
$summaryMessage = "<span class='tutor-title' id='obula_title'>Learning Analytics</span>";
$summaryMessage .= "
<button onclick='collapseTutor()' class='link-right dashboardCloseButton'>
    <i class='fa-solid fa fa-close'><b>Close</b></i>
</button>";


// Now let's get the renderer class so I can call functions from it
$renderer = $PAGE->get_renderer('block_obu_learnanalytics');
try {
    $dashboard = $renderer->advisor_dashboard();
} catch (\Exception $ex) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(array('success' => false, 'dashboardhtml' => 'BIGGG Bang :)'));
    //        $this->content->text = $renderer->error_page('Error Creating Student Dashboard', $ex);
    exit;
}
// Log that
// And log the event
$context = context_system::instance();       // Swapped to using system context as page threw error on Poodle
$other = array("From" => "showbutton", "HTTP_USER_AGENT" => $_SERVER['HTTP_USER_AGENT']);
$event = \block_obu_learnanalytics\event\tutor_dashboard_opened::create(array(
    'context' => $context, 'other' => json_encode($other)
));
$event->trigger();

// Now send all that back
echo json_encode(array('success' => true, 'summaryhtml' => $summaryMessage, 'dashboardhtml' => $dashboard));

exit;
