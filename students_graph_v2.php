<?php ob_start();?><?php
//Too early, config resets - $old_error_handler = set_error_handler("errorHandlerOBU");     // Not self:: as this isn't a class
try {
    //define('AJAX_SCRIPT', true);      // This breaks things
    // Now load up a general purpose class
    require_once __DIR__ . '/../../config.php';
    $util_odds = new \block_obu_learnanalytics\util\odds();
    $laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything
    // config.php sets an error handler, so override here
    $old_error_handler = set_error_handler("errorHandlerOBU");     // Not self:: as this isn't a class
    $resultSent = false;        // Good or bad
    $util_dates = new \block_obu_learnanalytics\util\date_functions();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // The request is using the POST method
        $chartType = $_POST["chartType"];
        $currentWeek = $_POST["currentWeek"] ?? "";
        if ($currentWeek == "" || $currentWeek == null) {
            $current = $util_dates->get_current_week();
        } else {
            $current = $util_dates->json_2_current_week($currentWeek);
        }
        $sid = $_POST["studentNumber"] ?? ""; //TODO error handling if no student id
        $sname = $_POST["studentName"] ?? "";
        $programme = $_POST["programme"];
        $bandingCalc = $_POST["bandingCalc"] ?? "MED???";
        $studyStage = $_POST["sStage"] ?? "*";
    } else {
        exit("Brookes Learning Analytics - GET not supported for student graph");
    }
    if($programme == "") {
        exit("Brookes Learning Analytics - programme not passed");
    }
    // OK So now we shouldn't let a non tutor to anyone else's data
    if ($laRole == "STUDENT" && $sid != $USER->username) {
        die("Permission to others students data denied");
    }
    if ($chartType != 'none') { // Will be none if not visible
        $simpleCurrent = $util_dates->createSimpleCurrentParam($current);
        $sStage = $studyStage;
        switch ($chartType) {
            case "ezsessions":
                $column = "ez_sessions";
                break;
            case "ezduration":
                $column = "ez_duration_total";
                break;
            case "ezsize":
                $column = "ez_size";
                break;
            case "vlesessions":
                $column = "vle_sessions";
                break;
            case "vleduration":
                $column = "vle_duration_minutes";
                break;
            case "vleviews":
                $column = "vle_page_hits";
                break;
            case "loansline":
            case "loansbar":
            case "loanscomb":
                $column = "library_resources_loaned";
                break;
            case "attperc":
                $column = "attendance_percentage";
                //$column = "vle_page_hits";
                break;
            case "attsessions":
                $column = "attendance_sessions";
                break;
            case "attduration":
                $column = "attendance_duration_total";
                break;
        }
        // Note exception isn't caught if next line fails
        $curl_common = new \block_obu_learnanalytics\curl\common();
        $enc_pgm = htmlspecialchars(urlencode(str_replace('/','~',$programme)));
        $params = "student/pgmgraphdata/$sid/{$enc_pgm}/{$sStage}/$simpleCurrent/median/{$column}/";
        try {
            $studentGraphData = $curl_common->send_request($params);
            echo json_encode($studentGraphData);
        } catch (\Exception $ex) {
            $curl_common->echo_error_console_log($ex);
            $resultSent = true;
            //throw $ex; No that stops the message getting to the console
            if (isset($old_error_handler)) {
                set_error_handler($old_error_handler);      // Not sure I need this as I go out of scope
                $old_error_handler = null;
            }
            exit;
        }
    }
} catch (\ErrorException $ex) {
    $temp = 'console.info("ErrorException details for console log");';
    $temp .= 'console.log(' . json_encode($ex->getMessage()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getFile()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = \sprintf('<div display="none"><script type="text/javascript">%s</script></div>', $temp);
    // Echo as an array - javascript is expecting that
    ob_start();     // to solve problems when something already sent
    header('Content-type: application/json');
    echo json_encode(array('success' => false, 'consolehtml' => $consolehtml));
    $resultSent = true;
    //throw $ex; No that stops the message getting to the console
    exit;
} catch (\Exception $ex) {
    $temp = 'console.info("Exception details for console log");';
    $temp .= 'console.log(' . json_encode($ex->getMessage()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getFile()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = \sprintf('<div display="none"><script type="text/javascript">%s</script></div>', $temp);
    // Echo as an array - javascript is expecting that
    ob_start();     // to solve problems when something already sent
    header('Content-type: application/json');
    echo json_encode(array('success' => false, 'consolehtml' => $consolehtml));
    $resultSent = true;
    //throw $ex; No that stops the message getting to the console
    exit;
}