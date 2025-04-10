<?php
/**
 * Provides Week Commencing and Semester controls for student and tutor pages
 * Called on initial load and semester changed event
 * Called by showDateControls which decides which controls to emit
 */

use block_obu_learnanalytics\event\dashboard_closed;

ob_start();
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/vendor/autoload.php');

// Adjust the following path if your vendor folder is in a different location.

// Protect the page from unauthorized external calls.
defined('MOODLE_INTERNAL') || die();
global $USER;
if ($USER == null || $USER->id == 0) {
    die("Not Authenticated");
}
global $PAGE;
$context = $PAGE->context;
?>
<?php
try {
    $util_dates = new \block_obu_learnanalytics\util\date_functions();

    // Get POST parameters.
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $option       = $_POST["option"];
        $newSemester  = $_POST["semester"];
        $dashboardFor = $_POST["dashboardFor"];
    } else {
        exit("Brookes Learning Analytics - GET not supported");
    }

    // Build Semester control HTML.
    $semesterHTML = "";
    $semesters = $util_dates->get_semesters();
    $semesterHTML .= "<label for='selSemester' style='min-width:150px'>Semester</label>";
    $disabled = ($dashboardFor == 'Advisor' ? "disabled" : "");
    $semesterHTML .= "<select {$disabled} name='semester' id='selSemester' onchange='semesterChanged()' style='min-width:100px'>";
    // Loop through the semesters
    $current = null;
    foreach ($semesters as $semesterRow) {
        $code = $semesterRow['code'];
        $label = $semesterRow['label'];
        $default_sem = $semesterRow['default'];
        $semesterHTML .= "<option value='{$code}' title='{$code}'";
        // When the event indicates a semester change, mark it selected.
        if (($option == "getcurrent" && ($default_sem == '1' || $default_sem == 'true'))
            || ($option == "semester" && $code == $newSemester)) {
            $semesterHTML .= " selected='selected'";
            //$semesterStart = $semesterRow['start_date'];
            $semesterEnd = $semesterRow['end_date'];
            $current = $util_dates->get_week_for_date($semesterEnd, true, $code);
        }
        $semesterHTML .= ">{$label}</option>";
    }
    $semesterHTML .= "</select>";

    // Build Week control HTML.
    // Initialize $weekHTML first.
    $weekHTML = "";
    $weekHTML .= '<label for="obula_weekdate" style="min-width:150px">Data to W/C</label>';
    $weekHTML .= '<span id="obula_weekdate" class="wc_date">';
    if ($current != null) {
        $weekHTML .= $current['first_day_week']->format('d-M-Y');
    }
    $weekHTML .= '</span>';

    // Hidden value for JavaScript.
    $encoded = json_encode($current);
    $weekHTML .= "<input type='hidden' id='obula_currentweek' value='" . $encoded . "'>";

    header('Content-type: application/json');
    echo json_encode(array(
        'success' => true, 
        'weekControl' => $weekHTML, 
        'semesterControl' => $semesterHTML, 
        'current' => $encoded
    ));
    exit;
} catch (Exception $ex) {
    // Log the exception to the error log.
    error_log("date_controls.php Exception: " . $ex->getMessage());
    header('Content-type: application/json');
    echo json_encode(array('success' => false, 'error' => $ex->getMessage()));
    exit;
}
