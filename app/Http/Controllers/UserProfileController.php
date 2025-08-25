<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WaiterProfile;
use App\Models\AdminProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\WorkHistory;

class UserProfileController extends Controller
{
    private function formatDate($date): ?string
    {
        if (!$date) {
            return null;
        }
        try {
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('d-m-Y');
            }
            return \Carbon\Carbon::parse($date)->format('d-m-Y');
        } catch (\Throwable $e) {
            return null;
        }
    }
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
            // Asegurar URL completa en avatar dentro de profile_data
            $profileArray['avatar'] = $profile->avatar_url;
            // Unificar fuente del nombre: usar solo user.name (no enviar display_name en profile_data)
            unset($profileArray['display_name']);
            // Detectar tipo según el perfil activo (no solo por rol del usuario)
            $activeType = $profile instanceof WaiterProfile ? 'waiter' : 'admin';

            // Estructura limpia solicitada: data { user, type, profile_data }
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'google_id' => $user->google_id,
                        'google_avatar' => $user->google_avatar,
                    ],
                    'profile_data' => $profileArray,
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
            'employment_type' => 'nullable|string|max:50',
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

            // Normalizar employment_type a valores canónicos esperados por la BD
            if (array_key_exists('employment_type', $data)) {
                $raw = $data['employment_type'];
                if ($raw !== null && $raw !== '') {
                    $normalized = strtolower(trim((string)$raw));
                    // Unificar espacios y guiones a underscore
                    $normalized = str_replace([' ', '-'], '_', $normalized);
                    // Sinónimos comunes
                    if ($normalized === 'freelance') {
                        $normalized = 'freelancer';
                    }
                    // Valores permitidos en la BD
                    $allowedEnums = ['full_time', 'part_time', 'hourly', 'weekends_only', 'freelancer'];
                    if (in_array($normalized, $allowedEnums, true)) {
                        $data['employment_type'] = $normalized;
                    } else {
                        // Si no es válido, lo ponemos en null para evitar error de truncado
                        $data['employment_type'] = null;
                    }
                } else {
                    $data['employment_type'] = null;
                }
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
            // Asegurar que avatar dentro de profile_data sea URL y no path
            $profileArray['avatar'] = $profileFresh->avatar_url;
            // No devolver display_name para evitar duplicar el nombre
            unset($profileArray['display_name']);

            return response()->json([
                'success' => true,
                'message' => 'Perfil de mozo actualizado exitosamente',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'google_id' => $user->google_id,
                        'google_avatar' => $user->google_avatar,
                    ],
                    'profile_data' => $profileArray,
                ]
            ], 200, [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

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
            // Asegurar que avatar dentro de profile_data sea URL
            $profileArray['avatar'] = $profileFresh->avatar_url;
            // No devolver display_name para evitar duplicar el nombre
            unset($profileArray['display_name']);
            return response()->json([
                'success' => true,
                'message' => 'Perfil de administrador actualizado exitosamente',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'google_id' => $user->google_id,
                        'google_avatar' => $user->google_avatar,
                    ],
                    'profile_data' => $profileArray,
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

    // Definir campos por tipo de perfil activo
    $isWaiterProfile = $profile instanceof WaiterProfile;
    if ($isWaiterProfile) {
            $fieldCategories = [
                'basic_info' => [
                    'name' => 'Información básica',
                    'description' => 'Datos personales fundamentales',
                    'fields' => [
                        // display_name removido: el nombre canónico vive en user.name
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
                        // display_name removido: el nombre canónico vive en user.name
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
    if ($isWaiterProfile) {
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

    // Tipo basado en el perfil activo
    $activeType = $profile instanceof WaiterProfile ? 'waiter' : 'admin';
    return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'google_avatar' => $user->google_avatar,
                ],
        'type' => $activeType,
                'profile_data' => $profileData,
                'is_complete' => $isComplete,
            ]
        ], 200, [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }

    /**
     * ✏️ ACTUALIZAR DATOS DE LA CUENTA (usuario)
     * PUT /api/account
     * body: { name?, email? }
     */
    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Guardar cambios en el usuario
        if (!empty($data)) {
            $user->fill($data);
            $user->save();
        }

        // Construir payload consistente
        $profile = $user->getActiveProfile();
        $profileArray = $profile ? $profile->toArray() : null;
        if ($profile && isset($profile->birth_date) && $profile->birth_date) {
            $profileArray['birth_date'] = $profile->birth_date->format('d-m-Y');
            $profileArray['avatar'] = $profile->avatar_url;
        } elseif ($profile) {
            $profileArray['avatar'] = $profile->avatar_url;
        }

        return response()->json([
            'success' => true,
            'message' => 'Cuenta actualizada correctamente',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'google_avatar' => $user->google_avatar,
                ],
                'type' => $profile ? ($profile instanceof WaiterProfile ? 'waiter' : 'admin') : null,
                'profile_data' => $profileArray,
                'is_complete' => $profile ? $profile->isComplete() : false,
            ]
        ]);
    }

    /**
     * 🧾 HISTORIAL LABORAL DEL USUARIO (negocios y roles)
     * GET /api/profile/work-history
     */
    public function workHistory(Request $request)
    {
        try {
            $user = Auth::user();

            // 1) Ítems creados por el usuario (tabla editable)
            // Forzamos a Support\Collection para evitar métodos de Eloquent que esperan modelos
            $customItems = WorkHistory::where('user_id', $user->id)
                ->get()
                ->map(function ($row) {
                    return [
                        'business_name' => $row->business_name,
                        'start_date' => $this->formatDate($row->start_date),
                        'end_date' => $this->formatDate($row->end_date),
                        'position' => $row->position ?? 'mozo',
                        'description' => $row->description,
                    ];
                })
                ->values()
                ->toBase();

            // 2) Ítems derivados del vínculo como mozo en negocios (pivot business_waiters)
            $pivotItems = $user->businessesAsWaiter()
                ->withPivot(['employment_status', 'employment_type', 'hourly_rate', 'work_schedule', 'hired_at', 'last_shift_at'])
                ->get()
                ->map(function ($business) use ($user) {
                    $status = $business->pivot->employment_status ?? null;
                    $hiredAt = $business->pivot->hired_at ?? null;
                    $lastShiftAt = $business->pivot->last_shift_at ?? null;
                    $endDate = ($status && strtolower($status) !== 'active') ? $lastShiftAt : null;
                    $description = $business->pivot->work_schedule ?: (optional($user->waiterProfile)->bio ?? null);
                    return [
                        'business_name' => $business->name,
                        'start_date' => $this->formatDate($hiredAt),
                        'end_date' => $this->formatDate($endDate),
                        'position' => 'mozo',
                        'description' => $description,
                    ];
                })
                ->values()
                ->toBase();

            // 3) Unir y ordenar
            $items = collect()->merge($customItems)->merge($pivotItems)
                ->sortByDesc(function ($item) {
                    return $item['end_date'] ?? $item['start_date'] ?? null;
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $items->count(),
                    'items' => $items,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial laboral',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un item de historial laboral (solo campos requeridos)
     * POST /api/profile/work-history
     * body: { business_name, start_date, end_date?, cargo, description? }
     */
    public function createWorkHistory(Request $request)
    {
        $data = $request->only(['business_name', 'start_date', 'end_date', 'position', 'description', 'cargo']);
        $validator = Validator::make($data, [
            'business_name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'position' => 'required_without:cargo|string|max:100',
            'cargo' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $row = WorkHistory::create([
            'user_id' => $user->id,
            'business_name' => $data['business_name'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'position' => $data['position'] ?? ($data['cargo'] ?? 'mozo'),
            'description' => $data['description'] ?? null,
        ]);

    return response()->json([
            'success' => true,
            'data' => [
                'id' => $row->id,
                'business_name' => $row->business_name,
        'start_date' => $this->formatDate($row->start_date),
        'end_date' => $this->formatDate($row->end_date),
                'position' => $row->position,
                'description' => $row->description,
            ]
        ], 201);
    }

    /**
     * Actualizar un item de historial laboral
     * PUT /api/profile/work-history/{id}
     * body: { business_name, start_date, end_date?, cargo, description? }
     */
    public function updateWorkHistory(Request $request, int $id)
    {
        $user = Auth::user();
        $row = WorkHistory::where('user_id', $user->id)->findOrFail($id);

        $data = $request->only(['business_name', 'start_date', 'end_date', 'position', 'description', 'cargo']);
        $validator = Validator::make($data, [
            'business_name' => 'sometimes|required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'position' => 'sometimes|required_without:cargo|string|max:100',
            'cargo' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $row->update([
            'business_name' => $data['business_name'] ?? $row->business_name,
            'start_date' => array_key_exists('start_date', $data) ? $data['start_date'] : $row->start_date,
            'end_date' => array_key_exists('end_date', $data) ? $data['end_date'] : $row->end_date,
            'position' => $data['position'] ?? ($data['cargo'] ?? $row->position),
            'description' => array_key_exists('description', $data) ? $data['description'] : $row->description,
        ]);

    return response()->json([
            'success' => true,
            'data' => [
                'id' => $row->id,
                'business_name' => $row->business_name,
        'start_date' => $this->formatDate($row->start_date),
        'end_date' => $this->formatDate($row->end_date),
                'position' => $row->position,
                'description' => $row->description,
            ]
        ]);
    }
}