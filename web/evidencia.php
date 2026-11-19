<?php
require_once __DIR__ . '/../database/db.php';

$courses = $pdo->query("SELECT * FROM courses ORDER BY id")->fetchAll();
$courseId = (int)($_GET['course_id'] ?? 2);

$sessStmt = $pdo->prepare("SELECT * FROM sessions WHERE course_id = ? ORDER BY week_number");
$sessStmt->execute([$courseId]);
$sessions = $sessStmt->fetchAll();

if (empty($sessions)) {
    $pdo->prepare("INSERT INTO sessions (course_id, week_number, session_date) VALUES (?, 9, '2026-11-10')")->execute([$courseId]);
    $sessStmt->execute([$courseId]);
    $sessions = $sessStmt->fetchAll();
}

$sessionId = (int)($_GET['session_id'] ?? 0);
$targetWeek = (int)($_GET['week'] ?? $currentWeek);
if (!$sessionId) {
    foreach ($sessions as $s) {
        if ((int)$s['week_number'] === $targetWeek) { $sessionId = (int)$s['id']; break; }
    }
    if (!$sessionId) $sessionId = (int)$sessions[0]['id'];
}

$curSessionStmt = $pdo->prepare("
    SELECT s.*, c.subject, c.lesson_type, c.group_name, c.room, c.weekday, c.starts_at, c.ends_at
    FROM sessions s JOIN courses c ON c.id = s.course_id
    WHERE s.id = ?
");
$curSessionStmt->execute([$sessionId]);
$currentSession = $curSessionStmt->fetch();
$courseId = (int)$currentSession['course_id'];

$rosterStmt = $pdo->prepare("
    SELECT s.id, s.full_name, s.student_code, a.status, a.arrived_at, a.source, a.note, a.linked_session_id, ls.session_date AS linked_date, lc.group_name AS linked_group, lc.starts_at AS linked_start, lc.ends_at AS linked_end
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    LEFT JOIN sessions ls ON ls.id = a.linked_session_id
    LEFT JOIN courses lc ON lc.id = ls.course_id
    WHERE a.session_id = ? AND a.is_incoming = 0
    ORDER BY s.id
");
$rosterStmt->execute([$sessionId]);
$roster = $rosterStmt->fetchAll();

$incStmt = $pdo->prepare("
    SELECT s.id, s.full_name, a.arrived_at, a.linked_session_id, ls.session_date AS orig_date, lc.group_name AS orig_group
    FROM attendance a
    JOIN students s ON s.id = a.student_id
    LEFT JOIN sessions ls ON ls.id = a.linked_session_id
    LEFT JOIN courses lc ON lc.id = ls.course_id
    WHERE a.session_id = ? AND a.is_incoming = 1
");
$incStmt->execute([$sessionId]);
$incoming = $incStmt->fetchAll();

$otherStudentsStmt = $pdo->prepare("
    SELECT s.id, s.full_name, c.group_name, c.weekday, c.starts_at
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    WHERE c.subject = ? AND c.id != ?
    ORDER BY c.group_name, s.full_name
");
$otherStudentsStmt->execute([$currentSession['subject'], $courseId]);
$otherStudents = $otherStudentsStmt->fetchAll();

$lastScanStmt = $pdo->prepare("
    SELECT s.full_name, a.arrived_at
    FROM attendance a JOIN students s ON s.id = a.student_id
    WHERE a.session_id = ? AND a.source = 'nfc' AND a.arrived_at IS NOT NULL
    ORDER BY a.arrived_at DESC LIMIT 1
");
$lastScanStmt->execute([$sessionId]);
$lastScan = $lastScanStmt->fetch();

$counts = ['present' => 0, 'absent' => 0, 'excused' => 0, 'replacing' => 0];
foreach ($roster as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
}
$counts['present'] += count($incoming);

$dayNames = [1 => 'Pondelok', 2 => 'Utorok', 3 => 'Streda', 4 => 'Štvrtok', 5 => 'Piatok'];
$typeLabels = ['lecture' => 'Prednáška', 'seminar' => 'Seminár', 'exercise' => 'Cvičenie', 'lab' => 'Lab. cvičenie'];
$statusLabels = ['present' => 'Prítomný', 'absent' => 'Neprítomný', 'excused' => 'Ospravedlnený', 'replacing' => 'Nahrádza'];
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evidencia dochádzky · dochádzka</title>
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
            <a href="evidencia.php" class="active" aria-current="page">Evidencia dochádzky</a>
            <a href="prehlad.php">Prehľad dochádzky</a>
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
            <span class="current">SQLite databáza · Zimný semester 2026 / 2027</span>
        </header>

        <main class="screen">
            <div class="page-heading">
                <h1>Evidencia dochádzky</h1>
                <p>Živý prehľad príchodov a ručná správa dochádzky.</p>
            </div>

            <form class="toolbar" method="GET" action="evidencia.php">
                <label class="visually-hidden" for="vyucba">Výučba</label>
                <select class="form-select select-plain bg-white" id="vyucba" name="course_id" onchange="this.form.submit()">
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int)$c['id'] === $courseId ? 'selected' : '' ?>>
                            <?= h($c['subject'] . ' · ' . ($typeLabels[$c['lesson_type']] ?? '') . ($c['group_name'] ? ' · ' . $c['group_name'] : '') . ' · ' . ($dayNames[$c['weekday']] ?? '') . ' ' . $c['starts_at']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="visually-hidden" for="datum">Dátum hodiny</label>
                <select class="form-select select-plain bg-white" id="datum" name="session_id" onchange="this.form.submit()">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int)$s['id'] === $sessionId ? 'selected' : '' ?>>
                            <?= (int)$s['week_number'] ?>. týždeň · <?= date('j. n. Y', strtotime($s['session_date'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="toolbar-meta grow">
                    <?= h(($dayNames[$currentSession['weekday']] ?? '') . ' · ' . $currentSession['starts_at'] . ' – ' . $currentSession['ends_at'] . ($currentSession['room'] ? ' · ' . $currentSession['room'] : '')) ?>
                </span>
            </form>

            <div class="totals">
                <div class="panel">
                    <div class="stat-number"><?= str_pad((string)$counts['present'], 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="stat-label">Prítomní</div>
                </div>
                <div class="panel">
                    <div class="stat-number"><?= str_pad((string)$counts['absent'], 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="stat-label">Neprítomní</div>
                </div>
                <div class="panel">
                    <div class="stat-number"><?= str_pad((string)$counts['excused'], 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="stat-label">Ospravedlnení</div>
                </div>
                <div class="panel">
                    <div class="stat-number"><?= str_pad((string)$counts['replacing'], 2, '0', STR_PAD_LEFT) ?></div>
                    <div class="stat-label">Nahrádzajú inde</div>
                </div>
            </div>

            <div class="attendance-grid">
                <div class="panel roster">
                    <div class="roster-heading">
                        <h2 class="panel-title">Študenti na hodine</h2>
                        <button class="btn btn-light" type="button" data-bs-toggle="modal" data-bs-target="#manualModal">+ Pridať ručný záznam</button>
                        <button class="btn btn-light" type="button" data-bs-toggle="modal" data-bs-target="#replacementModal">+ Pridať náhradu</button>
                    </div>

                    <div class="roster-columns">
                        <span>Študent</span><span>Príchod</span><span>Stav</span>
                    </div>

                    <?php foreach ($roster as $r): ?>
                    <div class="roster-row">
                        <span class="name">
                            <?= h($r['full_name']) ?>
                            <?php if ($r['note']): ?>
                                <small title="Poznámka"><?= h($r['note']) ?></small>
                            <?php endif; ?>
                        </span>
                        <span class="time">
                            <?= $r['arrived_at'] ? h($r['arrived_at']) : '—' ?>
                            <?php if ($r['source'] === 'manual' && $r['status'] !== 'replacing'): ?>
                                <em>ručne</em>
                            <?php endif; ?>
                        </span>
                        <form class="state">
                            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                            <input type="hidden" name="student_id" value="<?= $r['id'] ?>">
                            <select class="status status-<?= h($r['status']) ?>" name="status"
                                aria-label="Stav – <?= h($r['full_name']) ?>">
                                <?php foreach ($statusLabels as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= $r['status'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <?php endforeach; ?>

                    <?php foreach ($roster as $r): ?>
                        <?php if ($r['status'] === 'replacing' && $r['linked_date']): ?>
                        <div class="roster-link">
                            <?= h($r['full_name']) ?> →
                            nahrádza v <?= h(mb_strtolower($r['linked_group'] ?? 'inej skupine')) ?>
                            · <?= date('j. n. Y', strtotime($r['linked_date'])) ?> · <?= h($r['linked_start'] . ' – ' . $r['linked_end']) ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (!empty($incoming)): ?>
                    <div class="roster-section">
                        <strong>Náhrady z iných cvičení</strong>
                        <span>Prítomní na tejto hodine · mimo pôvodnej skupiny</span>
                    </div>

                    <?php foreach ($incoming as $inc): ?>
                    <div class="roster-row incoming">
                        <span class="name">
                            <?= h($inc['full_name']) ?>
                            <small>
                                zo <?= h(mb_strtolower($inc['orig_group'] ?? 'inej skupiny')) ?>
                                <?= $inc['orig_date'] ? ' · ' . date('j. n.', strtotime($inc['orig_date'])) : '' ?>
                            </small>
                        </span>
                        <span class="time">
                            <?= $inc['arrived_at'] ? h($inc['arrived_at']) : '—' ?>
                        </span>
                        <div class="state">
                            <span class="status status-present">Prítomný</span>
                            <form method="POST" action="api/attendance.php"
                                data-confirm="Náhrada študenta <?= h($inc['full_name']) ?> sa zruší aj na pôvodnom cvičení.">
                                <input type="hidden" name="action" value="replacement_remove">
                                <input type="hidden" name="session_id" value="<?= $sessionId ?>">
                                <input type="hidden" name="student_id" value="<?= $inc['id'] ?>">
                                <input type="hidden" name="linked_session_id" value="<?= (int)$inc['linked_session_id'] ?>">
                                <button class="btn-remove" type="submit" aria-label="Zrušiť náhradu">×</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <aside class="panel nfc" aria-labelledby="nfcTitle">
                    <h2 class="panel-title" id="nfcTitle">Snímanie ISIC</h2>

                    <span class="status status-present">Dnešná hodina · príjem príchodov</span>

                    <?php if ($lastScan): ?>
                    <div class="last-scan">
                        <strong>Príchod zaznamenaný</strong>
                        <span><?= h($lastScan['full_name']) ?></span>
                        <small><?= h($lastScan['arrived_at']) ?> · NFC / ISIC</small>
                    </div>
                    <?php else: ?>
                    <div class="last-scan idle">
                        <strong>Čaká sa na priloženie karty</strong>
                        <span>Zatiaľ žiadny NFC záznam</span>
                    </div>
                    <?php endif; ?>

                    <p>Ďalšie príchody sa zobrazia automaticky po priložení ISIC karty k telefónu.</p>
                    <p><strong class="text-body fw-semibold">Predvolený stav: neprítomný</strong></p>
                    <p>Stav môžete zmeniť ručne v zozname študentov.</p>
                </aside>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body">
                <h2 class="modal-title" id="confirmTitle">Naozaj odstrániť?</h2>
                <div data-confirm-text></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-wide" data-bs-dismiss="modal">Zrušiť</button>
                <button type="button" class="btn btn-primary btn-wide" data-confirm-ok>Odstrániť</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="manualModal" tabindex="-1" aria-labelledby="manualTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="--bs-modal-width: 600px">
        <form class="modal-content" method="POST" action="api/attendance.php" novalidate>
            <input type="hidden" name="action" value="manual">
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="manualTitle">Pridať ručný záznam</h2>
                    <p>Zapíšte dochádzku študenta, ktorý nemohol použiť ISIC kartu.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavrieť"></button>
            </div>

            <div class="modal-body">
                <div class="lesson-context">
                    <strong><?= h($currentSession['subject'] . ' · ' . ($typeLabels[$currentSession['lesson_type']] ?? '') . ($currentSession['group_name'] ? ' · ' . $currentSession['group_name'] : '')) ?></strong>
                    <span><?= date('j. n. Y', strtotime($currentSession['session_date'])) ?> · <?= h($currentSession['starts_at'] . ' – ' . $currentSession['ends_at']) ?></span>
                </div>

                <div>
                    <label class="form-label" for="studentSearch">Študent</label>
                    <input class="form-control mb-2" type="search" id="studentSearch" data-student-search
                        placeholder="Hľadať podľa mena alebo ID" autocomplete="off">
                    <div class="pick-list">
                        <div class="heading">Študenti v skupine</div>
                        <div class="items">
                            <?php foreach ($roster as $r): ?>
                            <label data-student="<?= h($r['full_name'] . ' ' . $r['student_code']) ?>">
                                <input class="form-check-input m-0" type="radio" name="student_id" value="<?= $r['id'] ?>" required>
                                <span class="who"><?= h($r['full_name']) ?><small>ID <?= h($r['student_code']) ?></small></span>
                                <span class="status status-<?= h($r['status']) ?>"><?= $statusLabels[$r['status']] ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="form-label" id="newStatusLabel">Nový stav</div>
                    <div class="chips" role="radiogroup" aria-labelledby="newStatusLabel">
                        <input class="btn-check" type="radio" name="status" id="manual-present" value="present" checked>
                        <label class="chip-present" for="manual-present"><span class="dot"></span>Prítomný</label>
                        <input class="btn-check" type="radio" name="status" id="manual-absent" value="absent">
                        <label class="chip-absent" for="manual-absent"><span class="dot"></span>Neprítomný</label>
                        <input class="btn-check" type="radio" name="status" id="manual-excused" value="excused">
                        <label class="chip-excused" for="manual-excused"><span class="dot"></span>Ospravedlnený</label>
                        <input class="btn-check" type="radio" name="status" id="manual-replacing" value="replacing">
                        <label class="chip-replacing" for="manual-replacing"><span class="dot"></span>Nahrádza</label>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="arrivedTime">Čas príchodu</label>
                        <div class="input-action">
                            <input class="form-control" type="time" step="1" id="arrivedTime" name="arrived_time"
                                value="<?= date('H:i:s') ?>">
                            <button type="button" data-now="#arrivedTime">Teraz</button>
                        </div>
                        <div class="form-text">Ukladá sa iba pri stave prítomný.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="recordedBy">Zapísal</label>
                        <input class="form-control" type="text" id="recordedBy" value="<?= h($_SESSION['user_name'] ?? 'Ján Novák') ?> · ručne" readonly>
                    </div>
                </div>

                <div>
                    <label class="form-label" for="note">Poznámka <span class="optional">voliteľné</span></label>
                    <textarea class="form-control" id="note" name="note" rows="3" maxlength="255"
                        placeholder="Napr. zabudnutá ISIC karta alebo nefunkčný čip…"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-wide" data-bs-dismiss="modal">Zrušiť</button>
                <button type="submit" class="btn btn-primary btn-wide">Uložiť záznam</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="replacementModal" tabindex="-1" aria-labelledby="replacementTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="--bs-modal-width: 560px">
        <form class="modal-content" method="POST" action="api/attendance.php" novalidate>
            <input type="hidden" name="action" value="replacement_add">
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <input type="hidden" name="week_number" value="<?= (int)$currentSession['week_number'] ?>">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="replacementTitle">Pridať náhradu</h2>
                    <p>Študent z inej skupiny si na tejto hodine nahrádza svoje cvičenie.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavrieť"></button>
            </div>

            <div class="modal-body">
                <div class="lesson-context">
                    <strong><?= h($currentSession['subject'] . ' · ' . ($typeLabels[$currentSession['lesson_type']] ?? '') . ($currentSession['group_name'] ? ' · ' . $currentSession['group_name'] : '')) ?></strong>
                    <span><?= date('j. n. Y', strtotime($currentSession['session_date'])) ?> · <?= h($currentSession['starts_at'] . ' – ' . $currentSession['ends_at']) ?></span>
                </div>

                <div>
                    <label class="form-label" for="replacementStudent">Študent z inej skupiny</label>
                    <select class="form-select" id="replacementStudent" name="student_id" required>
                        <option value="">Vyberte študenta</option>
                        <?php foreach ($otherStudents as $os): ?>
                            <option value="<?= $os['id'] ?>">
                                <?= h($os['full_name'] . ' · ' . mb_strtolower($os['group_name'] ?? '') . ' (' . ($dayNames[$os['weekday']] ?? '') . ' ' . $os['starts_at'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label" for="replacementTime">Čas príchodu <span class="optional">voliteľné</span></label>
                    <div class="input-action">
                        <input class="form-control" type="time" step="1" id="replacementTime" name="arrived_time"
                            value="<?= date('H:i:s') ?>">
                        <button type="button" data-now="#replacementTime">Teraz</button>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-wide" data-bs-dismiss="modal">Zrušiť</button>
                <button type="submit" class="btn btn-primary btn-wide">Pridať náhradu</button>
            </div>
        </form>
    </div>
</div>

<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
