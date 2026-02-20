        <section class="lms-stack lms-editor-panel">
            <article class="lms-card lms-stack">
                <h3>{{ __('messages.catalog_editor_title') }}</h3>
                <p class="lms-muted" data-editor-empty>{{ __('messages.catalog_select_hint') }}</p>

                @include('catalog.partials.editor.discipline-section')
                @include('catalog.partials.editor.category-section')
                @include('catalog.partials.editor.subcategory-section')
                @include('catalog.partials.editor.parameter-section')
            </article>
        </section>
