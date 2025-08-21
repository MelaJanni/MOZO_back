<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            
            // Campos obligatorios para mozos
            'date_of_birth' => 'required|date|before:today',
            'height' => 'required|numeric|between:1.0,2.5',
            'weight' => 'required|numeric|between:30,200',
            'gender' => 'required|in:masculino,femenino,otro',
            'experience_years' => 'required|integer|min:0|max:50',
            'employment_type' => 'required|in:tiempo_completo,medio_tiempo,por_horas',
            'current_schedule' => 'required|string|max:500',
            
            // Campos opcionales
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            
            // Foto de perfil si se sube
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'phone.required' => 'El teléfono es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'Debe ser un email válido',
            
            // Campos obligatorios
            'date_of_birth.required' => 'La fecha de nacimiento es obligatoria para mozos',
            'date_of_birth.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'height.required' => 'La altura es obligatoria para mozos',
            'height.between' => 'La altura debe estar entre 1.0 y 2.5 metros',
            'weight.required' => 'El peso es obligatorio para mozos',
            'weight.between' => 'El peso debe estar entre 30 y 200 kg',
            'gender.required' => 'El género es obligatorio para mozos',
            'gender.in' => 'El género debe ser: masculino, femenino u otro',
            'experience_years.required' => 'Los años de experiencia son obligatorios para mozos',
            'experience_years.min' => 'Los años de experiencia no pueden ser negativos',
            'experience_years.max' => 'Los años de experiencia no pueden superar 50',
            'employment_type.required' => 'El tipo de empleo es obligatorio para mozos',
            'employment_type.in' => 'El tipo de empleo debe ser: tiempo_completo, medio_tiempo o por_horas',
            'current_schedule.required' => 'El horario actual es obligatorio para mozos',
            
            // Archivo de imagen
            'avatar.image' => 'El archivo debe ser una imagen',
            'avatar.mimes' => 'La imagen debe ser de formato: jpeg, png, jpg o gif',
            'avatar.max' => 'La imagen no debe superar los 2MB',
            
            // Validaciones opcionales
            'skills.array' => 'Las habilidades deben ser una lista',
            'skills.*.max' => 'Cada habilidad no debe superar los 100 caracteres',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.between' => 'La longitud debe estar entre -180 y 180'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir skills de string a array si es necesario
        if ($this->has('skills') && is_string($this->skills)) {
            $this->merge([
                'skills' => json_decode($this->skills, true) ?: []
            ]);
        }
    }
}