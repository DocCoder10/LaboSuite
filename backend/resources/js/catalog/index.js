import { getCatalogLabels, getCatalogRoutes } from './config';
import {
    initializeValueTypeForm,
    refreshValueTypeForm,
    refreshValueTypeFormsInSection,
    syncSubcategoryOptions,
} from './value-type';

(() => {
    const editorRoot = document.querySelector('[data-catalog-editor]');

    if (!editorRoot) {
        return;
    }

    const labels = getCatalogLabels(editorRoot);
    const routeMap = getCatalogRoutes(editorRoot);

    const sections = {
        discipline: editorRoot.querySelector('[data-editor-section="discipline"]'),
        category: editorRoot.querySelector('[data-editor-section="category"]'),
        subcategory: editorRoot.querySelector('[data-editor-section="subcategory"]'),
        parameter: editorRoot.querySelector('[data-editor-section="parameter"]'),
    };

    const emptyState = editorRoot.querySelector('[data-editor-empty]');
    const addChildButton = document.querySelector('[data-add-child]');
    const deleteConfirmDialog = document.getElementById('modal-confirm-delete');
    const deleteConfirmForm = deleteConfirmDialog?.querySelector('[data-delete-confirm-form]');
    const deleteForceInput = deleteConfirmDialog?.querySelector('[data-delete-force-input]');
    const deleteMessage = deleteConfirmDialog?.querySelector('[data-delete-message]');
    const convertConfirmDialog = document.getElementById('modal-confirm-convert-values');
    const convertConfirmButton = convertConfirmDialog?.querySelector('[data-convert-confirm]');
    const convertCancelButton = convertConfirmDialog?.querySelector('[data-convert-cancel]');
    const addChildDialog = document.getElementById('modal-add-child');
    const addChildForm = addChildDialog?.querySelector('[data-form-add-child]');
    const addChildNameInput = addChildDialog?.querySelector('[data-add-child-name]');
    const addChildParentName = addChildDialog?.querySelector('[data-add-child-parent-name]');

    const draftState = new Map();
    const typeFormState = new WeakMap();
    let currentSelection = null;
    let dragState = null;
    let armedDragNode = null;
    let onConfirmParentConversion = null;
    let pendingAddChildAction = null;

    const fillRoute = (template, id) => template.replace('__ID__', String(id));

    const openDialog = (dialog) => {
        if (!dialog) {
            return;
        }

        if (!dialog.open) {
            dialog.showModal();
        }
    };

    const closeDialog = (dialog) => {
        if (dialog?.open) {
            dialog.close();
        }
    };

    const closeConvertConfirmDialog = () => {
        onConfirmParentConversion = null;
        closeDialog(convertConfirmDialog);
    };

    const openConvertConfirmDialog = (onConfirm) => {
        onConfirmParentConversion = onConfirm;
        openDialog(convertConfirmDialog);
    };

    const requiresParentConversion = (node) => {
        if (!node) {
            return false;
        }

        if (node.dataset.nodeType !== 'category' && node.dataset.nodeType !== 'subcategory') {
            return false;
        }

        return (node.dataset.hasParameters ?? '0') === '1';
    };

    const getNodeElements = () => Array.from(editorRoot.querySelectorAll('[data-node-type]'));

    const getParentNode = (node) => {
        const item = node.closest('li');
        const list = item ? item.parentElement : null;
        const parentDetails = list ? list.closest('details') : null;

        if (!parentDetails) {
            return null;
        }

        return parentDetails.querySelector(':scope > summary.lms-tree-node');
    };

    const clearNodeHighlights = () => {
        getNodeElements().forEach((node) => {
            node.classList.remove(
                'is-selected',
                'is-path-node',
                'is-path-discipline',
                'is-path-category',
                'is-path-sub',
                'is-path-sub-deep',
                'is-path-leaf'
            );
        });
    };

    const applyPathRoleClass = (node) => {
        const type = node.dataset.nodeType;

        if (type === 'discipline') {
            node.classList.add('is-path-discipline');
            return;
        }

        if (type === 'category') {
            node.classList.add('is-path-category');
            return;
        }

        if (type === 'subcategory') {
            const depth = Number(node.dataset.depth ?? '0');
            node.classList.add(depth >= 4 ? 'is-path-sub-deep' : 'is-path-sub');
            return;
        }

        node.classList.add('is-path-leaf');
    };

    const setSelectedPath = (activeNode) => {
        clearNodeHighlights();

        let cursor = activeNode;

        while (cursor) {
            cursor.classList.add('is-path-node');
            applyPathRoleClass(cursor);

            if (cursor === activeNode) {
                cursor.classList.add('is-selected');
            }

            cursor = getParentNode(cursor);
        }
    };

    const hideSections = () => {
        Object.values(sections).forEach((section) => {
            if (section) {
                section.hidden = true;
            }
        });
    };

    const setFormAction = (formSelector, route) => {
        const form = editorRoot.querySelector(formSelector);

        if (form) {
            form.setAttribute('action', route);
        }
    };

    const captureSectionState = (type) => {
        const section = sections[type];

        if (!section) {
            return {};
        }

        const state = {};

        section.querySelectorAll('[data-editor-input]').forEach((input) => {
            const key = input.dataset.editorInput;

            if (input.type === 'checkbox') {
                state[key] = input.checked;
                return;
            }

            state[key] = input.value;
        });

        return state;
    };

    const applySectionState = (type, state) => {
        const section = sections[type];

        if (!section) {
            return;
        }

        section.querySelectorAll('[data-editor-input]').forEach((input) => {
            const key = input.dataset.editorInput;

            if (!Object.prototype.hasOwnProperty.call(state, key)) {
                return;
            }

            if (input.type === 'checkbox') {
                input.checked = Boolean(state[key]);
                return;
            }

            input.value = state[key] ?? '';
        });

        refreshValueTypeFormsInSection(section, typeFormState);
    };

    const persistCurrentDraft = () => {
        if (!currentSelection) {
            return;
        }

        draftState.set(currentSelection.key, captureSectionState(currentSelection.type));
    };

    const selectionKey = (node) => `${node.dataset.nodeType}:${node.dataset.id}`;

    const populateDisciplineSection = (node) => {
        const section = sections.discipline;

        if (!section) {
            return;
        }

        section.querySelector('[data-editor-input="discipline-name"]').value = node.dataset.name ?? '';
        section.querySelector('[data-editor-input="discipline-active"]').checked = (node.dataset.active ?? '0') === '1';

        setFormAction('[data-editor-form="discipline-update"]', fillRoute(routeMap.disciplineUpdate, node.dataset.id));
        setFormAction('[data-editor-form="discipline-delete"]', fillRoute(routeMap.disciplineDelete, node.dataset.id));
    };

    const populateCategorySection = (node) => {
        const section = sections.category;

        if (!section) {
            return;
        }

        section.querySelector('[data-editor-input="category-discipline"]').value = node.dataset.parentId ?? '';
        section.querySelector('[data-editor-input="category-name"]').value = node.dataset.name ?? '';
        section.querySelector('[data-editor-input="category-active"]').checked = (node.dataset.active ?? '0') === '1';

        setFormAction('[data-editor-form="category-update"]', fillRoute(routeMap.categoryUpdate, node.dataset.id));
        setFormAction('[data-editor-form="category-delete"]', fillRoute(routeMap.categoryDelete, node.dataset.id));

        const hasSubcategories = (node.dataset.hasSubcategories ?? '0') === '1';
        const containerHint = section.querySelector('[data-category-container-hint]');
        const directForm = section.querySelector('[data-editor-form="category-parameter"]');
        const directDeleteForm = section.querySelector('[data-editor-form="category-parameter-delete"]');

        if (!containerHint || !directForm || !directDeleteForm) {
            return;
        }

        const parameterId = node.dataset.leafParameterId ?? '';
        const hasDirectParameter = parameterId !== '';
        const methodInput = section.querySelector('[data-editor-input="category-parameter-method"]');
        const categoryIdInput = section.querySelector('[data-editor-input="category-parameter-category-id"]');
        const nameInput = section.querySelector('[data-editor-input="category-parameter-name"]');
        const valueTypeInput = section.querySelector('[data-editor-input="category-parameter-value-type"]');
        const unitInput = section.querySelector('[data-editor-input="category-parameter-unit"]');
        const referenceInput = section.querySelector('[data-editor-input="category-parameter-reference"]');
        const optionsInput = section.querySelector('[data-editor-input="category-parameter-options"]');
        const defaultToggleInput = section.querySelector('[data-editor-input="category-parameter-default-toggle"]');
        const defaultOptionInput = section.querySelector('[data-editor-input="category-parameter-default-option"]');
        const visibleInput = section.querySelector('[data-editor-input="category-parameter-visible"]');
        const activeInput = section.querySelector('[data-editor-input="category-parameter-active"]');

        if (
            !methodInput
            || !categoryIdInput
            || !nameInput
            || !valueTypeInput
            || !unitInput
            || !referenceInput
            || !optionsInput
            || !defaultToggleInput
            || !defaultOptionInput
            || !visibleInput
            || !activeInput
        ) {
            return;
        }

        categoryIdInput.value = node.dataset.id ?? '';
        nameInput.value = node.dataset.name ?? '';
        valueTypeInput.value = node.dataset.leafParameterValueType ?? 'number';
        unitInput.value = node.dataset.leafParameterUnit ?? '';
        referenceInput.value = node.dataset.leafParameterReference ?? '';
        optionsInput.value = node.dataset.leafParameterOptionsCsv ?? '';
        defaultOptionInput.value = node.dataset.leafParameterDefaultValue ?? '';
        defaultToggleInput.checked = defaultOptionInput.value !== '';
        visibleInput.checked = hasDirectParameter
            ? (node.dataset.leafParameterVisible ?? '0') === '1'
            : true;
        activeInput.checked = hasDirectParameter
            ? (node.dataset.leafParameterActive ?? '0') === '1'
            : true;

        if (hasSubcategories) {
            containerHint.hidden = false;
            directForm.hidden = true;
            directDeleteForm.hidden = true;
            return;
        }

        containerHint.hidden = true;
        directForm.hidden = false;

        if (hasDirectParameter) {
            methodInput.disabled = false;
            methodInput.value = 'PUT';
            directForm.setAttribute('action', fillRoute(routeMap.parameterUpdate, parameterId));
            directDeleteForm.hidden = false;
            setFormAction('[data-editor-form="category-parameter-delete"]', fillRoute(routeMap.parameterDelete, parameterId));
        } else {
            methodInput.disabled = true;
            methodInput.value = 'PUT';
            directForm.setAttribute('action', routeMap.parameterStore);
            directDeleteForm.hidden = true;
            directDeleteForm.removeAttribute('action');
        }

        refreshValueTypeForm(directForm, typeFormState);
    };

    const populateSubcategorySection = (node) => {
        const section = sections.subcategory;

        if (!section) {
            return;
        }

        const categoryInput = section.querySelector('[data-editor-input="subcategory-category"]');
        const parentInput = section.querySelector('[data-editor-input="subcategory-parent"]');

        if (!categoryInput || !parentInput) {
            return;
        }

        categoryInput.value = node.dataset.categoryId ?? '';
        parentInput.value = node.dataset.parentSubcategoryId ?? '';

        section.querySelector('[data-editor-input="subcategory-name"]').value = node.dataset.name ?? '';
        section.querySelector('[data-editor-input="subcategory-active"]').checked = (node.dataset.active ?? '0') === '1';

        setFormAction('[data-editor-form="subcategory-update"]', fillRoute(routeMap.subcategoryUpdate, node.dataset.id));
        setFormAction('[data-editor-form="subcategory-delete"]', fillRoute(routeMap.subcategoryDelete, node.dataset.id));

        const hasChildren = (node.dataset.hasChildren ?? '0') === '1';
        const containerHint = section.querySelector('[data-subcategory-container-hint]');
        const parameterForm = section.querySelector('[data-editor-form="subcategory-parameter"]');
        const parameterDeleteForm = section.querySelector('[data-editor-form="subcategory-parameter-delete"]');

        if (!containerHint || !parameterForm || !parameterDeleteForm) {
            return;
        }

        const parameterId = node.dataset.leafParameterId ?? '';
        const hasLeafParameter = parameterId !== '';
        const methodInput = section.querySelector('[data-editor-input="subcategory-parameter-method"]');
        const categoryIdInput = section.querySelector('[data-editor-input="subcategory-parameter-category-id"]');
        const subcategoryIdInput = section.querySelector('[data-editor-input="subcategory-parameter-subcategory-id"]');
        const nameInput = section.querySelector('[data-editor-input="subcategory-parameter-name"]');
        const valueTypeInput = section.querySelector('[data-editor-input="subcategory-parameter-value-type"]');
        const unitInput = section.querySelector('[data-editor-input="subcategory-parameter-unit"]');
        const referenceInput = section.querySelector('[data-editor-input="subcategory-parameter-reference"]');
        const optionsInput = section.querySelector('[data-editor-input="subcategory-parameter-options"]');
        const defaultToggleInput = section.querySelector('[data-editor-input="subcategory-parameter-default-toggle"]');
        const defaultOptionInput = section.querySelector('[data-editor-input="subcategory-parameter-default-option"]');
        const visibleInput = section.querySelector('[data-editor-input="subcategory-parameter-visible"]');
        const activeInput = section.querySelector('[data-editor-input="subcategory-parameter-active"]');

        if (
            !methodInput
            || !categoryIdInput
            || !subcategoryIdInput
            || !nameInput
            || !valueTypeInput
            || !unitInput
            || !referenceInput
            || !optionsInput
            || !defaultToggleInput
            || !defaultOptionInput
            || !visibleInput
            || !activeInput
        ) {
            return;
        }

        categoryIdInput.value = node.dataset.categoryId ?? '';
        subcategoryIdInput.value = node.dataset.id ?? '';
        nameInput.value = node.dataset.name ?? '';
        valueTypeInput.value = node.dataset.leafParameterValueType ?? 'number';
        unitInput.value = node.dataset.leafParameterUnit ?? '';
        referenceInput.value = node.dataset.leafParameterReference ?? '';
        optionsInput.value = node.dataset.leafParameterOptionsCsv ?? '';
        defaultOptionInput.value = node.dataset.leafParameterDefaultValue ?? '';
        defaultToggleInput.checked = defaultOptionInput.value !== '';
        visibleInput.checked = hasLeafParameter
            ? (node.dataset.leafParameterVisible ?? '0') === '1'
            : true;
        activeInput.checked = hasLeafParameter
            ? (node.dataset.leafParameterActive ?? '0') === '1'
            : true;

        if (hasChildren) {
            containerHint.hidden = false;
            parameterForm.hidden = true;
            parameterDeleteForm.hidden = true;
            return;
        }

        containerHint.hidden = true;
        parameterForm.hidden = false;

        if (hasLeafParameter) {
            methodInput.disabled = false;
            methodInput.value = 'PUT';
            parameterForm.setAttribute('action', fillRoute(routeMap.parameterUpdate, parameterId));
            parameterDeleteForm.hidden = false;
            setFormAction('[data-editor-form="subcategory-parameter-delete"]', fillRoute(routeMap.parameterDelete, parameterId));
        } else {
            methodInput.disabled = true;
            methodInput.value = 'PUT';
            parameterForm.setAttribute('action', routeMap.parameterStore);
            parameterDeleteForm.hidden = true;
            parameterDeleteForm.removeAttribute('action');
        }

        refreshValueTypeForm(parameterForm, typeFormState);
    };

    const populateParameterSection = (node) => {
        const section = sections.parameter;

        if (!section) {
            return;
        }

        const categoryInput = section.querySelector('[data-editor-input="parameter-category"]');
        const subcategoryInput = section.querySelector('[data-editor-input="parameter-subcategory"]');
        const parameterForm = section.querySelector('[data-editor-form="parameter-update"]');

        if (!categoryInput || !subcategoryInput || !parameterForm) {
            return;
        }

        categoryInput.value = node.dataset.categoryId ?? '';
        syncSubcategoryOptions(categoryInput, subcategoryInput);
        subcategoryInput.value = node.dataset.subcategoryId ?? '';

        section.querySelector('[data-editor-input="parameter-name"]').value = node.dataset.name ?? '';
        section.querySelector('[data-editor-input="parameter-value-type"]').value = node.dataset.valueType ?? 'number';
        section.querySelector('[data-editor-input="parameter-unit"]').value = node.dataset.unit ?? '';
        section.querySelector('[data-editor-input="parameter-reference"]').value = node.dataset.reference ?? '';
        section.querySelector('[data-editor-input="parameter-options"]').value = node.dataset.optionsCsv ?? '';

        const defaultToggleInput = section.querySelector('[data-editor-input="parameter-default-toggle"]');
        const defaultOptionInput = section.querySelector('[data-editor-input="parameter-default-option"]');

        if (defaultToggleInput && defaultOptionInput) {
            defaultOptionInput.value = node.dataset.defaultValue ?? '';
            defaultToggleInput.checked = defaultOptionInput.value !== '';
        }

        section.querySelector('[data-editor-input="parameter-visible"]').checked = (node.dataset.visible ?? '0') === '1';
        section.querySelector('[data-editor-input="parameter-active"]').checked = (node.dataset.active ?? '0') === '1';

        setFormAction('[data-editor-form="parameter-update"]', fillRoute(routeMap.parameterUpdate, node.dataset.id));
        setFormAction('[data-editor-form="parameter-delete"]', fillRoute(routeMap.parameterDelete, node.dataset.id));
        refreshValueTypeForm(parameterForm, typeFormState);
    };

    const populateSectionFromNode = (type, node) => {
        if (type === 'discipline') {
            populateDisciplineSection(node);
            return;
        }

        if (type === 'category') {
            populateCategorySection(node);
            return;
        }

        if (type === 'subcategory') {
            populateSubcategorySection(node);
            return;
        }

        if (type === 'parameter') {
            populateParameterSection(node);
        }
    };

    const setAddChildAvailability = (node) => {
        if (!addChildButton) {
            return;
        }

        if (!node) {
            addChildButton.disabled = true;
            return;
        }

        addChildButton.disabled = node.dataset.nodeType === 'parameter';
    };

    const showNodeEditor = (node) => {
        const type = node.dataset.nodeType;
        const section = sections[type];

        if (!section) {
            return;
        }

        persistCurrentDraft();
        setSelectedPath(node);
        hideSections();

        if (emptyState) {
            emptyState.hidden = true;
        }

        section.hidden = false;
        populateSectionFromNode(type, node);

        const key = selectionKey(node);

        if (draftState.has(key)) {
            applySectionState(type, draftState.get(key));

            if (type === 'parameter') {
                const categoryInput = section.querySelector('[data-editor-input="parameter-category"]');
                const subcategoryInput = section.querySelector('[data-editor-input="parameter-subcategory"]');
                syncSubcategoryOptions(categoryInput, subcategoryInput);
            }
        }

        refreshValueTypeFormsInSection(section, typeFormState);

        currentSelection = {
            key,
            type,
            node,
        };

        setAddChildAvailability(node);
    };

    const createChevron = () => {
        const arrow = document.createElement('span');
        arrow.className = 'lms-tree-arrow';
        arrow.setAttribute('aria-hidden', 'true');
        return arrow;
    };

    const createLabel = (value) => {
        const label = document.createElement('span');
        label.className = 'lms-tree-label';
        label.textContent = value;
        return label;
    };

    const createDragHandle = () => {
        const handle = document.createElement('span');
        handle.className = 'lms-tree-drag-handle';
        handle.dataset.dragHandle = '';
        handle.title = labels.dragToReorder;
        handle.setAttribute('aria-label', labels.dragToReorder);
        handle.textContent = '⋮⋮';
        return handle;
    };

    const ensureChevron = (node) => {
        if (node.querySelector(':scope > .lms-tree-arrow')) {
            return;
        }

        const label = node.querySelector(':scope > .lms-tree-label');

        if (!label) {
            return;
        }

        node.insertBefore(createChevron(), label);
    };

    const removeChevron = (node) => {
        const chevron = node.querySelector(':scope > .lms-tree-arrow');

        if (chevron) {
            chevron.remove();
        }
    };

    const getBranchChildrenList = (node) => {
        const details = node.closest('details');

        if (!details) {
            return null;
        }

        return details.querySelector(':scope > .lms-tree-children');
    };

    const removeEmptyStates = (list) => {
        if (!list) {
            return;
        }

        list.querySelectorAll(':scope > li.lms-tree-empty').forEach((item) => item.remove());
    };

    const appendEmptyState = (list, text) => {
        if (!list) {
            return;
        }

        const li = document.createElement('li');
        li.className = 'lms-tree-empty';
        li.textContent = text;
        list.append(li);
    };

    const setNodeHasChildren = (node, hasChildren) => {
        node.dataset.hasChildren = hasChildren ? '1' : '0';

        if (hasChildren) {
            ensureChevron(node);
        } else {
            removeChevron(node);
        }
    };

    const clearConvertedParentState = (node) => {
        if (!node || (node.dataset.nodeType !== 'category' && node.dataset.nodeType !== 'subcategory')) {
            return;
        }

        node.dataset.hasParameters = '0';
        node.dataset.leafParameterId = '';
        node.dataset.leafParameterName = '';
        node.dataset.leafParameterActive = '1';
        node.dataset.leafParameterVisible = '1';
        node.dataset.leafParameterValueType = 'number';
        node.dataset.leafParameterUnit = '';
        node.dataset.leafParameterReference = '';
        node.dataset.leafParameterOptionsCsv = '';
        node.dataset.leafParameterDefaultValue = '';
    };

    const createCategoryNodeElement = (item) => {
        const li = document.createElement('li');
        const details = document.createElement('details');
        details.className = 'lms-tree-branch lms-tree-level-category';

        const summary = document.createElement('summary');
        summary.className = 'lms-tree-node lms-tree-summary';
        summary.dataset.nodeType = 'category';
        summary.dataset.dragEnabled = '1';
        summary.dataset.id = String(item.id);
        summary.dataset.categoryId = String(item.id);
        summary.dataset.parentId = String(item.discipline_id);
        summary.dataset.parentType = 'discipline';
        summary.dataset.name = item.name ?? '';
        summary.dataset.sortOrder = String(item.sort_order ?? 0);
        summary.dataset.active = item.is_active ? '1' : '0';
        summary.dataset.depth = '2';
        summary.dataset.hasChildren = '0';
        summary.dataset.hasSubcategories = '0';
        summary.dataset.hasParameters = '0';
        summary.dataset.leafParameterId = '';
        summary.dataset.leafParameterName = '';
        summary.dataset.leafParameterActive = '1';
        summary.dataset.leafParameterVisible = '1';
        summary.dataset.leafParameterValueType = 'number';
        summary.dataset.leafParameterUnit = '';
        summary.dataset.leafParameterReference = '';
        summary.dataset.leafParameterOptionsCsv = '';
        summary.dataset.leafParameterDefaultValue = '';
        summary.draggable = true;
        summary.append(createLabel(item.name ?? ''));
        summary.append(createDragHandle());

        const children = document.createElement('ul');
        children.className = 'lms-tree-children';
        appendEmptyState(children, labels.noParameter);

        details.append(summary, children);
        li.append(details);

        return { li, node: summary };
    };

    const createSubcategoryNodeElement = (item, visualDepth) => {
        const li = document.createElement('li');
        const details = document.createElement('details');
        details.className = `lms-tree-branch lms-tree-level-subcategory ${visualDepth > 1 ? 'lms-tree-level-subcategory-nested' : ''}`.trim();

        const summary = document.createElement('summary');
        summary.className = 'lms-tree-node lms-tree-summary';
        summary.dataset.nodeType = 'subcategory';
        summary.dataset.dragEnabled = '1';
        summary.dataset.id = String(item.id);
        summary.dataset.categoryId = String(item.category_id);
        summary.dataset.parentId = item.parent_subcategory_id ? String(item.parent_subcategory_id) : String(item.category_id);
        summary.dataset.parentType = item.parent_subcategory_id ? 'subcategory' : 'category';
        summary.dataset.parentSubcategoryId = item.parent_subcategory_id ? String(item.parent_subcategory_id) : '';
        summary.dataset.name = item.name ?? '';
        summary.dataset.sortOrder = String(item.sort_order ?? 0);
        summary.dataset.active = item.is_active ? '1' : '0';
        summary.dataset.depth = String(visualDepth);
        summary.dataset.subcategoryDepth = String(item.depth ?? 1);
        summary.dataset.hasChildren = '0';
        summary.dataset.hasParameters = '0';
        summary.dataset.leafParameterId = '';
        summary.dataset.leafParameterName = '';
        summary.dataset.leafParameterActive = '1';
        summary.dataset.leafParameterVisible = '1';
        summary.dataset.leafParameterValueType = 'number';
        summary.dataset.leafParameterUnit = '';
        summary.dataset.leafParameterReference = '';
        summary.dataset.leafParameterOptionsCsv = '';
        summary.dataset.leafParameterDefaultValue = '';
        summary.draggable = true;
        summary.append(createLabel(item.name ?? ''));
        summary.append(createDragHandle());

        const children = document.createElement('ul');
        children.className = 'lms-tree-children';
        appendEmptyState(children, labels.noParameter);

        details.append(summary, children);
        li.append(details);

        return { li, node: summary };
    };

    const postCatalogCreate = async (url, payload) => {
        const body = new URLSearchParams();
        Object.entries(payload).forEach(([key, value]) => {
            if (value === undefined || value === null) {
                return;
            }

            body.append(key, String(value));
        });

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': editorRoot.dataset.csrfToken ?? '',
            },
            body: body.toString(),
        });

        if (!response.ok) {
            let message = labels.createFailed;

            try {
                const data = await response.json();
                const firstError = data?.errors ? Object.values(data.errors)?.[0]?.[0] : null;
                message = firstError || data?.message || message;
            } catch {
                message = labels.createFailed;
            }

            throw new Error(message);
        }

        return response.json();
    };

    const buildAddChildRequest = (parentNode, name, forceConvert = false) => {
        if (!parentNode || !name) {
            return null;
        }

        if (parentNode.dataset.nodeType === 'discipline') {
            return {
                kind: 'category',
                url: routeMap.categoryStore,
                payload: {
                    discipline_id: parentNode.dataset.id,
                    name,
                },
            };
        }

        if (parentNode.dataset.nodeType === 'category') {
            return {
                kind: 'subcategory',
                url: routeMap.subcategoryStore,
                payload: {
                    category_id: parentNode.dataset.id,
                    name,
                    force_convert_parent: forceConvert ? 1 : 0,
                },
            };
        }

        if (parentNode.dataset.nodeType === 'subcategory') {
            return {
                kind: 'subcategory',
                url: routeMap.subcategoryStore,
                payload: {
                    category_id: parentNode.dataset.categoryId,
                    parent_subcategory_id: parentNode.dataset.id,
                    name,
                    force_convert_parent: forceConvert ? 1 : 0,
                },
            };
        }

        return null;
    };

    const handleAddChild = () => {
        if (!currentSelection || !addChildDialog || !addChildNameInput) {
            return;
        }

        if (currentSelection.node.dataset.nodeType === 'parameter') {
            return;
        }

        pendingAddChildAction = {
            parentNode: currentSelection.node,
        };
        addChildNameInput.value = '';
        if (addChildParentName) {
            addChildParentName.textContent = currentSelection.node.dataset.name ?? '-';
        }
        openDialog(addChildDialog);
        addChildNameInput.focus();
    };

    const isDraggableNode = (node) =>
        (node.dataset.nodeType === 'discipline' || node.dataset.nodeType === 'category' || node.dataset.nodeType === 'subcategory')
        && node.dataset.dragEnabled === '1';

    const isValidDropTarget = (draggedNode, targetNode) => {
        if (!draggedNode || !targetNode || draggedNode === targetNode) {
            return false;
        }

        if (draggedNode.dataset.nodeType !== targetNode.dataset.nodeType) {
            return false;
        }

        const draggedItem = draggedNode.closest('li');
        const targetItem = targetNode.closest('li');

        if (!draggedItem || !targetItem) {
            return false;
        }

        return draggedItem.parentElement === targetItem.parentElement;
    };

    const collectOrderedIds = (list, nodeType) => Array.from(list.children)
        .map((item) => item.querySelector(':scope > details > summary.lms-tree-node'))
        .filter((summary) => summary && summary.dataset.nodeType === nodeType)
        .map((summary) => Number(summary.dataset.id))
        .filter((id) => Number.isInteger(id));

    const persistReorder = async (node, list) => {
        if (!routeMap.reorder) {
            return;
        }

        const orderedIds = collectOrderedIds(list, node.dataset.nodeType);

        if (orderedIds.length === 0) {
            return;
        }

        const payload = node.dataset.nodeType === 'discipline'
            ? {
                type: 'discipline',
                ordered_ids: orderedIds,
            }
            : node.dataset.nodeType === 'category'
                ? {
                type: 'category',
                discipline_id: Number(node.dataset.parentId ?? 0),
                ordered_ids: orderedIds,
            }
                : {
                type: 'subcategory',
                category_id: Number(node.dataset.categoryId ?? 0),
                parent_subcategory_id: node.dataset.parentType === 'subcategory'
                    ? Number(node.dataset.parentId ?? 0)
                    : null,
                ordered_ids: orderedIds,
            };

        const response = await fetch(routeMap.reorder, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': editorRoot.dataset.csrfToken ?? '',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            let message = labels.reorderFailed;

            try {
                const data = await response.json();
                const firstError = data?.errors ? Object.values(data.errors)?.[0]?.[0] : null;
                message = firstError || data?.message || message;
            } catch {
                message = labels.reorderFailed;
            }

            throw new Error(message);
        }
    };

    const bindDragHandle = (handle) => {
        if (!handle || handle.dataset.bound === '1') {
            return;
        }

        handle.dataset.bound = '1';
        handle.addEventListener('pointerdown', (event) => {
            const node = handle.closest('[data-node-type]');
            event.stopPropagation();
            armedDragNode = node;
        });

        handle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
        });
    };

    const bindNodeInteractions = (node) => {
        if (!node || node.dataset.boundNode === '1') {
            return;
        }

        node.dataset.boundNode = '1';
        node.addEventListener('click', (event) => {
            if (node.tagName === 'SUMMARY' && (node.dataset.hasChildren ?? '0') !== '1') {
                event.preventDefault();
            }

            if (dragState) {
                return;
            }

            showNodeEditor(node);
        });

        if (!isDraggableNode(node)) {
            return;
        }

        node.addEventListener('dragstart', (event) => {
            if (armedDragNode !== node) {
                event.preventDefault();
                return;
            }

            const item = node.closest('li');
            const list = item?.parentElement;

            if (!item || !list) {
                event.preventDefault();
                return;
            }

            dragState = {
                node,
                item,
                list,
                moved: false,
            };

            item.classList.add('is-dragging');

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', selectionKey(node));
            }
        });

        node.addEventListener('dragend', async () => {
            if (!dragState || dragState.node !== node) {
                return;
            }

            const { list, moved, item } = dragState;
            item.classList.remove('is-dragging');
            dragState = null;
            armedDragNode = null;

            if (!moved) {
                return;
            }

            try {
                await persistReorder(node, list);
            } catch (error) {
                window.alert(error?.message || labels.reorderFailed);
            }
        });
    };

    const bindTreeInteractions = (scope = editorRoot) => {
        if (!scope || !scope.querySelectorAll) {
            return;
        }

        scope.querySelectorAll('[data-drag-handle]').forEach((handle) => bindDragHandle(handle));
        scope.querySelectorAll('[data-node-type]').forEach((node) => bindNodeInteractions(node));
    };

    const insertCreatedChildNode = (parentNode, request, item, forceConvert) => {
        const parentList = getBranchChildrenList(parentNode);

        if (!parentList) {
            throw new Error(labels.createFailed);
        }

        if (forceConvert) {
            clearConvertedParentState(parentNode);
        }

        removeEmptyStates(parentList);

        let created = null;
        if (request.kind === 'category') {
            created = createCategoryNodeElement(item);
        } else if (request.kind === 'subcategory') {
            const visualDepth = Number(parentNode.dataset.depth ?? '2') + 1;
            created = createSubcategoryNodeElement(item, visualDepth);
        }

        if (!created) {
            throw new Error(labels.createFailed);
        }

        parentList.append(created.li);
        bindTreeInteractions(created.li);

        setNodeHasChildren(parentNode, true);

        if (parentNode.dataset.nodeType === 'category') {
            parentNode.dataset.hasSubcategories = request.kind === 'subcategory' ? '1' : (parentNode.dataset.hasSubcategories ?? '0');
            if (forceConvert) {
                parentNode.dataset.hasParameters = '0';
            }
        }

        if (parentNode.dataset.nodeType === 'subcategory') {
            if (request.kind === 'subcategory' && forceConvert) {
                parentNode.dataset.hasParameters = '0';
            }
        }

        const branch = parentNode.closest('details');
        if (branch) {
            branch.open = true;
        }

        return created.node;
    };

    const submitAddChild = async (forceConvert = false) => {
        if (!pendingAddChildAction || !addChildNameInput) {
            return;
        }

        const parentNode = pendingAddChildAction.parentNode;
        const name = addChildNameInput.value.trim();

        if (!parentNode || name === '') {
            return;
        }

        const request = buildAddChildRequest(parentNode, name, forceConvert);

        if (!request || !request.url) {
            return;
        }

        const response = await postCatalogCreate(request.url, request.payload);
        const item = response?.item ?? null;

        if (!item) {
            throw new Error(labels.createFailed);
        }

        const newNode = insertCreatedChildNode(parentNode, request, item, forceConvert);
        closeDialog(addChildDialog);
        pendingAddChildAction = null;
        showNodeEditor(newNode);
    };

    document.addEventListener('pointerup', () => {
        if (!dragState) {
            armedDragNode = null;
        }
    });

    editorRoot.addEventListener('dragover', (event) => {
        if (!dragState) {
            return;
        }

        const targetNode = event.target.closest('[data-node-type]');

        if (!isValidDropTarget(dragState.node, targetNode)) {
            return;
        }

        const targetItem = targetNode.closest('li');

        if (!targetItem || targetItem === dragState.item) {
            return;
        }

        event.preventDefault();

        const rect = targetNode.getBoundingClientRect();
        const insertAfter = event.clientY > rect.top + (rect.height / 2);
        const referenceNode = insertAfter ? targetItem.nextElementSibling : targetItem;
        dragState.list.insertBefore(dragState.item, referenceNode);
        dragState.moved = true;
    });

    editorRoot.addEventListener('drop', (event) => {
        if (dragState) {
            event.preventDefault();
        }
    });

    Object.entries(sections).forEach(([type, section]) => {
        if (!section) {
            return;
        }

        section.querySelectorAll('[data-editor-input]').forEach((input) => {
            const capture = () => {
                if (!currentSelection || currentSelection.type !== type) {
                    return;
                }

                draftState.set(currentSelection.key, captureSectionState(type));
            };

            input.addEventListener('input', capture);
            input.addEventListener('change', capture);
        });

        const cancelButton = section.querySelector(`[data-editor-cancel="${type}"]`);

        if (cancelButton) {
            cancelButton.addEventListener('click', () => {
                if (!currentSelection || currentSelection.type !== type) {
                    return;
                }

                draftState.delete(currentSelection.key);
                populateSectionFromNode(type, currentSelection.node);
                refreshValueTypeFormsInSection(section, typeFormState);
            });
        }
    });

    editorRoot.querySelectorAll('form').forEach((form) => initializeValueTypeForm(form, typeFormState));
    bindTreeInteractions();

    const parameterCategoryInput = editorRoot.querySelector('[data-editor-input="parameter-category"]');
    const parameterSubcategoryInput = editorRoot.querySelector('[data-editor-input="parameter-subcategory"]');

    if (parameterCategoryInput && parameterSubcategoryInput) {
        parameterCategoryInput.addEventListener('change', () => {
            syncSubcategoryOptions(parameterCategoryInput, parameterSubcategoryInput);
        });
        syncSubcategoryOptions(parameterCategoryInput, parameterSubcategoryInput);
    }

    const subcategoryCategoryInput = editorRoot.querySelector('[data-editor-input="subcategory-category"]');
    const subcategoryParentInput = editorRoot.querySelector('[data-editor-input="subcategory-parent"]');

    if (subcategoryCategoryInput && subcategoryParentInput) {
        subcategoryCategoryInput.addEventListener('change', () => {
            if (!currentSelection || currentSelection.type !== 'subcategory') {
                return;
            }

            subcategoryParentInput.value = currentSelection.node.dataset.parentSubcategoryId ?? '';
        });
    }

    if (addChildForm && addChildNameInput) {
        addChildForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const parentNode = pendingAddChildAction?.parentNode ?? currentSelection?.node ?? null;
            const request = buildAddChildRequest(parentNode, addChildNameInput.value.trim(), false);

            if (!request) {
                return;
            }

            if (request.kind === 'subcategory' && requiresParentConversion(parentNode)) {
                openConvertConfirmDialog(() => {
                    submitAddChild(true).catch((error) => {
                        window.alert(error?.message || labels.createFailed);
                    });
                });
                return;
            }

            submitAddChild(false).catch((error) => {
                window.alert(error?.message || labels.createFailed);
            });
        });
    }

    if (convertConfirmButton) {
        convertConfirmButton.addEventListener('click', () => {
            const callback = onConfirmParentConversion;
            closeConvertConfirmDialog();

            if (typeof callback === 'function') {
                callback();
            }
        });
    }

    if (convertCancelButton) {
        convertCancelButton.addEventListener('click', () => {
            closeConvertConfirmDialog();
        });
    }

    if (convertConfirmDialog) {
        convertConfirmDialog.addEventListener('close', () => {
            onConfirmParentConversion = null;
        });
    }

    if (addChildDialog) {
        addChildDialog.addEventListener('close', () => {
            pendingAddChildAction = null;
            if (addChildNameInput) {
                addChildNameInput.value = '';
            }
            if (addChildParentName) {
                addChildParentName.textContent = '-';
            }
        });
    }

    if (addChildButton) {
        addChildButton.addEventListener('click', handleAddChild);
    }

    const rootBranches = Array.from(editorRoot.querySelectorAll('[data-root-branch]'));
    const openRootOrder = [];

    rootBranches.forEach((branch) => {
        if (branch.open) {
            openRootOrder.push(branch);
        }

        branch.addEventListener('toggle', () => {
            const existingIndex = openRootOrder.indexOf(branch);

            if (!branch.open) {
                if (existingIndex !== -1) {
                    openRootOrder.splice(existingIndex, 1);
                }
                return;
            }

            if (existingIndex !== -1) {
                openRootOrder.splice(existingIndex, 1);
            }

            openRootOrder.push(branch);

            if (openRootOrder.length <= 3) {
                return;
            }

            const oldestBranch = openRootOrder.shift();

            if (oldestBranch && oldestBranch !== branch) {
                oldestBranch.open = false;
            }
        });
    });

    const dialogs = document.querySelectorAll('.lms-modal');

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = document.getElementById(trigger.dataset.modalOpen);
            openDialog(dialog);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = trigger.closest('dialog');
            if (dialog?.id === 'modal-add-child') {
                pendingAddChildAction = null;
            }

            closeDialog(dialog);
        });
    });

    dialogs.forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            const isInDialog =
                rect.top <= event.clientY &&
                event.clientY <= rect.top + rect.height &&
                rect.left <= event.clientX &&
                event.clientX <= rect.left + rect.width;

            if (!isInDialog) {
                if (dialog.id === 'modal-add-child') {
                    pendingAddChildAction = null;
                }

                closeDialog(dialog);
            }
        });
    });

    editorRoot.querySelectorAll('form[data-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!deleteConfirmDialog || !deleteConfirmForm || !deleteMessage) {
                return;
            }

            event.preventDefault();
            deleteConfirmForm.setAttribute('action', form.getAttribute('action') || '');

            const hasChildren = currentSelection?.node?.dataset?.hasChildren === '1';
            const hasParameters = currentSelection?.node?.dataset?.hasParameters === '1';
            const hasDependents = hasChildren || hasParameters;
            deleteMessage.textContent = hasDependents ? labels.confirmDeleteWithChildren : labels.confirmDelete;

            if (deleteForceInput) {
                deleteForceInput.value = hasDependents ? '1' : '0';
            }

            openDialog(deleteConfirmDialog);
        });
    });

    setAddChildAvailability(null);
})();
