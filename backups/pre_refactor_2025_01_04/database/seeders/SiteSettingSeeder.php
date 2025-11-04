<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Configuraciones de contacto
            [
                'key' => 'contact_email',
                'value' => 'contacto@mozoqr.com',
                'type' => 'email',
                'group' => 'contact',
                'label' => 'Email de Contacto',
                'description' => 'Email principal para consultas generales'
            ],
            [
                'key' => 'support_email',
                'value' => 'soporte@mozoqr.com',
                'type' => 'email',
                'group' => 'contact',
                'label' => 'Email de Soporte',
                'description' => 'Email para consultas técnicas y soporte'
            ],
            [
                'key' => 'whatsapp_number',
                'value' => '+5491234567890',
                'type' => 'phone',
                'group' => 'contact',
                'label' => 'Número de WhatsApp',
                'description' => 'Número de WhatsApp para consultas rápidas'
            ],
            [
                'key' => 'phone_number',
                'value' => '+54 11 1234-5678',
                'type' => 'phone',
                'group' => 'contact',
                'label' => 'Número de Teléfono',
                'description' => 'Número de teléfono principal'
            ],

            // Configuraciones de redes sociales
            [
                'key' => 'facebook_url',
                'value' => 'https://facebook.com/mozoqr',
                'type' => 'url',
                'group' => 'social',
                'label' => 'URL de Facebook',
                'description' => 'Enlace a la página de Facebook'
            ],
            [
                'key' => 'instagram_url',
                'value' => 'https://instagram.com/mozoqr',
                'type' => 'url',
                'group' => 'social',
                'label' => 'URL de Instagram',
                'description' => 'Enlace a la página de Instagram'
            ],
            [
                'key' => 'twitter_url',
                'value' => 'https://twitter.com/mozoqr',
                'type' => 'url',
                'group' => 'social',
                'label' => 'URL de Twitter',
                'description' => 'Enlace a la página de Twitter'
            ],
            [
                'key' => 'linkedin_url',
                'value' => 'https://linkedin.com/company/mozoqr',
                'type' => 'url',
                'group' => 'social',
                'label' => 'URL de LinkedIn',
                'description' => 'Enlace a la página de LinkedIn'
            ],

            // Configuraciones generales
            [
                'key' => 'company_name',
                'value' => 'MOZO QR',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Nombre de la Empresa',
                'description' => 'Nombre oficial de la empresa'
            ],
            [
                'key' => 'company_address',
                'value' => 'Buenos Aires, Argentina',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Dirección de la Empresa',
                'description' => 'Dirección física de la empresa'
            ],
            [
                'key' => 'business_hours',
                'value' => 'Lun - Vie: 9:00 - 18:00',
                'type' => 'text',
                'group' => 'general',
                'label' => 'Horarios de Atención',
                'description' => 'Horarios de atención al cliente'
            ],
            [
                'key' => 'google_analytics_id',
                'value' => '',
                'type' => 'text',
                'group' => 'general',
                'label' => 'ID de Google Analytics',
                'description' => 'ID para tracking de Google Analytics'
            ],

            // URLs de las apps
            [
                'key' => 'android_app_url',
                'value' => 'https://play.google.com/store/apps/details?id=com.mozoqr.app',
                'type' => 'url',
                'group' => 'general',
                'label' => 'URL de la App Android',
                'description' => 'Enlace a la aplicación en Google Play Store'
            ],
            [
                'key' => 'ios_app_url',
                'value' => 'https://apps.apple.com/app/mozoqr/id1234567890',
                'type' => 'url',
                'group' => 'general',
                'label' => 'URL de la App iOS',
                'description' => 'Enlace a la aplicación en App Store'
            ],

            // FAQs para la landing page
            [
                'key' => 'faqs_data',
                'value' => json_encode([
                    [
                        'question' => '¿Cómo funciona MOZO QR?',
                        'answer' => 'MOZO QR genera códigos QR únicos para cada mesa de tu restaurante. Los clientes escanean el código, acceden al menú digital y pueden llamar al mozo instantáneamente cuando necesiten atención.'
                    ],
                    [
                        'question' => '¿Necesito una aplicación especial?',
                        'answer' => 'Los clientes no necesitan descargar nada. Solo escanean el QR con la cámara de su teléfono. Tu personal puede usar nuestra app móvil para recibir notificaciones.'
                    ],
                    [
                        'question' => '¿Funciona sin internet?',
                        'answer' => 'MOZO QR requiere conexión a internet para funcionar. Recomendamos tener WiFi estable en tu restaurante para la mejor experiencia.'
                    ],
                    [
                        'question' => '¿Cuánto tiempo toma implementar?',
                        'answer' => 'La implementación es inmediata. Una vez que te registras, puedes generar los códigos QR y comenzar a usarlos el mismo día.'
                    ],
                    [
                        'question' => '¿Hay soporte técnico?',
                        'answer' => 'Sí, ofrecemos soporte técnico completo vía WhatsApp, email y chat en vivo durante horarios comerciales.'
                    ],
                    [
                        'question' => '¿Puedo personalizar el menú?',
                        'answer' => 'Por supuesto. Puedes subir tu menú en PDF, actualizarlo cuando quieras y personalizarlo completamente desde el panel de administración.'
                    ]
                ]),
                'type' => 'json',
                'group' => 'content',
                'label' => 'Preguntas Frecuentes',
                'description' => 'Lista de preguntas frecuentes para la landing page'
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
