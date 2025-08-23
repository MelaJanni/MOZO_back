<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WaiterProfile;
use App\Models\AdminProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    /**
     * 👤 OBTENER PERFIL ACTIVO DEL USUARIO
     */
    public function getActiveProfile(Request $request)
    {
        try {
            $user = Auth::user();
            $profile = $user->getActiveProfile();

            if (!$profile) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No hay perfil configurado'
                ]);
            }

            $profileArray = $profile->toArray();
            if (isset($profile->birth_date) && $profile->birth_date) {
                $profileArray['birth_date'] = $profile->birth_date->format('d-m-Y');
            }
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $profile->id,
                    'type' => $user->isAdmin() ? 'admin' : 'waiter',
                    'user_id' => $user->id,
                    'avatar' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'birth_date' => $profile->birth_date ? $profile->birth_date->format('d-m-Y') : null,
                    'is_complete' => $profile->isComplete(),
                    'profile_data' => $profileArray
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📝 ACTUALIZAR PERFIL DE MOZO
     */
    public function updateWaiterProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'height' => 'nullable|numeric|between:1.0,2.5',
            'weight' => 'nullable|integer|between:30,200',
            'gender' => 'nullable|in:masculino,femenino,otro',
            'experience_years' => 'nullable|integer|between:0,50',
            'employment_type' => 'nullable|in:full-time,part-time,hourly,weekends-only',
            'current_schedule' => 'nullable',
            'current_location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'availability_hours' => 'nullable',
            'skills' => 'nullable',
            'is_available' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user->isWaiter()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los mozos pueden actualizar perfiles de mozo'
                ], 403);
            }

            // Excluir explícitamente business_id y otros campos no deseados
            $data = $request->except(['avatar', 'business_id']);

            // Convertir strings JSON a arrays si es necesario
            if (isset($data['availability_hours']) && is_string($data['availability_hours'])) {
                $data['availability_hours'] = json_decode($data['availability_hours'], true) ?: null;
            }
            if (isset($data['skills']) && is_string($data['skills'])) {
                $data['skills'] = json_decode($data['skills'], true) ?: null;
            }

            // Manejar la subida del avatar
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/waiters', 'public');
                $data['avatar'] = $avatarPath;
            }

            // Filtrar solo los campos permitidos en WaiterProfile para evitar errores de business_id
            $allowedFields = [
                'avatar', 'display_name', 'bio', 'phone', 'birth_date', 'height', 'weight', 
                'gender', 'experience_years', 'employment_type', 'current_schedule', 
                'current_location', 'latitude', 'longitude', 'availability_hours', 
                'skills', 'is_active', 'is_available', 'rating', 'total_reviews'
            ];
            
            $filteredData = array_intersect_key($data, array_flip($allowedFields));

            $profile = $user->waiterProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $filteredData
            );

            $profileFresh = $profile->fresh();
            $profileArray = $profileFresh->toArray();
            if (isset($profileFresh->birth_date) && $profileFresh->birth_date) {
                $profileArray['birth_date'] = $profileFresh->birth_date->format('d-m-Y');
            }
            return response()->json([
                'success' => true,
                'message' => 'Perfil de mozo actualizado exitosamente',
                'data' => [
                    'id' => $profile->id,
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'profile_data' => $profileArray
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil de mozo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🏢 ACTUALIZAR PERFIL DE ADMINISTRADOR
     */
    public function updateAdminProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:100',
            'corporate_email' => 'nullable|email|max:255',
            'corporate_phone' => 'nullable|string|max:20',
            'office_extension' => 'nullable|string|max:10',
            'bio' => 'nullable|string|max:1000',
            'notify_new_orders' => 'nullable|boolean',
            'notify_staff_requests' => 'nullable|boolean',
            'notify_reviews' => 'nullable|boolean',
            'notify_payments' => 'nullable|boolean',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden actualizar perfiles de admin'
                ], 403);
            }

            // Excluir explícitamente business_id y otros campos no deseados
            $data = $request->except(['avatar', 'business_id']);

            // Manejar la subida del avatar
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars/admins', 'public');
                $data['avatar'] = $avatarPath;
            }

            // Filtrar solo los campos permitidos en AdminProfile para evitar errores de business_id
            $allowedFields = [
                'avatar', 'display_name', 'position', 'corporate_email', 'corporate_phone', 
                'office_extension', 'bio', 'notify_new_orders', 'notify_staff_requests', 
                'notify_reviews', 'notify_payments'
            ];
            
            $filteredData = array_intersect_key($data, array_flip($allowedFields));

            $profile = $user->adminProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $filteredData
            );

            // Actualizar última actividad
            $profile->updateLastActive();

            $profileFresh = $profile->fresh();
            $profileArray = $profileFresh->toArray();
            if (isset($profileFresh->birth_date) && $profileFresh->birth_date) {
                $profileArray['birth_date'] = $profileFresh->birth_date->format('d-m-Y');
            }
            return response()->json([
                'success' => true,
                'message' => 'Perfil de administrador actualizado exitosamente',
                'data' => [
                    'id' => $profile->id,
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'profile_data' => $profileArray
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil de administrador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📋 OBTENER TODOS LOS PERFILES DE UN USUARIO
     */
    public function getAllProfiles(Request $request)
    {
        try {
            $user = Auth::user();
            $profiles = [];

            if ($user->isWaiter() && $user->waiterProfile) {
                $profile = $user->waiterProfile;
                $profiles[] = [
                    'id' => $profile->id,
                    'type' => 'waiter',
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'is_active' => $profile->is_active,
                    'is_available' => $profile->is_available
                ];
            }

            if ($user->isAdmin() && $user->adminProfile) {
                $profile = $user->adminProfile;
                $profiles[] = [
                    'id' => $profile->id,
                    'type' => 'admin',
                    'avatar_url' => $profile->avatar_url,
                    'display_name' => $profile->display_name,
                    'is_complete' => $profile->isComplete(),
                    'position' => $profile->position
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'profiles' => $profiles,
                    'total_profiles' => count($profiles)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los perfiles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ ELIMINAR AVATAR DEL PERFIL
     */
    public function deleteAvatar(Request $request)
    {
        try {
            $user = Auth::user();
            $profile = $user->getActiveProfile();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ], 404);
            }

            // Eliminar archivo del storage si existe
            if ($profile->avatar && Storage::disk('public')->exists($profile->avatar)) {
                Storage::disk('public')->delete($profile->avatar);
            }

            $profile->update(['avatar' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar eliminado exitosamente',
                'data' => [
                    'avatar_url' => $profile->avatar_url
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 OBTENER ESTADO DE COMPLETITUD DEL PERFIL NUEVO SISTEMA
     */
    public function getProfileCompleteness(Request $request)
    {
        $user = Auth::user();
        $profile = $user->getActiveProfile();

        // Verificar si el perfil existe
        if (!$profile) {
            return response()->json([
                'success' => true,
                'profile_exists' => false,
                'is_complete' => false,
                'completion_percentage' => 0,
                'user_role' => ($user->isAdmin() ? 'admin' : ($user->isWaiter() ? 'waiter' : null)),
                'message' => 'El perfil no ha sido creado aún',
                'missing_fields' => [
                    [
                        'field' => 'profile_creation',
                        'label' => 'Creación del perfil',
                        'description' => 'Necesitas crear tu perfil antes de completar la información',
                        'category' => 'setup',
                        'priority' => 'high',
                        'required' => true
                    ]
                ],
                'categories' => [
                    'setup' => [
                        'name' => 'Configuración inicial',
                        'completed' => 0,
                        'total' => 1,
                        'percentage' => 0
                    ]
                ],
                'next_steps' => [
                    'Crear tu perfil haciendo la primera actualización de datos'
                ]
            ]);
        }

        // Construir profile_data solo con los campos relevantes
        $profileData = [
            'avatar' => $profile->avatar_url,
            'display_name' => $profile->display_name,
            'bio' => $profile->bio,
            'phone' => $profile->phone,
            'birth_date' => $profile->birth_date ? $profile->birth_date->format('d-m-Y') : null,
            'height' => $profile->height,
            'weight' => $profile->weight,
            'gender' => $profile->gender,
            'experience_years' => $profile->experience_years,
            'current_location' => $profile->current_location,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'availability_hours' => $profile->availability_hours,
            'skills' => $profile->skills,
            'is_available_for_hire' => $profile->is_available_for_hire ?? null,
            'is_available' => $profile->is_available,
            'current_schedule' => $profile->current_schedule,
            'employment_type' => $profile->employment_type,
            'rating' => $profile->rating,
            'total_reviews' => $profile->total_reviews
        ];

        // Definir campos por rol
        if ($user->isWaiter()) {
            $fieldCategories = [
                'basic_info' => [
                    'name' => 'Información básica',
                    'description' => 'Datos personales fundamentales',
                    'fields' => [
                        'display_name' => [
                            'label' => 'Nombre a mostrar',
                            'description' => 'El nombre que aparecerá en tu perfil',
                            'required' => false,
                            'priority' => 'medium'
                        ],
                        'phone' => [
                            'label' => 'Teléfono',
                            'description' => 'Tu número de contacto',
                            'required' => true,
                            'priority' => 'high'
                        ],
                        'birth_date' => [
                            'label' => 'Fecha de nacimiento',
                            'description' => 'Tu fecha de nacimiento',
                            'required' => true,
                            'priority' => 'medium'
                        ],
                        'gender' => [
                            'label' => 'Género',
                            'description' => 'Tu identidad de género',
                            'required' => true,
                            'priority' => 'medium'
                        ]
                    ]
                ],
                'physical_info' => [
                    'name' => 'Información física',
                    'description' => 'Características físicas para uniformes',
                    'fields' => [
                        'height' => [
                            'label' => 'Altura',
                            'description' => 'Tu altura en metros (ej: 1.75)',
                            'required' => true,
                            'priority' => 'medium'
                        ],
                        'weight' => [
                            'label' => 'Peso',
                            'description' => 'Tu peso en kilogramos',
                            'required' => true,
                            'priority' => 'medium'
                        ]
                    ]
                ],
                'work_info' => [
                    'name' => 'Información laboral',
                    'description' => 'Tu experiencia y preferencias de trabajo',
                    'fields' => [
                        'experience_years' => [
                            'label' => 'Años de experiencia',
                            'description' => 'Cuántos años tienes trabajando como mozo',
                            'required' => true,
                            'priority' => 'high'
                        ],
                        'employment_type' => [
                            'label' => 'Tipo de empleo',
                            'description' => 'Tu modalidad de trabajo preferida',
                            'required' => true,
                            'priority' => 'high'
                        ],
                        'current_schedule' => [
                            'label' => 'Horario actual',
                            'description' => 'En qué turno prefieres trabajar',
                            'required' => true,
                            'priority' => 'high'
                        ]
                    ]
                ],
                'optional_info' => [
                    'name' => 'Información adicional',
                    'description' => 'Datos opcionales que mejoran tu perfil',
                    'fields' => [
                        'bio' => [
                            'label' => 'Biografía',
                            'description' => 'Una breve descripción sobre ti',
                            'required' => false,
                            'priority' => 'low'
                        ],
                        'current_location' => [
                            'label' => 'Ubicación actual',
                            'description' => 'Tu ubicación actual',
                            'required' => false,
                            'priority' => 'low'
                        ],
                        'avatar' => [
                            'label' => 'Foto de perfil',
                            'description' => 'Una foto tuya para el perfil',
                            'required' => false,
                            'priority' => 'low'
                        ]
                    ]
                ]
            ];
        } else {
            // Admin fields
            $fieldCategories = [
                'basic_info' => [
                    'name' => 'Información básica',
                    'description' => 'Datos personales fundamentales',
                    'fields' => [
                        'display_name' => [
                            'label' => 'Nombre a mostrar',
                            'description' => 'El nombre que aparecerá en tu perfil administrativo',
                            'required' => false,
                            'priority' => 'medium'
                        ],
                        'position' => [
                            'label' => 'Posición',
                            'description' => 'Tu cargo o posición en la empresa',
                            'required' => true,
                            'priority' => 'high'
                        ]
                    ]
                ],
                'contact_info' => [
                    'name' => 'Información de contacto',
                    'description' => 'Datos de contacto corporativo',
                    'fields' => [
                        'corporate_email' => [
                            'label' => 'Email corporativo',
                            'description' => 'Tu email de trabajo',
                            'required' => false,
                            'priority' => 'medium'
                        ],
                        'corporate_phone' => [
                            'label' => 'Teléfono corporativo',
                            'description' => 'Tu número de contacto de trabajo',
                            'required' => true,
                            'priority' => 'high'
                        ],
                        'office_extension' => [
                            'label' => 'Extensión de oficina',
                            'description' => 'Tu extensión telefónica',
                            'required' => false,
                            'priority' => 'low'
                        ]
                    ]
                ],
                'optional_info' => [
                    'name' => 'Información adicional',
                    'description' => 'Datos opcionales que mejoran tu perfil',
                    'fields' => [
                        'bio' => [
                            'label' => 'Biografía',
                            'description' => 'Una breve descripción profesional sobre ti',
                            'required' => false,
                            'priority' => 'low'
                        ],
                        'avatar' => [
                            'label' => 'Foto de perfil',
                            'description' => 'Una foto tuya para el perfil administrativo',
                            'required' => false,
                            'priority' => 'low'
                        ]
                    ]
                ]
            ];
        }

        $missingFields = [];
        $completedFields = [];
        $categories = [];
        $totalRequired = 0;
        $completedRequired = 0;

        // Analizar cada categoría
        foreach ($fieldCategories as $categoryKey => $category) {
            $categoryCompleted = 0;
            $categoryTotal = 0;
            $categoryRequired = 0;
            $categoryCompletedRequired = 0;

            foreach ($category['fields'] as $fieldKey => $fieldInfo) {
                $categoryTotal++;
                $fieldValue = $profile->$fieldKey;
                $isEmpty = empty($fieldValue);

                if ($fieldInfo['required']) {
                    $totalRequired++;
                    $categoryRequired++;
                    if (!$isEmpty) {
                        $completedRequired++;
                        $categoryCompletedRequired++;
                    }
                }

                if ($isEmpty) {
                    $missingFields[] = [
                        'field' => $fieldKey,
                        'label' => $fieldInfo['label'],
                        'description' => $fieldInfo['description'],
                        'category' => $categoryKey,
                        'category_name' => $category['name'],
                        'priority' => $fieldInfo['priority'],
                        'required' => $fieldInfo['required']
                    ];
                } else {
                    $categoryCompleted++;
                    $completedFields[] = [
                        'field' => $fieldKey,
                        'label' => $fieldInfo['label'],
                        'value' => $fieldValue,
                        'category' => $categoryKey
                    ];
                }
            }

            $categories[$categoryKey] = [
                'name' => $category['name'],
                'description' => $category['description'],
                'completed' => $categoryCompleted,
                'total' => $categoryTotal,
                'required' => $categoryRequired,
                'completed_required' => $categoryCompletedRequired,
                'percentage' => $categoryTotal > 0 ? round(($categoryCompleted / $categoryTotal) * 100, 2) : 0,
                'required_percentage' => $categoryRequired > 0 ? round(($categoryCompletedRequired / $categoryRequired) * 100, 2) : 100
            ];
        }

        // Calcular completitud general
        $overallPercentage = $totalRequired > 0 ? round(($completedRequired / $totalRequired) * 100, 2) : 0;
        $isComplete = count($missingFields) === 0 || array_filter($missingFields, fn($f) => $f['required']) === [];

        // Generar próximos pasos según el rol
        $nextSteps = [];
        if ($user->isWaiter()) {
            $highPriorityMissing = array_filter($missingFields, fn($f) => $f['priority'] === 'high' && $f['required']);
            if (count($highPriorityMissing) > 0) {
                $nextSteps[] = 'Completa tu información laboral (experiencia, tipo de empleo, horario)';
            }
            
            $basicInfoMissing = array_filter($missingFields, fn($f) => $f['category'] === 'basic_info' && $f['required']);
            if (count($basicInfoMissing) > 0) {
                $nextSteps[] = 'Agrega tu información básica (teléfono, fecha de nacimiento, género)';
            }

            $physicalInfoMissing = array_filter($missingFields, fn($f) => $f['category'] === 'physical_info');
            if (count($physicalInfoMissing) > 0) {
                $nextSteps[] = 'Completa tu información física para uniformes (altura y peso)';
            }
        } else {
            $requiredMissing = array_filter($missingFields, fn($f) => $f['required']);
            if (count($requiredMissing) > 0) {
                $nextSteps[] = 'Completa tu información administrativa requerida';
            }
        }

        if (empty($nextSteps)) {
            $nextSteps[] = 'Tu perfil está completo. Considera agregar información adicional para destacar más.';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $profile->id,
                'type' => $user->isAdmin() ? 'admin' : 'waiter',
                'user_id' => $user->id,
                'avatar' => $profile->avatar_url,
                'display_name' => $profile->display_name,
                'birth_date' => $profile->birth_date ? $profile->birth_date->format('d-m-Y') : null,
                'is_complete' => $isComplete,
                'profile_data' => $profileData
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }
}