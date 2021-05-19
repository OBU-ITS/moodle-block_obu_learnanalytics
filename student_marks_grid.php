<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
?>

<?php
$util_dates = new \block_obu_learnanalytics\util\date_functions();
$curl_common = new \block_obu_learnanalytics\curl\common();

// Drop down event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sid = $_POST["studentNumber"] ?? ""; //TODO error handling if no student id
    $programme = $_POST["programme"] ?? "";
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

try {
    $params = 'student/marks/' . $sid . '/';
    $studentMarks = $curl_common->send_request($params);
} catch (Exception $ex) {
    $curl_common->echo_error_console_log($ex);
    exit;
}

// Let's see if there are any un-related marks
$anyToHide = false;
foreach ($studentMarks as $module) {
    $pgm = $module['programme_code'];
    if ($pgm != $programme) {
        $anyToHide = true;
        break;
    }
}

echo "<td>";
$hide = "false";
if ($anyToHide) {
    $hide = get_user_preferences("obula_hide_othermarks");
    echo "<input type='checkbox' id='obula_hideOtherMarks'";
    if (isset($hide) && $hide == "true") {
        echo " checked";
    }
    echo " onchange='hideOtherMarksChanged()'>";
    echo "<label for='obula_hideotherMarks' style='padding-left:8px'>Hide Unrelated Marks (in italics)</label>";
}
echo "<table class='student-marks'>";
echo "<tr>";
echo "<th>Semester</th>";
echo "<th>Module Code</th>";
echo "<th>Module Title</th>";
echo "<th>Grade</th>";
echo "<th class='th-numeric'>%</th>";
echo "<th class='th-numeric'>Weighting</th>";
echo "<th>Status</th>";
echo "<th class='th-numeric'>Average<br>Pass</th>";
echo "<th class='th-numeric'>Average<br>All</th>";
echo "</tr>";

foreach ($studentMarks as $module) {
    $pgm = $module['programme_code'];
    $pc = $module['mark_percentage'];
    $wt = $module['weighting'];
    if ($module['grade_code'] == 'S' || $module['weighting'] == '0') {
        $pc = $wt = '';
    }
    // Rather than a post back for the click event, we'll output them all
    // and just swap the class in the click event
    if ($pgm == $programme) {
        echo "<tr class='main-mark'>";
    } else {
        if ($hide == "true") {
            echo "<tr class='hidden-mark'>";
        } else {
            echo "<tr class='other-mark'>";
        }
    }
    $term = "Code:{$module['eff_term_code']}";
    // Failed attempt at alternative tooltip
    //echo "<td class='hover-tip'>{$module['eff_term_name']}";
    //echo "<span class='hover-text'>{$term}</span>";
    // Use Bootstrap instead
    echo "<td data-toggle='bootstrap' title='{$term}'>{$module['eff_term_name']}";
    echo "</td>";
    echo "<td>{$module['module_code']}</td>";
    echo "<td>{$module['module_title']}</td>";
    echo "<td>{$module['grade_code']}</td>";
    echo "<td class='td-numeric'>{$pc}</td>";
    echo "<td class='td-numeric'>{$wt}</td>";
    $text = "";  //($module['published'] == 0) ? get_string('marks-notpublished', 'block_obu_learnanalytics') : get_string('marks-published', 'block_obu_learnanalytics');
    echo "<td class='students'>{$text}</td>";
    echo "<td class='td-numeric'>{$module['passed_avg_mark_percentage']}</td>";
    echo "<td class='td-numeric'>{$module['attempted_avg_mark_percentage']}</td>";
}

echo "</table></td>";
exit;
