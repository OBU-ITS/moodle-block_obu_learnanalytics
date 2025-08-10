<?php
ob_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/vendor/autoload.php';

/* ─────── security ─────────────────────────────────────────────────── */
$util_odds = new \block_obu_learnanalytics\util\odds();
if ($util_odds->get_la_role() === 'STUDENT') {
    die('Permission Denied');
}

/* ─────── helpers ──────────────────────────────────────────────────── */
/**
 * 1) Build a plain HTML table (optional).
 * 2) Convert nested API payload → attendance‑matrix structure.
 */

/* 1 ─ Table (unchanged) */
function build_attendance_table(array $tree): string
{
    if (empty($tree)) {
        return '<p>No attendance data returned.</p>';
    }

    $html  = '<table class="generaltable attendance-table">';
    $html .= '<thead><tr>'
           .  '<th>Student&nbsp;No.</th><th>Name</th><th>Semester</th><th>Week</th>'
           .  '<th>Module&nbsp;ID</th><th>Module</th><th>Session&nbsp;Date</th>'
           .  '<th>Att</th><th>Non‑Att</th><th>%</th></tr></thead><tbody>';

    foreach ($tree as $stuNo => $stu) {
        $sName = $stu['student_name'] ?? '';
        foreach ($stu as $semKey => $semVal) {
            if (in_array($semKey, ['person_id', 'student_name'])) continue;
            foreach ($semVal['weeks'] as $weekNo => $weekVal) {
                foreach ($weekVal as $modId => $modVal) {
                    foreach ($modVal['sessions'] as $sess) {
                        $date = date('d‑M‑Y&nbsp;H:i', strtotime($sess['session_date']));
                        $html .= '<tr>'
                              .  "<td>{$stuNo}</td><td>{$sName}</td><td>{$semKey}</td><td>{$weekNo}</td>"
                              .  "<td>{$modId}</td><td>{$modVal['module_name']}</td><td>{$date}</td>"
                              .  "<td>{$sess['attended']}</td><td>{$sess['non_attended']}</td>"
                              .  "<td>{$sess['attended_percentage']}</td></tr>";
                    }
                }
            }
        }
    }
    return $html .= '</tbody></table>';
}

/* 2 ─ Matrix converter  (API → buildAttendanceMatrix() shape) */
function convert_to_matrix(array $tree): array
{
    $matrix = [];

    foreach ($tree as $stuNo => $stu) {
        $stuName = $stu['student_name'] ?? 'Unknown';
        $personBlock = [];
        $programmeBlock = [];
        $weekBlock = [];

        foreach ($stu as $semKey => $semVal) {
            if (in_array($semKey, ['person_id', 'student_name'])) continue;
            // ── only keep Semester 1 for now ───────────────────────────────
            if ($semKey !== 'Semester 1') continue;


            $programmeName = $semVal['programme'] ?? 'Unknown programme';

            foreach ($semVal['weeks'] as $weekNo => $weekVal) {
                $label = "Week {$weekNo}";
                $attended = 0; $non = 0; $modsMissed = [];

                foreach ($weekVal as $modId => $modVal) {
                    $missed = array_reduce(
                        $modVal['sessions'],
                        fn($c,$s)=>$c+($s['non_attended']>0?1:0),
                        0
                    );
                    $total  = count($modVal['sessions']);
                    $attended += $total - $missed;
                    $non     += $missed;

                    if ($missed) $modsMissed[$modId] = "{$missed}/{$total}";
                }

                $totSessions = $attended + $non;
                $pct = $totSessions ? round($attended/$totSessions*100) : 0;

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

/* ─────── date / request parsing ───────────────────────────────────── */
$util_dates = new \block_obu_learnanalytics\util\date_functions();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Brookes Learning Analytics - GET not supported for tutor grid');
}

$currentWeek = $_POST['currentWeek'] ?? '';
$semester    = $_POST['semester']     ?? '';

$current  = $util_dates->json_2_current_week($currentWeek);
$semester = $current['semester'];

/* ─────── context / logging ────────────────────────────────────────── */
global $USER;
$today = new DateTime();
set_user_preference('obula_last_tutor_grid_date', serialize($today));

/* ─────── call web‑service ─────────────────────────────────────────── */
$success = true;
try {
    $params      = "tutor/adviseesmatrix/000000/1343801/";   // adjust if needed
    $curl_common = new \block_obu_learnanalytics\guzzle\common();
    $results     = $curl_common->send_request($params);   // nested structure
} catch (Exception $e) {
    $html    = "<p style='color:red;font-size:150%'>Exception: {$e}</p>";
    $success = false;
}

if ($success && empty($results)) {
    $html    = "<p style='color:blue;font-size:120%'>No Active Students</p>";
    $success = false;
}

/* ─────── build outputs ────────────────────────────────────────────── */
if ($success) {
    $html   = build_attendance_table($results);   // optional table
    $matrix = convert_to_matrix($results);        // JS “plug‑and‑play” data
    $count  = count($results);
} else {
    $matrix = [];
    $count  = 0;
}

/* ─────── JSON response ───────────────────────────────────────────── */
header('Content-Type: application/json');
echo json_encode([
    'success'        => $success,
    'html'           => $html,
    'data'           => $matrix,     // ← send to buildAttendanceMatrix()
    'students_count' => $count
]);
exit;
