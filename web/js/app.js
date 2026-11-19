document.addEventListener('DOMContentLoaded', () => {
    forms();
    confirmDialog();
    statusSelects();
    lessonDialog();
    manualDialog();
    timetableApp();

    document.querySelectorAll('[data-proto-note]').forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            toast(el.dataset.protoNote);
        });
    });
});

function toast(text) {
    document.querySelector('.proto-toast')?.remove();
    const el = document.createElement('div');
    el.className = 'proto-toast';
    el.setAttribute('role', 'status');
    el.textContent = text;
    document.body.append(el);
    setTimeout(() => el.remove(), 3500);
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}

function forms() {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirm) {
                event.preventDefault();
                return;
            }
            if (form.getAttribute('action')) return;

            event.preventDefault();
            if (form.dataset.go) {
                window.location.href = form.dataset.go;
                return;
            }
            const modal = form.closest('.modal');
            if (modal) bootstrap.Modal.getOrCreateInstance(modal).hide();
        });
    });
}

function confirmDialog() {
    const modalEl = document.getElementById('confirmModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    let pending = null;

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;
        event.preventDefault();
        pending = form;
        modalEl.querySelector('[data-confirm-text]').textContent = form.dataset.confirm;
        modal.show();
    });

    modalEl.querySelector('[data-confirm-ok]').addEventListener('click', () => {
        if (!pending) return;
        const formToSubmit = pending;
        pending = null;
        modal.hide();
        formToSubmit.submit();
    });
}

function statusSelects() {
    document.querySelectorAll('select.status').forEach((select) => {
        select.addEventListener('change', async () => {
            select.className = 'status status-' + select.value;
            const form = select.closest('form');
            const payload = {
                session_id: form.querySelector('[name=session_id]').value,
                student_id: form.querySelector('[name=student_id]').value,
                status: select.value
            };
            const res = await fetch('api/attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (res.ok) {
                toast('Stav dochádzky uložený do databázy.');
            }
        });
    });
}

function lessonDialog() {
    const form = document.getElementById('lessonForm');
    const modalEl = document.getElementById('lessonModal');
    if (!form || !modalEl) return;

    const toggleOnce = () => {
        const once = form.querySelector('[name=recurrence]:checked')?.value === 'once';
        form.querySelectorAll('[data-hide-once]').forEach((el) => el.classList.toggle('d-none', once));
        form.querySelector('[data-from-label]').textContent = once ? 'Dátum' : 'Platí od';
    };

    modalEl.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        const isEdit = trigger && trigger.classList.contains('edit');

        document.getElementById('lessonTitle').textContent = isEdit ? 'Upraviť výučbu' : 'Pridať výučbu';
        form.querySelector('[name=course_id]').value = isEdit ? trigger.dataset.courseId : '';
        form.querySelector('[name=subject]').value = isEdit ? trigger.dataset.subject : '';
        form.querySelector('[name=type]').value = isEdit ? trigger.dataset.type : 'exercise';
        form.querySelector('[name=room]').value = isEdit ? trigger.dataset.room : '';
        form.querySelector('[name=weekday]').value = isEdit ? trigger.dataset.weekday : '1';
        form.querySelector('[name=starts_at]').value = isEdit ? trigger.dataset.starts : '08:00';
        form.querySelector('[name=ends_at]').value = isEdit ? trigger.dataset.ends : '09:30';
        form.querySelector('[name=valid_from]').value = isEdit ? trigger.dataset.from : '2026-09-14';
        form.querySelector('[name=valid_to]').value = isEdit ? (trigger.dataset.to || '2026-12-11') : '2026-12-11';

        const rec = isEdit ? trigger.dataset.recurrence : 'weekly';
        const radio = form.querySelector(`[name=recurrence][value="${rec}"]`);
        if (radio) radio.checked = true;

        toggleOnce();
    });

    form.addEventListener('change', toggleOnce);
    toggleOnce();
}

function manualDialog() {
    const search = document.querySelector('[data-student-search]');
    if (search) {
        search.addEventListener('input', () => {
            const query = normalize(search.value);
            document.querySelectorAll('[data-student]').forEach((row) => {
                row.classList.toggle('d-none', !normalize(row.dataset.student).includes(query));
            });
        });
    }

    document.querySelectorAll('[data-now]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelector(button.dataset.now).value = new Date().toTimeString().slice(0, 8);
        });
    });
}

function normalize(text) {
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function timetableApp() {
    const root = document.getElementById('timetableApp');
    if (!root) return;

    const courses = JSON.parse(root.dataset.courses || '[]');
    const currentWeek = parseInt(root.dataset.currentWeek, 10) || 1;
    const currentWeekday = parseInt(root.dataset.currentWeekday, 10) || 0;
    let week = parseInt(root.dataset.initialWeek, 10) || currentWeek;

    const prevBtn = document.getElementById('prevWeekBtn');
    const nextBtn = document.getElementById('nextWeekBtn');
    const todayBtn = document.getElementById('todayWeekBtn');
    const heading = document.getElementById('weekLabelHeading');
    const countEl = document.getElementById('weekLessonsCount');
    const returnWeekInput = document.getElementById('modalReturnWeek');

    const monthsSk = [
        'januára', 'februára', 'marca', 'apríla', 'mája', 'júna',
        'júla', 'augusta', 'septembra', 'októbra', 'novembra', 'decembra'
    ];
    const typeLabels = {
        lecture: 'Prednáška',
        seminar: 'Seminár',
        exercise: 'Cvičenie',
        lab: 'Lab. cvičenie'
    };
    const typeColors = {
        lecture: 'c0',
        seminar: 'c1',
        exercise: 'c2',
        lab: 'c4'
    };

    const timeToCol = (t) => {
        const [h, m] = t.split(':').map(Number);
        return ((h - 7) * 12) + Math.floor(m / 5) + 1;
    };
    const durationToSpan = (s, e) => Math.max(6, timeToCol(e) - timeToCol(s));

    const toIsoDate = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    function render() {
        prevBtn.disabled = week <= 1;
        nextBtn.disabled = week >= 13;
        if (returnWeekInput) returnWeekInput.value = week;

        const url = new URL(window.location);
        url.searchParams.set('week', week);
        window.history.replaceState({}, '', url);

        const monday = new Date(2026, 8, 14 + (week - 1) * 7);
        const friday = new Date(monday);
        friday.setDate(monday.getDate() + 4);

        heading.innerHTML = `${monday.getDate()}. – ${friday.getDate()}. ${monthsSk[friday.getMonth()]} ${friday.getFullYear()} <span class="toolbar-meta ms-2">${week}. týždeň výučby</span>`;

        let totalVisible = 0;

        for (let d = 1; d <= 5; d++) {
            const dayDate = new Date(monday);
            dayDate.setDate(monday.getDate() + (d - 1));
            const isoStr = toIsoDate(dayDate);

            const rowEl = root.querySelector(`[data-day-row="${d}"]`);
            const trackEl = root.querySelector(`[data-day-track="${d}"]`);
            const isToday = (week === currentWeek && d === currentWeekday);

            rowEl.classList.toggle('today', isToday);
            rowEl.querySelector('[data-day-date]').textContent = `${dayDate.getDate()}. ${dayDate.getMonth() + 1}.`;
            rowEl.querySelector('[data-day-today]').classList.toggle('d-none', !isToday);

            const dayCourses = courses
                .filter((c) => {
                    if (parseInt(c.weekday, 10) !== d) return false;
                    if (c.valid_from && isoStr < c.valid_from) return false;
                    if (c.valid_to && isoStr > c.valid_to) return false;
                    if (c.recurrence === 'biweekly' && (week % 2 === 0)) return false;
                    return true;
                })
                .sort((a, b) => a.starts_at.localeCompare(b.starts_at));

            totalVisible += dayCourses.length;

            const laneEnds = [];
            trackEl.innerHTML = dayCourses.map((c) => {
                let assignedLane = -1;
                for (let i = 0; i < laneEnds.length; i++) {
                    if (laneEnds[i] <= c.starts_at) {
                        assignedLane = i;
                        break;
                    }
                }
                if (assignedLane === -1) {
                    assignedLane = laneEnds.length;
                    laneEnds.push(c.ends_at);
                } else {
                    laneEnds[assignedLane] = c.ends_at;
                }
                const laneNum = assignedLane + 1;

                const col = timeToCol(c.starts_at);
                const span = durationToSpan(c.starts_at, c.ends_at);
                let sub = typeLabels[c.lesson_type] || c.lesson_type;
                if (c.group_name) sub += ' · ' + c.group_name.replace('Skupina ', '');
                if (c.room) sub += ' · ' + c.room;

                const noteHtml = c.recurrence === 'biweekly' ? '<div class="note">Každý 2. týždeň</div>' : '';

                return `
                <div class="tt-block ${typeColors[c.lesson_type] || 'c0'}"
                    style="--col: ${col}; --span: ${span}; --lane: ${laneNum}"
                    title="${escapeHtml(c.subject + ' · ' + sub + ' · ' + c.starts_at + ' – ' + c.ends_at)}">
                    <a class="title stretched-link" href="evidencia.php?course_id=${c.id}&week=${week}">${escapeHtml(c.subject)}</a>
                    ${escapeHtml(sub)}
                    ${noteHtml}
                    <a class="edit" href="#" data-bs-toggle="modal" data-bs-target="#lessonModal"
                        data-course-id="${c.id}"
                        data-subject="${escapeHtml(c.subject)}"
                        data-type="${escapeHtml(c.lesson_type)}"
                        data-room="${escapeHtml(c.room || '')}"
                        data-weekday="${c.weekday}"
                        data-starts="${escapeHtml(c.starts_at)}"
                        data-ends="${escapeHtml(c.ends_at)}"
                        data-recurrence="${escapeHtml(c.recurrence)}"
                        data-from="${escapeHtml(c.valid_from)}"
                        data-to="${escapeHtml(c.valid_to || '')}"
                        aria-label="Upraviť výučbu ${escapeHtml(c.subject)}" title="Upraviť">✎</a>
                    <form method="POST" action="api/courses.php"
                        data-confirm="Výučba „${escapeHtml(c.subject)}“ sa odstráni z databázy.">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="return_week" value="${week}">
                        <input type="hidden" name="course_id" value="${c.id}">
                        <button class="remove" type="submit" aria-label="Odstrániť výučbu">×</button>
                    </form>
                </div>`;
            }).join('');
        }

        countEl.textContent = String(totalVisible).padStart(2, '0');
    }

    prevBtn.addEventListener('click', () => { if (week > 1) { week--; render(); } });
    nextBtn.addEventListener('click', () => { if (week < 13) { week++; render(); } });
    todayBtn.addEventListener('click', () => { week = currentWeek; render(); });

    render();
}
