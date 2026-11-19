<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbFile = __DIR__ . '/database.db';
$isNew = !file_exists($dbFile);

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec('PRAGMA foreign_keys = ON;');

if ($isNew && file_exists(__DIR__ . '/schema.sql')) {
    $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
}

$semesterStart = new DateTime('2026-09-14');
$today = new DateTime();
$diffDays = (int)$semesterStart->diff($today)->format('%r%a');
$currentWeek = max(1, min(13, intdiv($diffDays, 7) + 1));
$currentWeekday = (int)$today->format('N');

function h(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
