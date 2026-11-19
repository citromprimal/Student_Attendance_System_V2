<?php
require_once __DIR__ . '/../../db.php';

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }
    $stmt = $pdo->prepare("
        INSERT INTO attendance (session_id, student_id, status, source, is_incoming)
        VALUES (?, ?, ?, 'manual', 0)
        ON CONFLICT(session_id, student_id) DO UPDATE SET status = excluded.status, source = 'manual'
    ");
    $stmt->execute([
        (int)$data['session_id'],
        (int)$data['student_id'],
        $data['status']
    ]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../evidencia.php");
    exit;
}

$action    = $_POST['action'] ?? '';
$sessionId = (int)($_POST['session_id'] ?? 0);

switch ($action) {
    case 'manual':
        $studentId = (int)($_POST['student_id'] ?? 0);
        $status    = $_POST['status'] ?? 'present';
        $time      = ($status === 'present' && !empty($_POST['arrived_time'])) ? $_POST['arrived_time'] : null;
        $note      = trim($_POST['note'] ?? '');

        $stmt = $pdo->prepare("
            INSERT INTO attendance (session_id, student_id, status, arrived_at, source, note, is_incoming)
            VALUES (?, ?, ?, ?, 'manual', ?, 0)
            ON CONFLICT(session_id, student_id) DO UPDATE SET
                status = excluded.status,
                arrived_at = excluded.arrived_at,
                source = 'manual',
                note = excluded.note
        ");
        $stmt->execute([$sessionId, $studentId, $status, $time, $note ?: null]);
        break;

    case 'replacement_add':
        $weekNumber = (int)($_POST['week_number'] ?? 0);
        $studentId  = (int)($_POST['student_id'] ?? 0);
        $time       = !empty($_POST['arrived_time']) ? $_POST['arrived_time'] : date('H:i:s');

        $origStmt = $pdo->prepare("
            SELECT s.id FROM sessions s
            JOIN enrollments e ON e.course_id = s.course_id
            WHERE e.student_id = ? AND s.week_number = ?
            LIMIT 1
        ");
        $origStmt->execute([$studentId, $weekNumber]);
        $origSessionId = $origStmt->fetchColumn();

        if ($origSessionId) {
            $pdo->prepare("
                INSERT INTO attendance (session_id, student_id, status, source, linked_session_id, is_incoming)
                VALUES (?, ?, 'replacing', 'manual', ?, 0)
                ON CONFLICT(session_id, student_id) DO UPDATE SET status = 'replacing', linked_session_id = excluded.linked_session_id
            ")->execute([$origSessionId, $studentId, $sessionId]);
        }

        $pdo->prepare("
            INSERT INTO attendance (session_id, student_id, status, arrived_at, source, linked_session_id, is_incoming)
            VALUES (?, ?, 'present', ?, 'manual', ?, 1)
            ON CONFLICT(session_id, student_id) DO UPDATE SET status = 'present', arrived_at = excluded.arrived_at, linked_session_id = excluded.linked_session_id, is_incoming = 1
        ")->execute([$sessionId, $studentId, $time, $origSessionId ?: null]);
        break;

    case 'replacement_remove':
        $studentId       = (int)($_POST['student_id'] ?? 0);
        $linkedSessionId = (int)($_POST['linked_session_id'] ?? 0);

        $pdo->prepare("DELETE FROM attendance WHERE session_id = ? AND student_id = ? AND is_incoming = 1")
            ->execute([$sessionId, $studentId]);

        if ($linkedSessionId) {
            $pdo->prepare("UPDATE attendance SET status = 'absent', linked_session_id = NULL WHERE session_id = ? AND student_id = ?")
                ->execute([$linkedSessionId, $studentId]);
        }
        break;
}

header("Location: ../evidencia.php?session_id=" . $sessionId);
exit;
