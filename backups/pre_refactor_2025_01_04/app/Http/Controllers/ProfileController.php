<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Models\Table;
use App\Models\WorkExperience;
use App\Models\DeviceToken;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        
        if ($userId) {
            $profiles = Profile::where('user_id', $userId)->get();
        } else {
            $profiles = Profile::all();
        }
        
        return response()->json($profiles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'table_ids' => 'array',
            'table_ids.*' => 'exists:tables,id',
        ]);

        $profile = Profile::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
        ]);

        if ($request->has('table_ids')) {
            $profile->tables()->attach($request->table_ids);
        }

        return response()->json($profile->load('tables'), 201);
    }

    public function show(Profile $profile)
    {
        return response()->json($profile->load('tables'));
    }

    public function update(Request $request, Profile $profile)
    {
        $request->validate([
            'user_id' => 'exists:users,id',
            'name' => 'string|max:255',
            'table_ids' => 'array',
            'table_ids.*' => 'exists:tables,id',
        ]);

        $profile->update($request->only(['user_id', 'name']));

        if ($request->has('table_ids')) {
            $profile->tables()->sync($request->table_ids);
        }

        return response()->json($profile->load('tables'));
    }

    public function destroy(Profile $profile)
    {
        $profile->tables()->detach();
        $profile->delete();
        return response()->json(null, 204);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile()->firstOrCreate([]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8|confirmed',
            
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string',
            'profile_picture' => 'sometimes|string',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'height' => 'sometimes|numeric',
            'weight' => 'sometimes|numeric',
            'skills' => 'sometimes|array',
            'latitude' => ['sometimes', 'numeric', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'longitude' => ['sometimes', 'numeric', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->fill($request->only(['name', 'email']));
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        $profile->fill($request->except(['name', 'email', 'password']));
        $profile->save();

        return response()->json([
            'message' => 'Perfil actualizado exitosamente.',
            'user' => $user->load('profile'),
        ]);
    }

    /**
     * 📝 ACTUALIZAR PERFIL CON VALIDACIONES OBLIGATORIAS PARA MOZOS
     */
    public function updateWaiterProfile(UpdateProfileRequest $request)
    {
        try {
            $user = Auth::user();
            
            // Verificar que el usuario sea mozo
            if ($user->role !== 'waiter') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los mozos pueden usar este endpoint'
                ], 403);
            }

            $profile = $user->profile()->firstOrCreate([]);

            // Actualizar datos del usuario
            $user->fill($request->only(['name', 'email']));
            
            // Manejar subida de avatar si existe
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }
            
            $user->save();

            // Actualizar perfil con todos los campos
            $profileData = $request->except(['name', 'email', 'avatar']);
            
            // Convertir skills a JSON si viene como array
            if (isset($profileData['skills']) && is_array($profileData['skills'])) {
                $profileData['skills'] = $profileData['skills'];
            }

            $profile->fill($profileData);
            $profile->save();

            \Log::info('Waiter profile updated with required fields', [
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'has_required_fields' => $this->hasRequiredFields($profile)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Perfil de mozo actualizado exitosamente',
                'user' => $user->load('profile'),
                'profile_complete' => $this->hasRequiredFields($profile)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating waiter profile', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil'
            ], 500);
        }
    }

    /**
     * 🔍 VERIFICAR SI EL PERFIL TIENE TODOS LOS CAMPOS OBLIGATORIOS
     */
    private function hasRequiredFields($profile): bool
    {
        $requiredFields = [
            'name', 'phone', 'date_of_birth', 'height', 'weight', 
            'gender', 'experience_years', 'employment_type', 'current_schedule'
        ];

        foreach ($requiredFields as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 📊 OBTENER ESTADO DE COMPLETITUD DEL PERFIL
     */
    public function getProfileCompleteness(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;

        // Verificar si el perfil existe
        if (!$profile) {
            return response()->json([
                'success' => true,
                'profile_exists' => false,
                'is_complete' => false,
                'completion_percentage' => 0,
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

        // Definir campos por categorías con más detalle
        $fieldCategories = [
            'basic_info' => [
                'name' => 'Información básica',
                'description' => 'Datos personales fundamentales',
                'fields' => [
                    'phone' => [
                        'label' => 'Teléfono',
                        'description' => 'Tu número de contacto',
                        'required' => true,
                        'priority' => 'high'
                    ],
                    'date_of_birth' => [
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
                    'address' => [
                        'label' => 'Dirección',
                        'description' => 'Tu dirección de residencia',
                        'required' => false,
                        'priority' => 'low'
                    ],
                    'profile_picture' => [
                        'label' => 'Foto de perfil',
                        'description' => 'Una foto tuya para el perfil',
                        'required' => false,
                        'priority' => 'low'
                    ]
                ]
            ]
        ];

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

        // Generar próximos pasos
        $nextSteps = [];
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

        if (empty($nextSteps)) {
            $nextSteps[] = 'Tu perfil está completo. Considera agregar información adicional para destacar más.';
        }

        return response()->json([
            'success' => true,
            'profile_exists' => true,
            'is_complete' => $isComplete,
            'completion_percentage' => $overallPercentage,
            'message' => $isComplete ? 'Tu perfil está completo' : 'Tu perfil necesita información adicional',
            'statistics' => [
                'total_fields' => count($completedFields) + count($missingFields),
                'completed_fields' => count($completedFields),
                'missing_fields' => count($missingFields),
                'required_fields' => $totalRequired,
                'completed_required' => $completedRequired,
                'missing_required' => $totalRequired - $completedRequired
            ],
            'missing_fields' => $missingFields,
            'completed_fields' => $completedFields,
            'categories' => $categories,
            'next_steps' => $nextSteps,
            'profile_tips' => [
                'Una foto de perfil mejora tu presentación ante los empleadores',
                'Una biografía personal te ayuda a destacar entre otros candidatos',
                'Mantener tu información actualizada aumenta tus oportunidades de trabajo'
            ]
        ]);
    }
    
    public function sendWhatsAppMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        
        if (!preg_match('/^\d{10,15}$/', $phone)) {
            return response()->json([
                'message' => 'El número de teléfono no tiene un formato válido para WhatsApp'
            ], 422);
        }
        
        
        \Log::info("Usuario {$user->id} ({$user->name}) envió un mensaje WhatsApp a {$phone}");
        
        return response()->json([
            'message' => 'Mensaje WhatsApp enviado exitosamente',
            'to' => $phone,
            'status' => 'sent'
        ]);
    }

    public function getWorkHistory()
    {
        $workHistory = Auth::user()->workExperiences;
        return response()->json($workHistory);
    }

    public function addWorkHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workExperience = Auth::user()->workExperiences()->create($request->all());

        return response()->json($workExperience, 201);
    }

    public function updateWorkHistory(Request $request, WorkExperience $workExperience)
    {
        if ($workExperience->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'company' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $workExperience->update($request->all());

        return response()->json($workExperience);
    }

    public function deleteWorkHistory(WorkExperience $workExperience)
    {
        if ($workExperience->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $workExperience->delete();

        return response()->json(null, 204);
    }


    public function deleteDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'sometimes|string',
            'id' => 'sometimes|integer|exists:device_tokens,id'
        ]);

        if ($request->has('id')) {
            $deleted = $request->user()->deviceTokens()->where('id', $request->id)->delete();
            return response()->json(['message' => $deleted ? 'Token eliminado' : 'Token no encontrado']);
        }

        if ($request->has('token')) {
            $deleted = $request->user()->deviceTokens()->where('token', $request->token)->delete();
            return response()->json(['message' => $deleted ? 'Token eliminado' : 'Token no encontrado']);
        }

        return response()->json(['message' => 'Se requiere token o id'], 422);
    }
}
