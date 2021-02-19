<?php
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
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

if ($date == null) {
    $current = $util_dates->get_current_week();
} else {
    $current = $util_dates->json_2_current_week($date);
}

echo '<label for="obula_weekdate" colspan="2">Week Commencing</label>';
echo '<button class="button-previous" onclick="clickChangeWeek(-1)"></button>';
echo '<span data-toggle="ztooltip" title="Week Commencing " id="obula_weekdate">';
echo $current["first_day_week"]->format('d-M-Y');
echo '</span>';
echo '<button class="button-next" onclick="clickChangeWeek(1)"></button>';

// Now the hidden values for the javascript to pick up
$encoded = htmlspecialchars(json_encode($current)); // Serialize as json so I can get it from javascript
echo "<input type='hidden' id='obula_currentweek' value = '" . $encoded . "'>";
$nextWeek = $util_dates->get_next_week($current);
$encoded = htmlspecialchars(json_encode($nextWeek));
echo "<input type='hidden' id='obula_nextweek' value = '" . $encoded . "'>";
$prevWeek = $util_dates->get_prev_week($current);
$encoded = htmlspecialchars(json_encode($prevWeek));
echo "<input type='hidden' id='obula_prevweek' value = '" . $encoded . "'>";
