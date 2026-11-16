// Dochádzka – klikateľný prototyp. Stránky sú statické: formuláre nič neukladajú,
// iba ukazujú, ako sa bude rozhranie správať.

document.addEventListener('DOMContentLoaded', () => {
    forms();
    confirmDialog();
    statusSelects();
    lessonDialog();
    manualDialog();

    document.querySelectorAll('[data-proto-note]').forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            toast(el.dataset.protoNote);
        });
    });
});

// Krátka správa v spodnej časti obrazovky.
function toast(text) {
    document.querySelector('.proto-toast')?.remove();
    const el = document.createElement('div');
    el.className = 'proto-toast';
    el.setAttribute('role', 'status');
    el.textContent = text;
    document.body.append(el);
    setTimeout(() => el.remove(), 3500);
}

// Formuláre: buď prejdú na ďalšiu obrazovku (data-go), alebo len zatvoria dialóg.
function forms() {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (form.dataset.confirm) return; // rieši confirmDialog()

            if (form.dataset.go) {
                window.location.href = form.dataset.go;
                return;
            }

            const modal = form.closest('.modal');
            if (modal) bootstrap.Modal.getOrCreateInstance(modal).hide();
            toast('Prototyp – údaje sa zatiaľ neukladajú.');
        });
    });
}

// Potvrdenie pred odstránením: <form data-confirm="Text otázky">
function confirmDialog() {
    const modalEl = document.getElementById('confirmModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    let pending = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', () => {
            pending = form;
            modalEl.querySelector('[data-confirm-text]').textContent = form.dataset.confirm;
            modal.show();
        });
    });

    modalEl.querySelector('[data-confirm-ok]').addEventListener('click', () => {
        if (!pending) return;
        pending.closest('.tt-block, .roster-row')?.remove();
        pending = null;
        modal.hide();
        toast('Odstránené iba v ukážke – po obnovení stránky sa položka vráti.');
    });
}

// Zmena stavu v zozname študentov prefarbí štítok.
function statusSelects() {
    document.querySelectorAll('select.status').forEach((select) => {
        select.addEventListener('change', () => {
            select.className = 'status status-' + select.value;
        });
    });
}

// Dialóg „Pridať výučbu“: pri jednorazovej výučbe sa skryje deň a „platí do“.
function lessonDialog() {
    const form = document.getElementById('lessonForm');
    if (!form) return;

    const toggleOnce = () => {
        const once = form.querySelector('[name=recurrence]:checked')?.value === 'once';
        form.querySelectorAll('[data-hide-once]').forEach((el) => el.classList.toggle('d-none', once));
        form.querySelector('[data-from-label]').textContent = once ? 'Dátum' : 'Platí od';
    };

    form.addEventListener('change', toggleOnce);
    toggleOnce();
}

// Dialóg „Pridať ručný záznam“: hľadanie študenta a tlačidlo „Teraz“.
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
    return text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}
