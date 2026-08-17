import './stimulus_bootstrap.js';
import './styles/app.css';

function syncToolbar(form) {
    const boxes = Array.from(form.querySelectorAll('.js-row-check'));
    const selected = boxes.filter((box) => box.checked);
    const selectAll = form.querySelector('#select-all');
    const needsSelection = form.querySelectorAll('.js-needs-selection');

    needsSelection.forEach((button) => {
        button.disabled = selected.length === 0;
    });

    if (!selectAll) {
        return;
    }

    selectAll.checked = boxes.length > 0 && selected.length === boxes.length;
    selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
}

const form = document.getElementById('users-form');

if (form) {
    const selectAll = form.querySelector('#select-all');

    selectAll?.addEventListener('change', () => {
        form.querySelectorAll('.js-row-check').forEach((box) => {
            box.checked = selectAll.checked;
        });
        syncToolbar(form);
    });

    form.querySelectorAll('.js-row-check').forEach((box) => {
        box.addEventListener('change', () => syncToolbar(form));
    });

    syncToolbar(form);
}
