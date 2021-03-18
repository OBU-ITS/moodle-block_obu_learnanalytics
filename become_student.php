<?php
/**
 * Page used by Student Support Coordinator dashboard to show what the students
 * sees when they look at their Learning Analytics Dashboard
 */
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role("SSC");    // Protects against attacks, wrong roles and everything
?>
<?php
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentNumber = $_POST["studentNumber"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

global $DB;

try {
    // NOT NEEDED YET
} catch (Exception $e) {
    // Just output it in big bold red, shouldn't happen so no CSS for this
    echo "<br><b><font size='6'><style='color:red'>Exception from get_student: {$e}</style></font></b>";
}

switch ($studentNumber) {
    //TODO extract common code from 2 becomes and put in shared class
    case '':
        header('Content-type: application/json');
        die(json_encode(array('success' => false, 'message' => 'Please Enter Student Number')));
    
    case '*!*hhhhh*!*':         // not expected :)
        header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(array('success' => false, 'message' => 'BIGGG Bang :)'));
        break;

    default:
        global $DB;
        global $PAGE;
        $validInMoodle = true;
        // NOTE - In Brookes Moodle, the student number (or staff p number) is the username
        $userObj = $DB->get_record("user", array('username' => $studentNumber));
        //TODO Check they are a valid active student
        if ($userObj == null || $userObj == false) {
            $validInMoodle = false;
            // TODO Put this check back
//            header('Content-type: application/json');
//            die(json_encode(array('success' => false, 'message' => 'Student Not Found')));
        }

        header('Content-type: application/json');
        if ($validInMoodle) {
            $sname = $userObj->firstname . ' ' . $userObj->lastname;
            $fname = $userObj->firstname;
        } else {
            $sname = $studentNumber;
            $fname = $studentNumber;
        }
        $summaryMessage = "<span class='ssc-title' id='obula_title'>You are viewing the Learning Analytics Dashboard for {$sname}</span>";
        $summaryMessage .= "   <a href='javascript:clearSSC() class='link-right''>Clear</a>";
        $summaryMessage .= "   <a href='javascript:collapseSSC() class='link-right''>Close</a>";

        // Now let's get the renderer class so I can call functions from it
        $renderer = $PAGE->get_renderer('block_obu_learnanalytics');
        // Now work out the programme (code nearly the same code in block_obu_learnanalytics.php)
        $params = "student/programmes/$studentNumber/";
        $curl_common = new \block_obu_learnanalytics\curl\common();
        $pgms = $curl_common->send_request($params);
    
        if ($pgms == null || count($pgms) == 0) {
            header('Content-type: application/json');
            die(json_encode(array('success' => false, 'message' => "Student {$sname} Not on an Active Programme")));
        }
        $pgm = $pgms[0]["programme_code"]; // TODO cope with zero and > 1
        try {
            $dashboard = $renderer->students_dashboard(true, $studentNumber, $fname, $sname, $pgm);
        } catch (\Exception $ex) {
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(array('success' => false, 'dashboardhtml' => 'BIGGG Bang :)'));
            $this->content->text = $renderer->error_page('Error Creating Student Dashboard', $ex);
            exit;
        }
        // Now send all that back
        echo json_encode(array('success' => true, 'summaryhtml' => $summaryMessage, 'dashboardhtml' => $dashboard));
        break;
}

exit;
