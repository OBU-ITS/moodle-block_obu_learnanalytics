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
    $date = $_POST["date"];
    $weekControl = filter_var($_POST["weekControl"] ?? true, FILTER_VALIDATE_BOOLEAN);
    $semesterControl = filter_var($_POST["semesterControl"] ?? true, FILTER_VALIDATE_BOOLEAN);
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

if ($date == "getcurrent" || $date == null) {       // Shouldn't be null anymore
    $current = $util_dates->get_current_week();
} else {
    $current = $util_dates->json_2_current_week($date);
}

$weekHTML = "";
if ($weekControl) {
    $weekHTML .=  '<label for="obula_weekdate" style="min-width:150px">Week Commencing</label>';
    $weekHTML .=  '<a class="material-icons nextprev" onclick="clickChangeWeek(-1)" data-toggle="ztooltip" title="Previous Week">chevron_left</a>';
    // $weekHTML .=  '<a class="material-icons" onclick="clickChangeWeek(1)" title="Next Week">arrow_forward_ios</a>';         // Does not Match icon in google calendar
    $weekHTML .=  '<span id="obula_weekdate" class="wc_date">';
    $weekHTML .=  $current["first_day_week"]->format('d-M-Y');
    $weekHTML .=  '</span>';
    $weekHTML .=  '<a class="material-icons nextprev" onclick="clickChangeWeek(1)" data-toggle="ztooltip" title="Next Week">chevron_right</a>';         // Matches icon in google calendar
        
    // Now the hidden values for the javascript to pick up
    //$encoded = htmlspecialchars(json_encode($current)); // Don't think I need to worry about specialchars
    $encoded = json_encode($current); // Just Serialize as json so I can get it from javascript
    $weekHTML .=  "<input type='hidden' id='obula_currentweek' value = '" . $encoded . "'>";
}

$semesterHTML = "";
if ($semesterControl) {
    $today = new DateTime();
    $semesters = $util_dates->get_semesters($today);

    $semesterHTML .= "<label for='selSemester' style='min-width:150px'>Semester</label>";
    $semesterHTML .= "<select name='semester' id='selSemester' onchange='semesterChanged()' style='min-width:100px'>";
    $semesterHTML .= "<option value='week' selected='selected'>Specified Week</option>";
    foreach ($semesters as $semester) {
        $code = $semester['code'];
        $label = $semester['label'];
        $semesterHTML .= "<option value='{$code}'>{$label}</option>";
    }
    $semesterHTML .= "</select>";
}

header('Content-type: application/json');
// Now send all that back
echo json_encode(array('success' => true, 'weekControl' => $weekHTML, 'semesterControl' => $semesterHTML));
exit;
