const VALID_TYPES = new Set(['number', 'text', 'list']);

const getSupportedTypes = (field) => {
    const rawValue = field.dataset.valueTypeField ?? '';

    return rawValue
        .split(' ')
        .map((value) => value.trim())
        .filter((value) => value !== '');
};

export const initializeValueTypeForm = (form, typeFormState) => {
    const hiddenInput = form.querySelector('[data-value-type-hidden]');
    const typeChoices = Array.from(form.querySelectorAll('[data-value-type-choice]'));

    if (!hiddenInput || typeChoices.length === 0) {
        return;
    }

    const typedFields = Array.from(form.querySelectorAll('[data-value-type-field]'));
    const defaultToggle = form.querySelector('[data-default-option-toggle]');
    const defaultWrap = form.querySelector('[data-default-option-wrap]');
    const defaultInput = form.querySelector('[data-default-option-input]');

    const refresh = () => {
        let activeType = hiddenInput.value;

        if (!VALID_TYPES.has(activeType)) {
            activeType = 'number';
            hiddenInput.value = activeType;
        }

        typeChoices.forEach((choice) => {
            choice.checked = choice.value === activeType;
        });

        typedFields.forEach((field) => {
            const visible = getSupportedTypes(field).includes(activeType);
            field.hidden = !visible;

            field.querySelectorAll('input, select, textarea').forEach((input) => {
                if (input === hiddenInput || input.type === 'hidden') {
                    return;
                }

                if (input === defaultToggle) {
                    return;
                }

                input.disabled = !visible;
            });
        });

        if (!defaultToggle || !defaultWrap || !defaultInput) {
            return;
        }

        const optionsMode = activeType === 'list';
        defaultToggle.disabled = !optionsMode;

        if (!optionsMode) {
            defaultToggle.checked = false;
            defaultWrap.hidden = true;
            defaultInput.disabled = true;
            defaultInput.value = '';
            return;
        }

        defaultWrap.hidden = !defaultToggle.checked;
        defaultInput.disabled = !defaultToggle.checked;

        if (!defaultToggle.checked) {
            defaultInput.value = '';
        }
    };

    typeChoices.forEach((choice) => {
        choice.addEventListener('change', () => {
            if (choice.checked) {
                hiddenInput.value = choice.value;
                refresh();
                return;
            }

            if (hiddenInput.value === choice.value) {
                choice.checked = true;
            }
        });
    });

    if (defaultToggle) {
        defaultToggle.addEventListener('change', () => {
            refresh();
        });
    }

    typeFormState.set(form, { refresh });
    refresh();
};

export const refreshValueTypeForm = (form, typeFormState) => {
    const state = typeFormState.get(form);

    if (state) {
        state.refresh();
    }
};

export const refreshValueTypeFormsInSection = (section, typeFormState) => {
    if (!section) {
        return;
    }

    section.querySelectorAll('form').forEach((form) => refreshValueTypeForm(form, typeFormState));
};

export const syncSubcategoryOptions = (categorySelect, subcategorySelect) => {
    if (!categorySelect || !subcategorySelect) {
        return;
    }

    const selectedCategory = categorySelect.value;
    const options = subcategorySelect.querySelectorAll('option[data-category-id]');

    options.forEach((option) => {
        option.hidden = option.dataset.categoryId !== selectedCategory;
    });

    const currentValue = subcategorySelect.value;
    const selectedOption = Array.from(subcategorySelect.options).find((option) => option.value === currentValue);

    if (currentValue !== '' && selectedOption && selectedOption.hidden) {
        subcategorySelect.value = '';
    }
};
