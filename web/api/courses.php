<?php
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$returnWeek = max(1, min(13, (int)($_POST['return_week'] ?? $currentWeek)));

switch ($action) {
    case 'save':
        $courseId   = (int)($_POST['course_id'] ?? 0);
        $subject    = trim($_POST['subject'] ?? '');
        $type       = $_POST['type'] ?? 'exercise';
        $room       = trim($_POST['room'] ?? '') ?: null;
        $weekday    = (int)($_POST['weekday'] ?? 1);
        $startsAt   = $_POST['starts_at'];
        $endsAt     = $_POST['ends_at'];
        $recurrence = $_POST['recurrence'] ?? 'weekly';
        $validFrom  = $_POST['valid_from'];
        $validTo    = !empty($_POST['valid_to']) ? $_POST['valid_to'] : null;

        if ($recurrence === 'once') {
            $weekday = (int)date('N', strtotime($validFrom));
            $validTo = $validFrom;
        }

        if ($courseId > 0) {
            $stmt = $pdo->prepare("
                UPDATE courses
                SET subject = ?, lesson_type = ?, room = ?, weekday = ?,
                    starts_at = ?, ends_at = ?, recurrence = ?, valid_from = ?, valid_to = ?
                WHERE id = ?
            ");
            $stmt->execute([$subject, $type, $room, $weekday, $startsAt, $endsAt, $recurrence, $validFrom, $validTo, $courseId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO courses (teacher_id, subject, lesson_type, room, weekday, starts_at, ends_at, recurrence, valid_from, valid_to)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$subject, $type, $room, $weekday, $startsAt, $endsAt, $recurrence, $validFrom, $validTo]);
            $newCourseId = (int)$pdo->lastInsertId();

            // Vygenerovanie termínov v tabuľke sessions pre 1. – 13. týždeň
            $semesterStart = new DateTime('2026-09-14');
            $insSession = $pdo->prepare("INSERT INTO sessions (course_id, week_number, session_date) VALUES (?, ?, ?)");

            for ($w = 1; $w <= 13; $w++) {
                if ($recurrence === 'biweekly' && ($w % 2 === 0)) continue;
                $date = (clone $semesterStart)->modify('+' . (($w - 1) * 7 + ($weekday - 1)) . ' days');
                $dateStr = $date->format('Y-m-d');

                if ($dateStr < $validFrom) continue;
                if ($validTo && $dateStr > $validTo) continue;

                $insSession->execute([$newCourseId, $w, $dateStr]);
            }
        }
        break;

    case 'delete':
        $courseId = (int)($_POST['course_id'] ?? 0);
        $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$courseId]);
        break;
}

header("Location: ../index.php?week=" . $returnWeek);
exit;
