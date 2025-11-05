<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Trait JsonResponses
 * 
 * Proporciona métodos estandarizados para respuestas JSON en controladores.
 * Reduce duplicación de código y asegura consistencia en las respuestas de la API.
 * 
 * @package App\Http\Controllers\Concerns
 */
trait JsonResponses
{
    /**
     * Respuesta exitosa con datos
     * 
     * @param mixed $data Datos a retornar
     * @param string|null $message Mensaje opcional
     * @param int $status Código HTTP (default: 200)
     * @return JsonResponse
     */
    protected function success($data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            // Si $data es un array asociativo con claves específicas, merge directo
            if (is_array($data)) {
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }
        
        return response()->json($response, $status);
    }

    /**
     * Respuesta de error
     * 
     * @param string $message Mensaje de error
     * @param int $status Código HTTP (default: 400)
     * @param array $errors Errores adicionales (opcional)
     * @return JsonResponse
     */
    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = ['message' => $message];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Respuesta de errores de validación
     * 
     * @param array $errors Errores de validación
     * @param string|null $message Mensaje opcional (default: 'Validation failed')
     * @return JsonResponse
     */
    protected function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message ?? 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Respuesta de recurso no encontrado
     * 
     * @param string $message Mensaje personalizado (default: 'Resource not found')
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }

    /**
     * Respuesta de no autorizado
     * 
     * @param string $message Mensaje personalizado (default: 'Unauthorized')
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }

    /**
     * Respuesta de prohibido/sin permisos
     * 
     * @param string $message Mensaje personalizado (default: 'Forbidden')
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json(['message' => $message], 403);
    }

    /**
     * Respuesta de recurso creado exitosamente
     * 
     * @param mixed $data Datos del recurso creado
     * @param string|null $message Mensaje opcional (default: 'Resource created successfully')
     * @return JsonResponse
     */
    protected function created($data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message ?? 'Resource created successfully', 201);
    }

    /**
     * Respuesta de recurso actualizado exitosamente
     * 
     * @param mixed $data Datos del recurso actualizado
     * @param string|null $message Mensaje opcional (default: 'Resource updated successfully')
     * @return JsonResponse
     */
    protected function updated($data = null, ?string $message = null): JsonResponse
    {
        return $this->success($data, $message ?? 'Resource updated successfully', 200);
    }

    /**
     * Respuesta de recurso eliminado exitosamente
     * 
     * @param string|null $message Mensaje opcional (default: 'Resource deleted successfully')
     * @return JsonResponse
     */
    protected function deleted(?string $message = null): JsonResponse
    {
        return $this->success(null, $message ?? 'Resource deleted successfully', 200);
    }

    /**
     * Respuesta sin contenido (204)
     * 
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json([], 204);
    }
}
