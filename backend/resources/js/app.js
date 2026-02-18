import './bootstrap';

const root = document.querySelector('[data-analysis-builder]');

if (root) {
    const catalog = JSON.parse(root.dataset.catalog || '[]');
    const selected = new Set(JSON.parse(root.dataset.selected || '[]').map((value) => Number(value)));
    const oldResults = JSON.parse(root.dataset.oldResults || '{}');
    const panel = root.querySelector('[data-parameter-panel]');

    const labels = {
        noSubcategory: root.dataset.msgNoSubcategory || 'General',
        parameter: root.dataset.msgParameter || 'Analysis',
        result: root.dataset.msgResult || 'Result',
        reference: root.dataset.msgReference || 'Reference',
        unit: root.dataset.msgUnit || 'Unit',
        hint: root.dataset.msgSelectHint || 'Select one analysis to continue.',
    };

    const escapeHtml = (unsafe) => String(unsafe ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const renderInput = (parameter) => {
        const fieldName = `results[${parameter.id}]`;
        const oldValue = oldResults[String(parameter.id)] ?? '';

        if (parameter.value_type === 'number') {
            return `<input type="number" step="any" name="${fieldName}" value="${escapeHtml(oldValue)}">`;
        }

        if (parameter.value_type === 'list') {
            const options = (parameter.options || [])
                .map((option) => {
                    const selectedAttr = String(oldValue) === String(option) ? 'selected' : '';
                    return `<option value="${escapeHtml(option)}" ${selectedAttr}>${escapeHtml(option)}</option>`;
                })
                .join('');

            return `<select name="${fieldName}"><option value=""></option>${options}</select>`;
        }

        return `<input type="text" name="${fieldName}" value="${escapeHtml(oldValue)}">`;
    };

    const render = () => {
        if (!panel) {
            return;
        }

        const selectedCategories = [];

        catalog.forEach((discipline) => {
            discipline.categories.forEach((category) => {
                if (selected.has(Number(category.id))) {
                    selectedCategories.push({ discipline, category });
                }
            });
        });

        if (selectedCategories.length === 0) {
            panel.innerHTML = `<p class="lms-muted">${escapeHtml(labels.hint)}</p>`;
            return;
        }

        const html = selectedCategories.map(({ discipline, category }) => {
            const subcategoryMap = new Map();

            category.parameters.forEach((parameter) => {
                const key = parameter.subcategory_id ? String(parameter.subcategory_id) : 'none';

                if (!subcategoryMap.has(key)) {
                    const subcategory = category.subcategories.find((entry) => String(entry.id) === key);
                    subcategoryMap.set(key, {
                        label: subcategory?.label || labels.noSubcategory,
                        rows: [],
                    });
                }

                subcategoryMap.get(key).rows.push(parameter);
            });

            const subcategoryRows = [...subcategoryMap.values()].map((group) => {
                const rows = group.rows.map((parameter) => `
                    <tr>
                        <td>${escapeHtml(parameter.label)}</td>
                        <td>${renderInput(parameter)}</td>
                        <td>${escapeHtml(parameter.reference || '-')}</td>
                        <td>${escapeHtml(parameter.unit || '-')}</td>
                    </tr>
                `).join('');

                return `
                    <h6>${escapeHtml(group.label)}</h6>
                    <table class="lms-table">
                        <thead>
                            <tr>
                                <th>${escapeHtml(labels.parameter)}</th>
                                <th>${escapeHtml(labels.result)}</th>
                                <th>${escapeHtml(labels.reference)}</th>
                                <th>${escapeHtml(labels.unit)}</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                `;
            }).join('');

            return `
                <div class="lms-card lms-stack">
                    <p class="lms-muted">${escapeHtml(discipline.label)}</p>
                    <h5>${escapeHtml(category.label)}</h5>
                    ${subcategoryRows}
                </div>
            `;
        }).join('');

        panel.innerHTML = html;
    };

    root.querySelectorAll('input[name="selected_categories[]"]').forEach((checkbox) => {
        checkbox.addEventListener('change', (event) => {
            const value = Number(event.currentTarget.value);

            if (event.currentTarget.checked) {
                selected.add(value);
            } else {
                selected.delete(value);
            }

            render();
        });
    });

    render();
}
