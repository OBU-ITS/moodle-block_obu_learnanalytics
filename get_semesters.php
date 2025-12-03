<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/util/date_functions.php');

$util_dates = new \block_obu_learnanalytics\util\date_functions();
$semesters = $util_dates->get_semesters();

foreach ($semesters as &$s) {
    if ($s['start_date'] instanceof DateTime) {
        $s['start_date'] = $s['start_date']->format('Y-m-d');
    }
    if ($s['end_date'] instanceof DateTime) {
        $s['end_date'] = $s['end_date']->format('Y-m-d');
    }
}

header('Content-Type: application/json');
echo json_encode($semesters);
exit;
