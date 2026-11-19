<?php
require_once __DIR__ . '/../database/db.php';

$courses = $pdo->query("SELECT * FROM courses ORDER BY id")->fetchAll();
$courseId = (int)($_GET['course_id'] ?? 2);

$sessStmt = $pdo->prepare("SELECT * FROM sessions WHERE course_id = ? ORDER BY week_number");
$sessStmt->execute([$courseId]);
$sessions = $sessStmt->fetchAll();
$sessionsByWeek = [];
foreach ($sessions as $s) {
    $sessionsByWeek[(int)$s['week_number']] = $s;
}

$studStmt = $pdo->prepare("
    SELECT s.* FROM enrollments e
    JOIN students s ON s.id = e.student_id
    WHERE e.course_id = ?
    ORDER BY s.id
");
$studStmt->execute([$courseId]);
$students = $studStmt->fetchAll();

$attStmt = $pdo->prepare("
    SELECT a.*, s.week_number
    FROM attendance a
    JOIN sessions s ON s.id = a.session_id
    WHERE s.course_id = ? AND a.is_incoming = 0
");
$attStmt->execute([$courseId]);
$matrix = [];
$totalAbsent = 0;
$totalExcused = 0;

foreach ($attStmt->fetchAll() as $row) {
    $matrix[(int)$row['student_id']][(int)$row['week_number']] = $row;
    if ($row['status'] === 'absent') $totalAbsent++;
    if ($row['status'] === 'excused') $totalExcused++;
}


$repStmt = $pdo->prepare("
    SELECT st.full_name, s.week_number, s.session_date AS orig_date, c.group_name AS orig_group, ls.id AS linked_session_id, ls.session_date AS new_date, ls.course_id AS new_course_id, lc.group_name AS new_group, lc.starts_at, lc.ends_at
    FROM attendance a
    JOIN students st ON st.id = a.student_id
    JOIN sessions s ON s.id = a.session_id
    JOIN courses c ON c.id = s.course_id
    JOIN sessions ls ON ls.id = a.linked_session_id
    JOIN courses lc ON lc.id = ls.course_id
    WHERE s.course_id = ? AND a.status = 'replacing' AND a.is_incoming = 0
");
$repStmt->execute([$courseId]);
$replacements = $repStmt->fetchAll();

$dayNames = [1 => 'Pondelok', 2 => 'Utorok', 3 => 'Streda', 4 => 'Štvrtok', 5 => 'Piatok'];
$typeLabels = ['lecture' => 'Prednáška', 'seminar' => 'Seminár', 'exercise' => 'Cvičenie', 'lab' => 'Lab. cvičenie'];
$cellMap = [
    'present'   => ['P', 'status-present', 'Prítomný'],
    'absent'    => ['N', 'status-absent', 'Neprítomný'],
    'excused'   => ['O', 'status-excused', 'Ospravedlnený'],
    'replacing' => ['R', 'status-replacing', 'Nahrádza'],
];
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prehľad dochádzky </title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="app">
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Hlavná navigácia">
        <a class="brand" href="index.php">dochádzka</a>
        <div class="eyebrow">Pracovný priestor</div>

        <nav>
            <a href="index.php">Rozvrh</a>
            <a href="evidencia.php">Evidencia dochádzky</a>
            <a href="prehlad.php" class="active" aria-current="page">Prehľad dochádzky</a>
        </nav>

        <div class="semester-card">
            <strong>Zimný semester</strong>
            <span>2026 / 2027</span>
            <span><?= $currentWeek ?>. týždeň výučby</span>
        </div>

        <div class="account">
            <small>Prístup iba pre učiteľov</small>
            <strong><?= h($_SESSION['user_name'] ?? 'Ján Novák') ?></strong>
            <span><?= h($_SESSION['user_role'] ?? 'Administrátor') ?></span>
            <form action="prihlasenie.php" method="GET">
                <button type="submit">Odhlásiť sa</button>
            </form>
        </div>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <button class="btn btn-light btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Otvoriť navigáciu">☰</button>
            <span class="year">Akademický rok 2026 / 2027</span>
            <span class="current">Zimný semester 2026 / 2027</span>
        </header>

        <main class="screen">
            <div class="page-heading">
                <h1>Prehľad dochádzky</h1>
                <p>Dochádzka študentov po týždňoch a prehľad neprítomností.</p>
            </div>

            <form class="toolbar" method="GET" action="prehlad.php">
                <label class="visually-hidden" for="vyucba">Výučba</label>
                <select class="form-select select-plain bg-white" id="vyucba" name="course_id" onchange="this.form.submit()">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$c['id'] === $courseId ? 'selected' : '' ?>>
                            <?= h($c['subject'] . ' · ' . ($typeLabels[$c['lesson_type']] ?? '') . ($c['group_name'] ? ' · ' . $c['group_name'] : '') . ' · ' . ($dayNames[$c['weekday']] ?? '') . ' ' . $c['starts_at']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="toolbar-meta grow">Zimný semester 2026 / 2027</span>
                <a class="btn btn-primary" href="api/export_csv.php?course_id=<?= $courseId ?>">Export kompletnej dochádzky</a>
            </form>

            <div class="panel summary">
                <div>
                    <div class="stat-number"><?= str_pad((string)count($students), 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="stat-label">Študentov v skupine</div>
                </div>
                <div>
                    <div class="stat-number"><?= str_pad((string)$currentWeek, 2, '0', STR_PAD_LEFT) ?> / 13</div>
                    <div class="stat-label">Aktuálny týždeň / celkom</div>
                </div>
                <div>
                    <div class="stat-number"><?= str_pad((string)$totalAbsent, 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="stat-label">Neospravedlnené absencie</div>
                </div>
                <div>
                    <div class="stat-number"><?= str_pad((string)$totalExcused, 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="stat-label">Ospravedlnené absencie</div>
                </div>
            </div>

            <div class="panel">
                <div class="d-flex align-items-center gap-3 p-4 pb-3">
                    <h2 class="panel-title flex-grow-1">Dochádzka podľa týždňov</h2>
                    <span class="stat-label">Týždne 1 – 13</span>
                </div>

                <div class="matrix-wrap">
                    <table class="matrix">
                        <thead>
                            <tr>
                                <th class="student" scope="col">Študent</th>
                                <?php for ($w = 1; $w <= 13; $w++):
                                    $dt = isset($sessionsByWeek[$w]) ? date('j. n.', strtotime($sessionsByWeek[$w]['session_date'])) : '';
                                ?>
                                <th scope="col" class="<?= $w === $currentWeek ? 'current' : '' ?>">
                                    <?= $w ?>
                                    <small><?= $dt ?></small>
                                </th>
                                <?php endfor; ?>
                                <th class="total" scope="col">Neospr.</th>
                                <th class="total" scope="col">Ospr.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $st):
                                $stAbs = 0; $stExc = 0;
                            ?>
                            <tr>
                                <th class="student" scope="row"><?= h($st['full_name']) ?></th>
                                <?php for ($w = 1; $w <= 13; $w++):
                                    $rec = $matrix[(int)$st['id']][$w] ?? null;
                                    if ($rec && isset($cellMap[$rec['status']])):
                                        [$letter, $cls, $lbl] = $cellMap[$rec['status']];
                                        if ($rec['status'] === 'absent') $stAbs++;
                                        if ($rec['status'] === 'excused') $stExc++;
                                        $title = $w . '. týždeň · ' . $lbl . ($rec['note'] ? ' · ' . $rec['note'] : '');
                                ?>
                                    <td><span class="cell <?= $cls ?>" title="<?= h($title) ?>"><?= $letter ?></span></td>
                                <?php else: ?>
                                    <td><span class="cell none" title="Hodina ešte nebola">—</span></td>
                                <?php endif; endfor; ?>
                                <td class="total"><?= $stAbs ?></td>
                                <td class="total"><?= $stExc ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="legend">
                <span><span class="cell status-present">P</span>Prítomný</span>
                <span><span class="cell status-absent">N</span>Neprítomný</span>
                <span><span class="cell status-excused">O</span>Ospravedlnený</span>
                <span><span class="cell status-replacing">R</span>Nahrádza</span>
                <span><span class="cell none">—</span>Hodina ešte nebola</span>
                <span><span class="cell none">·</span>Výučba sa nekoná</span>
            </div>

            <?php foreach ($replacements as $rep): ?>
            <div class="panel">
                <div class="replacement-detail">
                    <strong>Náhrada · <?= h($rep['full_name']) ?> · <?= (int)$rep['week_number'] ?>. týždeň</strong>
                    <span>
                        Pôvodné cvičenie: <?= h(mb_strtolower($rep['orig_group'] ?? '')) ?>, <?= date('j. n.', strtotime($rep['orig_date'])) ?>
                        →
                        Náhradné cvičenie: <?= h(mb_strtolower($rep['new_group'] ?? '')) ?>,
                        <?= date('j. n.', strtotime($rep['new_date'])) ?> · <?= h($rep['starts_at'] . ' – ' . $rep['ends_at']) ?>
                    </span>
                    <a href="evidencia.php?course_id=<?= (int)$rep['new_course_id'] ?>&session_id=<?= (int)$rep['linked_session_id'] ?>">Otvoriť prepojené cvičenie →</a>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>
</div>

<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
