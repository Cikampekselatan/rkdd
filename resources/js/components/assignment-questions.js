function reindex(list) {
    [...list.querySelectorAll('[data-assignment-question-row]')].forEach((row, index) => {
        row.querySelector('[data-assignment-question-number]').textContent = index + 1;
        row.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace(/questions\[\d+|questions\[__INDEX__/g, `questions[${index}`);
        });
    });
}

function toggleOptions(row) {
    const type = row.querySelector('[data-assignment-question-type]');
    const options = row.querySelector('[data-assignment-question-options]');
    if (!type || !options) return;

    options.classList.toggle('d-none', type.value !== 'multiple_choice');
}

document.addEventListener('DOMContentLoaded', () => {
    const list = document.querySelector('[data-assignment-question-list]');
    const template = document.querySelector('[data-assignment-question-template]');
    const addButton = document.querySelector('[data-assignment-question-add]');

    if (!list || !template || !addButton) return;

    list.querySelectorAll('[data-assignment-question-row]').forEach(toggleOptions);

    addButton.addEventListener('click', () => {
        const index = list.querySelectorAll('[data-assignment-question-row]').length;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', index).trim();
        const row = wrapper.firstElementChild;
        list.append(row);
        toggleOptions(row);
        reindex(list);
        row.querySelector('input[name$="[prompt]"]')?.focus();
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-assignment-question-remove]');
        if (!removeButton) return;

        const rows = list.querySelectorAll('[data-assignment-question-row]');
        if (rows.length <= 1) {
            const row = removeButton.closest('[data-assignment-question-row]');
            row.querySelectorAll('input:not([type="hidden"]), textarea').forEach((field) => {
                field.value = '';
            });
            row.querySelector('[data-assignment-question-type]').value = 'paragraph';
            toggleOptions(row);
            return;
        }

        removeButton.closest('[data-assignment-question-row]').remove();
        reindex(list);
    });

    list.addEventListener('change', (event) => {
        const type = event.target.closest('[data-assignment-question-type]');
        if (!type) return;

        toggleOptions(type.closest('[data-assignment-question-row]'));
    });
});
