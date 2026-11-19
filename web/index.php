<?php
require_once __DIR__ . '/../database/db.php';

$initialWeek = max(1, min(13, (int)($_GET['week'] ?? $currentWeek)));
$courses = $pdo->query("SELECT * FROM courses ORDER BY weekday, starts_at, id")->fetchAll();
$subjects = array_values(array_unique(array_column($courses, 'subject')));
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rozvrh · dochádzka</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body>
<div class="app">
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Hlavná navigácia">
        <a class="brand" href="index.php">dochádzka</a>
        <div class="eyebrow">Pracovný priestor</div>

        <nav>
            <a href="index.php" class="active" aria-current="page">Rozvrh</a>
            <a href="evidencia.php">Evidencia dochádzky</a>
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
            <span class="current">Zimný semester</span>
        </header>

        <main class="screen" id="timetableApp"
            data-courses="<?= h(json_encode($courses, JSON_UNESCAPED_UNICODE)) ?>"
            data-initial-week="<?= $initialWeek ?>"
            data-current-week="<?= $currentWeek ?>"
            data-current-weekday="<?= $currentWeekday ?>">
            <div class="page-heading">
                <h1>Rozvrh</h1>
                <p>Váš rozvrh a najbližšie hodiny na jednom mieste.</p>
            </div>

            <div class="toolbar">
                <button class="btn btn-light btn-icon" type="button" id="prevWeekBtn" aria-label="Predchádzajúci týždeň">‹</button>
                <button class="btn btn-light" type="button" id="todayWeekBtn">Dnes</button>
                <button class="btn btn-light btn-icon" type="button" id="nextWeekBtn" aria-label="Nasledujúci týždeň">›</button>
                <h2 class="week-label grow" id="weekLabelHeading"></h2>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#lessonModal">+ Pridať výučbu</button>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="panel panel-body">
                        <div class="stat-label fw-medium mb-3">Hodín tento týždeň</div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="stat-number" id="weekLessonsCount">00</span>
                            <span class="stat-label">Prednášky, semináre a cvičenia</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel panel-body">
                        <div class="stat-label fw-medium mb-3">Predmety</div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="stat-number"><?= str_pad((string)count($subjects), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="stat-label">Zimný semester 2026 / 2027</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel tt">
                <div class="tt-inner">
                    <div class="tt-head">
                        <div class="tt-corner">Hodina <span>Začiatok</span></div>
                        <div class="tt-hours">
                            <?php for ($h = 7; $h <= 20; $h++): ?>
                                <div><small><?= $h - 6 ?></small><strong><?= $h ?>:00</strong></div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?php
                    $shortDays = [1 => 'Pon', 2 => 'Uto', 3 => 'Str', 4 => 'Štv', 5 => 'Pia'];
                    foreach ($shortDays as $dayNum => $shortName):
                    ?>
                    <div class="tt-row" data-day-row="<?= $dayNum ?>">
                        <div class="tt-day">
                            <strong><?= $shortName ?></strong>
                            <span data-day-date></span>
                            <span class="pill d-none" data-day-today>Dnes</span>
                        </div>
                        <div class="tt-track" data-day-track="<?= $dayNum ?>"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
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

<div class="modal fade" id="lessonModal" tabindex="-1" aria-labelledby="lessonTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="--bs-modal-width: 640px">
        <form class="modal-content" id="lessonForm" method="POST" action="api/courses.php" novalidate>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="return_week" id="modalReturnWeek" value="<?= $initialWeek ?>">
            <input type="hidden" name="course_id" id="course_id" value="">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="lessonTitle">Pridať výučbu</h2>
                    <p>Vytvorte novú hodinu v rozvrhu – Zimný semester 2026 / 2027.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavrieť"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="subject">Predmet</label>
                        <input class="form-control" type="text" id="subject" name="subject" list="subjectList"
                            value="" placeholder="Vyberte alebo napíšte nový" autocomplete="off" required>
                        <datalist id="subjectList">
                            <?php foreach ($subjects as $subj): ?>
                                <option value="<?= h($subj) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="type">Typ výučby</label>
                        <select class="form-select" id="type" name="type">
                            <option value="lecture">Prednáška</option>
                            <option value="seminar">Seminár</option>
                            <option value="exercise" selected>Cvičenie</option>
                            <option value="lab">Lab. cvičenie</option>
                        </select>
                    </div>

                    <div class="col-sm-6">
                        <label class="form-label" for="room">Miestnosť <span class="optional">voliteľné</span></label>
                        <input class="form-control" type="text" id="room" name="room"
                            value="" placeholder="napr. A-101">
                    </div>

                    <div class="col-sm-4" data-hide-once>
                        <label class="form-label" for="weekday">Deň</label>
                        <select class="form-select" id="weekday" name="weekday">
                            <option value="1" selected>Pondelok</option>
                            <option value="2">Utorok</option>
                            <option value="3">Streda</option>
                            <option value="4">Štvrtok</option>
                            <option value="5">Piatok</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-4">
                        <label class="form-label" for="starts_at">Začiatok</label>
                        <input class="form-control" type="time" step="300" id="starts_at" name="starts_at"
                            value="08:00" required>
                    </div>
                    <div class="col-6 col-sm-4">
                        <label class="form-label" for="ends_at">Koniec</label>
                        <input class="form-control" type="time" step="300" id="ends_at" name="ends_at"
                            value="09:30" required>
                    </div>
                </div>

                <div>
                    <div class="form-label" id="recurrenceLabel">Opakovanie</div>
                    <div class="chips" role="radiogroup" aria-labelledby="recurrenceLabel">
                        <input class="btn-check" type="radio" name="recurrence" id="recurrence-weekly"
                            value="weekly" checked>
                        <label for="recurrence-weekly">Každý týždeň</label>
                        <input class="btn-check" type="radio" name="recurrence" id="recurrence-biweekly"
                            value="biweekly">
                        <label for="recurrence-biweekly">Každý druhý týždeň</label>
                        <input class="btn-check" type="radio" name="recurrence" id="recurrence-once"
                            value="once">
                        <label for="recurrence-once">Jednorazovo</label>
                    </div>
                </div>

                <div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="valid_from" data-from-label>Platí od</label>
                            <input class="form-control" type="date" id="valid_from" name="valid_from"
                                min="2026-09-14" max="2026-12-11"
                                value="2026-09-14" required>
                        </div>
                        <div class="col-sm-6" data-hide-once>
                            <label class="form-label" for="valid_to">Platí do</label>
                            <input class="form-control" type="date" id="valid_to" name="valid_to"
                                min="2026-09-14" max="2026-12-11"
                                value="2026-12-11">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-wide" data-bs-dismiss="modal">Zrušiť</button>
                <button type="submit" class="btn btn-primary btn-wide">Uložiť výučbu</button>
            </div>
        </form>
    </div>
</div>

<script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
