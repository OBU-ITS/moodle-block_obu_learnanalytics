<?php
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
?>
<?php
$util_dates = new \block_obu_learnanalytics\util\date_functions();

// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentNumber = $_POST["studentNumber"];
} else {
    exit("Brookes Learning Analytics - GET not supported for student ideas");
}

$current = $util_dates->get_current_week();

global $USER;
$username = $USER->username;

try {
    // NOT NEEDED YET
} catch (Exception $e) {
    // Just output it in big bold red, shouldn't happen so no CSS for this
    echo "<br><b><font size='6'><style='color:red'>Exception from student_ideas: {$e}</style></font></b>";
}

echo "Investigate what others are reading or downloading " . "<a>show</a>";
//echo "Investigate what others are reading or downloading " . "<a href='javascript:showELibraryHistory()'>show</a>";
echo "<br>You have a quiz due by the 5th of August " . "<a href='javascript:xxx()'>show</a>";
echo "<br>You have 2 quiz's that you can retry " . "<a href='javascript:xxx()'>show</a>";
echo "<br>Message your group for advice on good reading materials";
echo "<br>Try to regularly access your online resources";
exit;

?>