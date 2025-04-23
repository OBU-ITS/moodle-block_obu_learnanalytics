<?php ob_start();?><?php
try {
    require_once __DIR__ . '/../../config.php';
    require_once(__DIR__ . '/vendor/autoload.php');

    $util_odds = new \block_obu_learnanalytics\util\odds();
    $laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything

    $util_dates = new \block_obu_learnanalytics\util\date_functions();

    $posted = true;
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
    } else {
        exit("Brookes Learning Analytics - GET not supported for student modules");
    }

    // OK So now we shouldn't let a non tutor to anyone else's data
    if ($laRole == "STUDENT" && $sid != $USER->username) {
        die("Permission to others students data denied");
    }

    $simpleCurrent = $util_dates->createSimpleCurrentParam($current);
    $params = "student/modgraphdata/$sid/$simpleCurrent/";
    $curl_common = new \block_obu_learnanalytics\guzzle\common();

    try { 
        $studentModuleData = $curl_common->send_request($params);
        echo json_encode($studentModuleData);
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
} catch (\ErrorException $ex) {
    exit;
}