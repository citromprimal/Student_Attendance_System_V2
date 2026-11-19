PRAGMA foreign_keys = ON;

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT DEFAULT 'Administrátor'
);

CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_code TEXT UNIQUE NOT NULL,
    isic_uid TEXT UNIQUE,
    full_name TEXT NOT NULL
);

CREATE TABLE courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    subject TEXT NOT NULL,
    lesson_type TEXT NOT NULL,
    group_name TEXT,
    room TEXT,
    weekday INTEGER NOT NULL,
    starts_at TEXT NOT NULL,
    ends_at TEXT NOT NULL,
    recurrence TEXT NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE,
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

CREATE TABLE enrollments (
    course_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    PRIMARY KEY (course_id, student_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    week_number INTEGER NOT NULL,
    session_date DATE NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'absent',
    arrived_at TEXT,
    source TEXT DEFAULT 'nfc',
    note TEXT,
    linked_session_id INTEGER,
    is_incoming INTEGER DEFAULT 0,
    UNIQUE(session_id, student_id),
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (linked_session_id) REFERENCES sessions(id)
);

INSERT INTO users (id, email, password_hash, full_name, role) VALUES
(1, 'jan.novak@stuba.sk', 'demo', 'Ján Novák', 'Administrátor');

INSERT INTO courses (id, teacher_id, subject, lesson_type, group_name, room, weekday, starts_at, ends_at, recurrence, valid_from, valid_to) VALUES
(1, 1, 'Programovanie', 'lecture', NULL, 'A-101', 1, '08:00', '09:30', 'weekly', '2026-09-14', '2026-12-11'),
(2, 1, 'Databázové systémy', 'exercise', 'Skupina A', 'PC-2', 2, '10:00', '11:30', 'weekly', '2026-09-14', '2026-12-11'),
(3, 1, 'Databázové systémy', 'exercise', 'Skupina B', 'PC-3', 2, '10:00', '11:30', 'weekly', '2026-09-14', '2026-12-11'),
(4, 1, 'Programovanie', 'exercise', NULL, 'PC-1', 4, '13:00', '14:30', 'biweekly', '2026-09-14', '2026-12-11'),
(5, 1, 'Databázové systémy', 'exercise', 'Skupina C', 'PC-2', 4, '15:00', '16:30', 'weekly', '2026-09-14', '2026-12-11');

INSERT INTO students (id, student_code, isic_uid, full_name) VALUES
(1, '210012', '04A1B2C301', 'Adam Baláž'),
(4, '210045', '04A1B2C304', 'Eva Horváthová'),
(3, '210068', '04A1B2C303', 'Martin Duda'),
(7, '210136', '04A1B2C307', 'Peter Novák'),
(5, '210102', '04A1B2C305', 'Tomáš Kováč'),
(13, '210201', '04A1B2C313', 'Filip Gajdoš'),
(16, '210202', '04A1B2C316', 'Ema Krajčíová'),
(17, '210203', '04A1B2C317', 'Michal Lukáč'),
(18, '210204', '04A1B2C318', 'Natália Mináriková'),
(19, '210205', '04A1B2C319', 'Laura Tóthová');

INSERT INTO enrollments (course_id, student_id) VALUES
(2, 1), (2, 4), (2, 3), (2, 7), (2, 5),
(3, 13), (3, 16),
(5, 17), (5, 18), (5, 19);

-- Týždne 1..13 pre kurz 2 (Databázové systémy - Skupina A)
INSERT INTO sessions (id, course_id, week_number, session_date) VALUES
(1, 2, 1, '2026-09-15'),
(2, 2, 2, '2026-09-22'),
(3, 2, 3, '2026-09-29'),
(4, 2, 4, '2026-10-06'),
(5, 2, 5, '2026-10-13'),
(6, 2, 6, '2026-10-20'),
(7, 2, 7, '2026-10-27'),
(8, 2, 8, '2026-11-03'),
(9, 2, 9, '2026-11-10'),
(10, 2, 10, '2026-11-19'),
(11, 2, 11, '2026-11-24'),
(12, 2, 12, '2026-12-01'),
(13, 2, 13, '2026-12-08'),
(14, 5, 9, '2026-11-12');

-- Historická dochádzka týždne 1..8 pre Skupinu A
INSERT INTO attendance (session_id, student_id, status) VALUES
(1, 1, 'present'), (1, 4, 'present'), (1, 3, 'present'), (1, 7, 'present'), (1, 5, 'present'),
(2, 1, 'present'), (2, 4, 'present'), (2, 3, 'absent'),  (2, 7, 'present'), (2, 5, 'excused'),
(3, 1, 'present'), (3, 4, 'present'), (3, 3, 'present'), (3, 7, 'present'), (3, 5, 'present'),
(4, 1, 'present'), (4, 4, 'present'), (4, 3, 'present'), (4, 7, 'absent'),  (4, 5, 'present'),
(5, 1, 'present'), (5, 4, 'present'), (5, 3, 'excused'), (5, 7, 'present'), (5, 5, 'present'),
(6, 1, 'present'), (6, 4, 'absent'),  (6, 3, 'present'), (6, 7, 'present'), (6, 5, 'present'),
(7, 1, 'present'), (7, 4, 'present'), (7, 3, 'present'), (7, 7, 'present'), (7, 5, 'present'),
(8, 1, 'present'), (8, 4, 'present'), (8, 3, 'present'), (8, 7, 'present'), (8, 5, 'excused');

-- 9. týždeň (session_id = 9, 10. 11. 2026) - aktuálna hodina
INSERT INTO attendance (session_id, student_id, status, arrived_at, source, note, linked_session_id, is_incoming) VALUES
(9, 1, 'present', '09:57:14', 'nfc', NULL, NULL, 0),
(9, 4, 'present', '09:59:18', 'nfc', NULL, NULL, 0),
(9, 3, 'absent', NULL, 'nfc', NULL, NULL, 0),
(9, 7, 'replacing', NULL, 'manual', NULL, 14, 0),
(9, 5, 'excused', NULL, 'manual', 'Ospravedlnenie od lekára', NULL, 0),
(9, 19, 'present', '10:03:42', 'nfc', NULL, 14, 1),
(14, 19, 'replacing', NULL, 'manual', NULL, 9, 0);
