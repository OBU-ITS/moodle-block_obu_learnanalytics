<?php

/**
 * Just a Page used to log a moodle event from javascript
 */

require_once __DIR__ . '/../../config.php';
$context = context_system::instance();       // Swapped to using system context as page threw error on Poodle
?>
<?php
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Pick up any data if passed
    $dashboard = $_POST["dashboard"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

// log the event
$other = array("Dashboard" => $dashboard);
$event = \block_obu_learnanalytics\event\dashboard_closed::create(array(
    'context' => $context, 'other' => json_encode($other)
));
$event->trigger();

// Now send something back, although js doesn't actually use it
echo json_encode(array('success' => true));

exit;
