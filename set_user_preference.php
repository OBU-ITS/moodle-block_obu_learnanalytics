<?php

/**
 * Used to set a user preference in moodle
 *
 * @package     block_obu_learnanalytics
 * @copyright   2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 //ob_start();
require_once __DIR__ . '/../../config.php';
?>
<?php
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST["name"];
    $value = $_POST["value"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

set_user_preference($name, $value);

// Now send something back
header('Content-type: application/json');
echo json_encode(array('success' => true));
exit;
