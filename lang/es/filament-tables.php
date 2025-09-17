<?php

return [
    'columns' => [
        'text' => [
            'actions' => [
                'collapse_list' => 'Mostrar :count menos',
                'expand_list' => 'Mostrar :count más',
            ],
        ],
    ],

    'fields' => [
        'bulk_select_page' => [
            'label' => 'Seleccionar/deseleccionar todos los elementos para acciones masivas.',
        ],
        'bulk_select_record' => [
            'label' => 'Seleccionar/deseleccionar elemento :key para acciones masivas.',
        ],
        'search' => [
            'label' => 'Buscar',
            'placeholder' => 'Buscar',
            'indicator' => 'Buscar',
        ],
    ],

    'pagination' => [
        'label' => 'Navegación de paginación',
        'overview' => 'Mostrando :first a :last de :total resultados',
        'fields' => [
            'records_per_page' => [
                'label' => 'Por página',
                'options' => [
                    '10' => '10',
                    '25' => '25',
                    '50' => '50',
                    '100' => '100',
                    'all' => 'Todos',
                ],
            ],
        ],
        'buttons' => [
            'go_to_page' => [
                'label' => 'Ir a página :page',
            ],
            'next' => [
                'label' => 'Siguiente',
            ],
            'previous' => [
                'label' => 'Anterior',
            ],
        ],
    ],

    'buttons' => [
        'disable_reordering' => [
            'label' => 'Terminar reordenamiento de registros',
        ],
        'enable_reordering' => [
            'label' => 'Reordenar registros',
        ],
        'filter' => [
            'label' => 'Filtrar',
        ],
        'group' => [
            'label' => 'Agrupar',
        ],
        'open_bulk_actions' => [
            'label' => 'Acciones masivas',
        ],
        'toggle_columns' => [
            'label' => 'Alternar columnas',
        ],
    ],

    'empty' => [
        'heading' => 'No se encontraron registros',
        'description' => 'Crea un :model para empezar.',
    ],

    'filters' => [
        'actions' => [
            'remove' => [
                'label' => 'Quitar filtro',
            ],
            'remove_all' => [
                'label' => 'Quitar todos los filtros',
                'tooltip' => 'Quitar todos los filtros',
            ],
            'reset' => [
                'label' => 'Restablecer',
            ],
        ],
        'heading' => 'Filtros',
        'indicator' => 'Filtros activos',
        'multi_select' => [
            'placeholder' => 'Todos',
        ],
        'select' => [
            'placeholder' => 'Todos',
        ],
        'trashed' => [
            'label' => 'Registros eliminados',
            'only_trashed' => 'Solo registros eliminados',
            'with_trashed' => 'Con registros eliminados',
            'without_trashed' => 'Sin registros eliminados',
        ],
    ],

    'grouping' => [
        'fields' => [
            'group' => [
                'label' => 'Agrupar por',
                'placeholder' => 'Agrupar por',
            ],
            'direction' => [
                'label' => 'Dirección de agrupación',
                'options' => [
                    'asc' => 'Ascendente',
                    'desc' => 'Descendente',
                ],
            ],
        ],
    ],

    'reorder_indicator' => 'Arrastra y suelta los registros en orden.',

    'selection_indicator' => [
        'selected_count' => '1 registro seleccionado|:count registros seleccionados',
        'actions' => [
            'select_all' => [
                'label' => 'Seleccionar todos los :count',
            ],
            'deselect_all' => [
                'label' => 'Deseleccionar todos',
            ],
        ],
    ],

    'sorting' => [
        'fields' => [
            'column' => [
                'label' => 'Ordenar por',
            ],
            'direction' => [
                'label' => 'Dirección de ordenamiento',
                'options' => [
                    'asc' => 'Ascendente',
                    'desc' => 'Descendente',
                ],
            ],
        ],
    ],
];