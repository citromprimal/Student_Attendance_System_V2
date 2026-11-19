<?php
require_once __DIR__ . '/../../db.php';

$courseId = (int)($_GET['course_id'] ?? 2);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="dochadzka_kurz_' . $courseId . '.csv"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

$header = ['Študent', 'ID'];
for ($w = 1; $w <= 13; $w++) $header[] = $w . '. týždeň';
fputcsv($out, $header, ';');

$students = $pdo->prepare("SELECT s.* FROM enrollments e JOIN students s ON s.id = e.student_id WHERE e.course_id = ? ORDER BY s.id");
$students->execute([$courseId]);

$att = $pdo->prepare("SELECT a.student_id, s.week_number, a.status FROM attendance a JOIN sessions s ON s.id = a.session_id WHERE s.course_id = ? AND a.is_incoming = 0");
$att->execute([$courseId]);
$map = [];
foreach ($att->fetchAll() as $r) $map[$r['student_id']][$r['week_number']] = $r['status'];

foreach ($students->fetchAll() as $st) {
    $row = [$st['full_name'], $st['student_code']];
    for ($w = 1; $w <= 13; $w++) {
        $row[] = $map[$st['id']][$w] ?? '-';
    }
    fputcsv($out, $row, ';');
}
fclose($out);
exit;
