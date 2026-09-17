<?php

class EmailService
{
    /**
     * OJO, Envía únicamente una alerta genérica — nada de descripción, nombre, empresa,
     * correo ni teléfono del reportante. El contenido real del caso se
     * consulta únicamente desde el panel administrativo (con sesión).
     */
    public static function notificarNuevoCaso(string $numeroReferencia, string $nombreCategoria): bool
    {
        $apiKey = $_ENV['RESEND_API_KEY'] ?? null;
        $desde = $_ENV['EMAIL_FROM'] ?? 'onboarding@resend.dev';
        $hacia = $_ENV['EMAIL_TO'] ?? 'canal.etico@logienter.com';

        if (!$apiKey) {
            error_log('EmailService: falta RESEND_API_KEY en .env, no se envió la notificación.');
            return false;
        }

        $cuerpo = '<p>Te llegó un nuevo reporte a través del Canal Ético.</p>'
            . "<p><strong>N° de referencia:</strong> {$numeroReferencia}<br>"
            . '<strong>Categoría:</strong> ' . htmlspecialchars($nombreCategoria) . '</p>'
            . '<p>Ingresa al panel administrativo para ver el detalle completo.</p>';

        $payload = json_encode([
            'from' => $desde,
            'to' => [$hacia],
            'subject' => "Nuevo reporte — {$numeroReferencia}",
            'html' => $cuerpo,
        ]);

        $contexto = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 10,
            ],
        ]);

        $respuesta = @file_get_contents('https://api.resend.com/emails', false, $contexto);

        if ($respuesta === false) {
            error_log('EmailService: no se pudo contactar a Resend (revisa tu conexión a internet).');
            return false;
        }

        $codigo = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $m)) {
                $codigo = (int) $m[1];
                break;
            }
        }

        if ($codigo < 200 || $codigo >= 300) {
            error_log("EmailService: Resend respondió {$codigo} - {$respuesta}");
            return false;
        }

        error_log("EmailService: notificación enviada a {$hacia} para el caso {$numeroReferencia}.");

        return true;
    }
}