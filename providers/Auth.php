<?php

class Auth
{
    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function estaAutenticado(): bool
    {
        self::iniciarSesion();
        return !empty($_SESSION['admin_autenticado']);
    }

    public static function nombreActual(): ?string
    {
        self::iniciarSesion();
        return $_SESSION['admin_nombre'] ?? null;
    }

    public static function requerirAutenticacion(): void
    {
        if (!self::estaAutenticado()) {
            header('Location: login.php');
            exit;
        }
    }

    private static function cargarAdministradores(): array
    {
        $ruta = __DIR__ . '/../config/admins.php';
        if (!file_exists($ruta)) {
            throw new RuntimeException('Falta config/admins.php. Copia config/admins.example.php y complétalo.');
        }
        return require $ruta;
    }

    public static function iniciar(string $usuario, string $password): bool
    {
        self::iniciarSesion();

        foreach (self::cargarAdministradores() as $admin) {
            if (hash_equals($admin['nombre'], $usuario) && password_verify($password, $admin['password'])) {
                $_SESSION['admin_autenticado'] = true;
                $_SESSION['admin_nombre'] = $admin['nombre'];
                session_regenerate_id(true);
                self::registrarBitacora($admin['nombre'], 'login', 'Inicio de sesión exitoso');
                return true;
            }
        }

        self::registrarBitacora($usuario, 'login_fallido', 'Usuario o contraseña incorrectos');
        return false;
    }

    public static function registrarBitacora(string $nombreAdmin, string $accion, ?string $detalle = null): void
    {
        try {
            $pdo = Database::conectar();
            $pdo->prepare(
                'INSERT INTO bitacora_admin (nombre_admin, accion, detalle) VALUES (:nombre, :accion, :detalle)'
            )->execute([
                'nombre' => $nombreAdmin,
                'accion' => $accion,
                'detalle' => $detalle,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo registrar en la bitácora: ' . $e->getMessage());
        }
    }

    public static function cerrarSesion(): void
    {
        self::iniciarSesion();
        if (!empty($_SESSION['admin_nombre'])) {
            self::registrarBitacora($_SESSION['admin_nombre'], 'logout', 'Cierre de sesión');
        }
        $_SESSION = [];
        session_destroy();
    }
}
