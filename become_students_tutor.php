<?php
/**
 * Page used by Student Support Coordinator dashboard to show what the tutor
 * sees for a students programme when they look at their Learning Analytics Dashboard
 * It is expecting a student number, not a staff number
 */
ob_start();
//echo __DIR__;
require_once __DIR__ . '/../../config.php';
$util_odds = new \block_obu_learnanalytics\util\odds();
$laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
?>
<?php
// Click event posts the request so we can pick up parameters from the data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentNumber = $_POST["studentNumber"];
} else {
    exit("Brookes Learning Analytics - GET not supported");
}

try {
    // NOT NEEDED YET
} catch (Exception $e) {
    // Just output it in big bold red, shouldn't happen so no CSS for this
    echo "<br><b><font size='6'><style='color:red'>Exception from ????: {$e}</style></font></b>";
}

switch ($studentNumber) {
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
            //header('Content-type: application/json');
            //die(json_encode(array('success' => false, 'message' => 'Student Not Found')));
        }

        header('Content-type: application/json');
        if ($validInMoodle) {
            $sname = $userObj->firstname . ' ' . $userObj->lastname;
        } else {
            $sname = $studentNumber;
        }
        $summaryMessage = "<span class='ssc-title' id='obula_title'>You are viewing the Tutors Dashboard for {$sname}'s Programme</span>";
        $summaryMessage .= "   <a href='javascript:clearSSC()' class='link-right'>Clear</a>";
        $summaryMessage .= "   <a href='javascript:collapseSSC()' class='link-right'>Close</a>";

        // Now work out the programme (code nearly the same code in block_obu_learnanalytics.php and elsewhere)
        $params = "student/programmes/$studentNumber/";
        $curl_common = new \block_obu_learnanalytics\curl\common();
        $pgms = $curl_common->send_request($params);
        if ($pgms == null || count($pgms) == 0) {
            header('Content-type: application/json');
            die(json_encode(array('success' => false, 'message' => "Student {$sname} Not on an Active Programme")));
        }
        $pgm = $pgms[0]["programme_code"]; // TODO cope with > 1

        // Now let's get the renderer class so I can call functions from it
        $renderer = $PAGE->get_renderer('block_obu_learnanalytics');
        try {
            $dashboard = $renderer->tutor_dashboard($pgm, true, $studentNumber);
        } catch (\Exception $ex) {
            header('HTTP/1.0 500 Internal Server Error');
            echo json_encode(array('success' => false, 'dashboardhtml' => 'BIGGG Bang :)'));
            //        $this->content->text = $renderer->error_page('Error Creating Student Dashboard', $ex);
            exit;
        }

        // Log that
        // And log the event
        $context = context_system::instance();       // Swapped to using system context as page threw error on Poodle
        $other = array("From" => "SSC_Dashboard", "Programme" => $pgm, "HTTP_USER_AGENT" => $_SERVER['HTTP_USER_AGENT']);
        $event = \block_obu_learnanalytics\event\tutor_dashboard_opened::create(array(
            'context' => $context, 'other' => json_encode($other)
        ));
        $event->trigger();

        // Now send all that back
        echo json_encode(array('success' => true, 'summaryhtml' => $summaryMessage, 'dashboardhtml' => $dashboard));
        break;
}

exit;
