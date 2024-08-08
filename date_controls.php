<?php
/**
 * Optionally Provides Week Commencing and Semester controls for student and tuto pages
 */
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
// Next section of lines is to protect page from being called from outside moodle
// and breaking security by impersonating a post
defined('MOODLE_INTERNAL') || die();
global $USER;
if ($USER == null || $USER->id == 0) {
    die("Not Authenticated");
}
global $PAGE;
$context = $PAGE->context;
// End of protective code
?>
<?php
$util_dates = new \block_obu_learnanalytics\util\date_functions();

// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $option = $_POST["option"];
    $newSemester = $_POST["semester"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

$semesterHTML = "";
$semesters = $util_dates->get_semesters();
$semesterHTML .= "<label for='selSemester' style='min-width:150px'>Semester</label>";
$semesterHTML .= "<select name='semester' id='selSemester' onchange='semesterChanged()' style='min-width:100px'>";
//$semesterHTML .= "<option value='week' selected='selected'>Specified Week</option>";
foreach ($semesters as $semesterRow) {
    $code = $semesterRow['code'];
    $label = $semesterRow['label'];
    $default_sem = $semesterRow['default'];
    $semesterHTML .= "<option value='{$code}' title='{$code}'";
    if (($option == "getcurrent" && ($default_sem == '1' || $default_sem == 'true'))
        || ($option == "semester" && $code == $newSemester)) {
    // if ($default_sem == '1' || $default_sem == 'true') {
        $semesterHTML .= " selected='selected'";
        //$semesterStart = $semesterRow['start_date'];
        $semesterEnd = $semesterRow['end_date'];
        $current = $util_dates->get_week_for_date($semesterEnd, true, $code);
    }
    $semesterHTML .= ">{$label}</option>";
}
$semesterHTML .= "</select>";

// Week is always shown
$weekHTML .= '<label for="obula_weekdate" style="min-width:150px">Data to W/C</label>';
$weekHTML .= '<span id="obula_weekdate" class="wc_date">';
if ($current != null) {
    $weekHTML .= $current['first_day_week']->format('d-M-Y');
}
$weekHTML .= '</span>';
    
// Now the hidden values for the javascript to pick up
//$encoded = htmlspecialchars(json_encode($current)); // Don't think I need to worry about specialchars
$encoded = json_encode($current); // Just Serialize as json so I can get it from javascript
$weekHTML .= "<input type='hidden' id='obula_currentweek' value = '" . $encoded . "'>";

header('Content-type: application/json');
// Now send all that back
echo json_encode(array('success' => true, 'weekControl' => $weekHTML, 'semesterControl' => $semesterHTML, 'current' => $encoded));
exit;
