export const getCatalogLabels = (editorRoot) => ({
    confirmDelete: editorRoot.dataset.labelConfirmDelete || 'Confirm deletion?',
    confirmDeleteWithChildren: editorRoot.dataset.labelConfirmDeleteWithChildren || 'This item has children. Confirm deletion?',
    reorderFailed: editorRoot.dataset.labelReorderFailed || 'Unable to reorder.',
    createFailed: editorRoot.dataset.labelCreateFailed || 'Unable to create item.',
    noParameter: editorRoot.dataset.labelNoParameter || 'No parameter defined.',
    dragToReorder: editorRoot.dataset.labelDragToReorder || 'Drag to reorder',
});

export const getCatalogRoutes = (editorRoot) => ({
    disciplineUpdate: editorRoot.dataset.routeDisciplineUpdate,
    disciplineDelete: editorRoot.dataset.routeDisciplineDelete,
    categoryStore: editorRoot.dataset.routeCategoryStore,
    categoryUpdate: editorRoot.dataset.routeCategoryUpdate,
    categoryDelete: editorRoot.dataset.routeCategoryDelete,
    subcategoryStore: editorRoot.dataset.routeSubcategoryStore,
    subcategoryUpdate: editorRoot.dataset.routeSubcategoryUpdate,
    subcategoryDelete: editorRoot.dataset.routeSubcategoryDelete,
    parameterStore: editorRoot.dataset.routeParameterStore,
    parameterUpdate: editorRoot.dataset.routeParameterUpdate,
    parameterDelete: editorRoot.dataset.routeParameterDelete,
    reorder: editorRoot.dataset.routeReorder,
});
