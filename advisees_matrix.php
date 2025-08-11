<?php
define('AJAX_SCRIPT', true);

ob_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/vendor/autoload.php';

$util_odds = new \block_obu_learnanalytics\util\odds();
if ($util_odds->get_la_role() === 'STUDENT') {
    die('Permission Denied');
}


function attendance_matrix(array $tree): array
{
    $matrix = [];

    foreach ($tree as $stuNo => $stu) {
        $stuName = $stu['student_name'] ?? 'Unknown';
        $personBlock = [];
        $programmeBlock = [];

        foreach ($stu as $semKey => $semVal) {
            // skip non-semester fields present in the payload
            if (in_array($semKey, ['student_person_id', 'student_name'], true)) {
                continue;
            }

            // only process entries that look like a semester block (must have weeks)
            if (!is_array($semVal) || !isset($semVal['weeks']) || !is_array($semVal['weeks'])) {
                continue;
            }

            // reset per semester
            $weekBlock = [];

            $programmeName = $semVal['programme'] ?? 'Unknown programme';

            foreach ($semVal['weeks'] as $weekNo => $weekVal) {
                $label = "Week {$weekNo}";
                $attended = 0; $non = 0; $modsMissed = [];

                foreach ($weekVal as $modId => $modVal) {
                    $sessions = $modVal['sessions'] ?? [];
                    if (!is_array($sessions)) {
                        $sessions = [];
                    }

                    $missed = 0;
                    foreach ($sessions as $s) {
                        $missed += (!empty($s['non_attended']) ? 1 : 0);
                    }
                    $total  = count($sessions);
                    $attended += max(0, $total - $missed);
                    $non     += $missed;

                    if ($missed) {
                        $modsMissed[$modId] = "{$missed}/{$total}";
                    }
                }

                $totSessions = $attended + $non;
                $pct = $totSessions ? round($attended / $totSessions * 100) : 0;

                $weekBlock[$label] = [
                    'attendance_percent' => (string)$pct,
                    'modules_missed'     => $modsMissed
                ];
            }

            $programmeBlock[$programmeName] = $weekBlock;
        }

        $personBlock[$stuName] = $programmeBlock;
        $matrix[$stuNo] = $personBlock;
    }

    return $matrix;
}


$util_dates = new \block_obu_learnanalytics\util\date_functions();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Brookes Learning Analytics - GET not supported for tutor grid');
}

$semester    = $_POST['semester']     ?? '';

global $USER;
$today = new DateTime();
set_user_preference('obula_last_tutor_grid_date', serialize($today));


$success = true;
try {
    $params      = "tutor/adviseesmatrix/$semester/$USER->username/";
    $curl_common = new \block_obu_learnanalytics\guzzle\common();
    $results     = $curl_common->send_request($params); 
} catch (Exception $e) {
    $html    = "<p style='color:red;font-size:150%'>Exception: {$e}</p>";
    $success = false;
}



if ($success && empty($results)) {
    $html    = "<p style='color:blue;font-size:120%'>No Active Students {$semester}</p>";
    $success = false;
}

if ($success) {
    $matrix = attendance_matrix($results);        // JS “plug‑and‑play” data
    $count  = count($results);
} else {
    $matrix = [];
    $count  = 0;
}

header('Content-Type: application/json');
echo json_encode([
    'success'        => $success,
    'semester'       => $semester,
    'data'           => $matrix,     // ← send to buildAttendanceMatrix()
    'students_count' => $count
]);
exit;
