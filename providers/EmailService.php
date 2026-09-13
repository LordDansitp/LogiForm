<?php

class EmailService
{
    public static function notificarNuevoCaso(array $caso, string $nombreCategoria): bool
    {
        $apiKey = $_ENV['RESEND_API_KEY'] ?? null;
        $desde = $_ENV['EMAIL_FROM'] ?? 'onboarding@resend.dev';
        $hacia = $_ENV['EMAIL_TO'] ?? 'canal.etico@logienter.com';

        if (!$apiKey) {
            error_log('EmailService: falta RESEND_API_KEY en .env, no se envió la notificación.');
            return false;
        }

        $remitente = $caso['es_anonimo'] ? 'Anónimo' : ($caso['contacto_nombre'] ?: 'Sin nombre');

        $cuerpo = '<h2>Nuevo reporte recibido — Canal Ético</h2>'
            . "<p><strong>N° de referencia:</strong> {$caso['numero_referencia']}</p>"
            . '<p><strong>Categoría:</strong> ' . htmlspecialchars($nombreCategoria) . '</p>'
            . "<p><strong>Fecha del suceso:</strong> {$caso['fecha_suceso']}</p>"
            . '<p><strong>Remitente:</strong> ' . htmlspecialchars($remitente) . '</p>';

        if (!$caso['es_anonimo']) {
            $cuerpo .= '<p><strong>Email:</strong> ' . htmlspecialchars($caso['contacto_email'] ?: '—') . '</p>'
                . '<p><strong>Teléfono:</strong> ' . htmlspecialchars($caso['contacto_telefono'] ?: '—') . '</p>';
        }

        $cuerpo .= '<p><strong>Descripción:</strong><br>' . nl2br(htmlspecialchars($caso['descripcion'])) . '</p>'
            . '<hr><p style="color:#888;font-size:12px">Entra a la tabla "casos" en Supabase para ver el detalle completo y los adjuntos.</p>';

        $payload = json_encode([
            'from' => $desde,
            'to' => [$hacia],
            'subject' => "Nuevo caso — {$caso['numero_referencia']} ({$nombreCategoria})",
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

        error_log("EmailService: correo enviado a {$hacia} para el caso {$caso['numero_referencia']}.");

        return true;
    }
}
