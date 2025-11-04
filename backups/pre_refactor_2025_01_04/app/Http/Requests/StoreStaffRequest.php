<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
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
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'position' => 'required|string|max:100',
            
            // Campos obligatorios para mozos según requerimientos
            'date_of_birth' => 'required|date|before:today',
            'height' => 'required|numeric|between:1.0,2.5', // metros
            'weight' => 'required|numeric|between:30,200', // kg
            'gender' => 'required|in:masculino,femenino,otro',
            'experience_years' => 'required|integer|min:0|max:50',
            'employment_type' => 'required|in:tiempo_completo,medio_tiempo,por_horas',
            'current_schedule' => 'required|string|max:500',
            
            // Campos opcionales
            'user_id' => 'nullable|exists:users,id',
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'avatar_path' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'business_id.required' => 'El ID del negocio es obligatorio',
            'business_id.exists' => 'El negocio especificado no existe',
            'name.required' => 'El nombre es obligatorio',
            'name.max' => 'El nombre no debe superar los 255 caracteres',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'Debe ser un email válido',
            'phone.required' => 'El teléfono es obligatorio',
            'position.required' => 'La posición es obligatoria',
            
            // Mensajes para campos obligatorios específicos
            'date_of_birth.required' => 'La fecha de nacimiento es obligatoria',
            'date_of_birth.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'height.required' => 'La altura es obligatoria',
            'height.numeric' => 'La altura debe ser un número',
            'height.between' => 'La altura debe estar entre 1.0 y 2.5 metros',
            'weight.required' => 'El peso es obligatorio',
            'weight.numeric' => 'El peso debe ser un número',
            'weight.between' => 'El peso debe estar entre 30 y 200 kg',
            'gender.required' => 'El género es obligatorio',
            'gender.in' => 'El género debe ser: masculino, femenino u otro',
            'experience_years.required' => 'Los años de experiencia son obligatorios',
            'experience_years.integer' => 'Los años de experiencia deben ser un número entero',
            'experience_years.min' => 'Los años de experiencia no pueden ser negativos',
            'experience_years.max' => 'Los años de experiencia no pueden superar 50',
            'employment_type.required' => 'El tipo de empleo es obligatorio',
            'employment_type.in' => 'El tipo de empleo debe ser: tiempo_completo, medio_tiempo o por_horas',
            'current_schedule.required' => 'El horario actual es obligatorio',
            'current_schedule.max' => 'El horario no debe superar los 500 caracteres',
            
            // Validaciones opcionales
            'salary.numeric' => 'El salario debe ser un número',
            'salary.min' => 'El salario no puede ser negativo',
            'skills.array' => 'Las habilidades deben ser una lista',
            'skills.*.max' => 'Cada habilidad no debe superar los 100 caracteres',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.between' => 'La longitud debe estar entre -180 y 180'
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'business_id' => 'negocio',
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'position' => 'posición',
            'date_of_birth' => 'fecha de nacimiento',
            'height' => 'altura',
            'weight' => 'peso',
            'gender' => 'género',
            'experience_years' => 'años de experiencia',
            'employment_type' => 'tipo de empleo',
            'current_schedule' => 'horario actual',
            'salary' => 'salario',
            'hire_date' => 'fecha de contratación',
            'notes' => 'notas',
            'avatar_path' => 'foto de perfil',
            'skills' => 'habilidades',
            'latitude' => 'latitud',
            'longitude' => 'longitud'
        ];
    }
}