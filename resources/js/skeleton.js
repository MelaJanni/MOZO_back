// Skeleton Loading Management for Filament

document.addEventListener('DOMContentLoaded', function() {

    // Initialize loading states
    initializeLoadingStates();

    // Handle Livewire loading states
    handleLivewireLoading();

    // Handle form submissions
    handleFormSubmissions();

    // Handle table actions
    handleTableActions();
});

function initializeLoadingStates() {
    console.log('🎯 Skeleton loading initialized');

    // Add loading class to elements that are still loading
    const loadingElements = document.querySelectorAll('[wire\\:loading]');
    loadingElements.forEach(element => {
        if (!element.querySelector('.loading-spinner')) {
            addLoadingSpinner(element);
        }
    });
}

function handleLivewireLoading() {
    // Listen for Livewire events
    document.addEventListener('livewire:load', function() {
        console.log('🔄 Livewire loaded');
    });

    document.addEventListener('livewire:start', function() {
        console.log('⏳ Livewire request started');
        showTableSkeleton();
        showGlobalLoading();
    });

    document.addEventListener('livewire:finish', function() {
        console.log('✅ Livewire request finished');
        hideTableSkeleton();
        hideGlobalLoading();
    });

    // Handle specific Livewire loading targets
    document.addEventListener('livewire:start', function(event) {
        const target = event.detail?.component?.el;
        if (target) {
            target.classList.add('loading');
        }
    });

    document.addEventListener('livewire:finish', function(event) {
        const target = event.detail?.component?.el;
        if (target) {
            target.classList.remove('loading');
        }
    });
}

function handleFormSubmissions() {
    // Handle all form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM') {
            showFormLoading(form);
        }
    });

    // Handle Filament form submissions specifically
    document.addEventListener('click', function(e) {
        const button = e.target.closest('button[type="submit"]');
        if (button) {
            showButtonLoading(button);

            // Remove loading after a timeout as fallback
            setTimeout(() => {
                hideButtonLoading(button);
            }, 10000);
        }
    });
}

function handleTableActions() {
    // Handle table row actions
    document.addEventListener('click', function(e) {
        const action = e.target.closest('[data-action]');
        if (action) {
            const table = action.closest('.fi-ta-table');
            if (table) {
                showTableLoading(table);

                // Hide loading after reasonable timeout
                setTimeout(() => {
                    hideTableLoading(table);
                }, 5000);
            }
        }
    });

    // Handle bulk actions
    document.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox' && e.target.closest('.fi-ta-record')) {
            updateBulkActionState();
        }
    });
}

function showTableSkeleton() {
    const tables = document.querySelectorAll('.fi-ta-table');
    tables.forEach(table => {
        if (!table.querySelector('.skeleton-overlay')) {
            const overlay = createTableSkeletonOverlay();
            table.style.position = 'relative';
            table.appendChild(overlay);
        }
    });
}

function hideTableSkeleton() {
    const overlays = document.querySelectorAll('.skeleton-overlay');
    overlays.forEach(overlay => {
        overlay.remove();
    });
}

function createTableSkeletonOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'skeleton-overlay';
    overlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        z-index: 10;
        display: flex;
        flex-direction: column;
        padding: 16px;
    `;

    // Create skeleton rows
    for (let i = 0; i < 5; i++) {
        const row = document.createElement('div');
        row.style.cssText = `
            display: flex;
            gap: 16px;
            margin-bottom: 12px;
            align-items: center;
        `;

        // Avatar skeleton
        const avatar = document.createElement('div');
        avatar.className = 'skeleton skeleton-avatar';
        row.appendChild(avatar);

        // Text skeletons
        for (let j = 0; j < 4; j++) {
            const text = document.createElement('div');
            text.className = 'skeleton skeleton-text';
            text.style.width = `${Math.random() * 40 + 60}%`;
            text.style.flex = '1';
            row.appendChild(text);
        }

        overlay.appendChild(row);
    }

    return overlay;
}

function showFormLoading(form) {
    form.classList.add('loading');

    // Disable all inputs
    const inputs = form.querySelectorAll('input, select, textarea, button');
    inputs.forEach(input => {
        input.disabled = true;
        input.dataset.wasDisabled = input.disabled;
    });

    // Add loading spinner to submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        showButtonLoading(submitBtn);
    }
}

function hideFormLoading(form) {
    form.classList.remove('loading');

    // Re-enable inputs
    const inputs = form.querySelectorAll('input, select, textarea, button');
    inputs.forEach(input => {
        if (input.dataset.wasDisabled !== 'true') {
            input.disabled = false;
        }
        delete input.dataset.wasDisabled;
    });

    // Remove loading spinner from submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        hideButtonLoading(submitBtn);
    }
}

function showButtonLoading(button) {
    button.classList.add('btn-loading');
    button.disabled = true;

    // Store original text
    if (!button.dataset.originalText) {
        button.dataset.originalText = button.textContent;
    }

    // Add loading text
    const spinner = document.createElement('span');
    spinner.className = 'loading-spinner';
    spinner.style.marginRight = '8px';

    button.innerHTML = '';
    button.appendChild(spinner);
    button.appendChild(document.createTextNode('Cargando...'));
}

function hideButtonLoading(button) {
    button.classList.remove('btn-loading');
    button.disabled = false;

    // Restore original text
    if (button.dataset.originalText) {
        button.textContent = button.dataset.originalText;
        delete button.dataset.originalText;
    }
}

function showTableLoading(table) {
    table.classList.add('loading');

    // Add shimmer effect
    if (!table.querySelector('.table-loading-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'table-loading-overlay';
        overlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 1.5s infinite;
            pointer-events: none;
            z-index: 1;
        `;
        table.style.position = 'relative';
        table.appendChild(overlay);
    }
}

function hideTableLoading(table) {
    table.classList.remove('loading');

    const overlay = table.querySelector('.table-loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showGlobalLoading() {
    let loader = document.getElementById('global-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'global-loader';
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #3b82f6);
            background-size: 200% 100%;
            animation: loading-bar 2s linear infinite;
            z-index: 9999;
        `;
        document.body.appendChild(loader);
    }
    loader.style.display = 'block';
}

function hideGlobalLoading() {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.style.display = 'none';
    }
}

function updateBulkActionState() {
    const checkboxes = document.querySelectorAll('.fi-ta-record input[type="checkbox"]:checked');
    const bulkActions = document.querySelector('.fi-ta-bulk-actions');

    if (bulkActions) {
        if (checkboxes.length > 0) {
            bulkActions.style.opacity = '1';
            bulkActions.style.pointerEvents = 'auto';
        } else {
            bulkActions.style.opacity = '0.5';
            bulkActions.style.pointerEvents = 'none';
        }
    }
}

function addLoadingSpinner(element) {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.style.margin = '0 8px';
    element.appendChild(spinner);
}

// CSS animation for loading bar
const style = document.createElement('style');
style.textContent = `
    @keyframes loading-bar {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
`;
document.head.appendChild(style);

// Export functions for external use
window.SkeletonLoader = {
    showTableSkeleton,
    hideTableSkeleton,
    showFormLoading,
    hideFormLoading,
    showButtonLoading,
    hideButtonLoading,
    showGlobalLoading,
    hideGlobalLoading
};