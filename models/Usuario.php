<?php

class Usuario
{
    public function __construct(private PDO $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $q = $this->db->prepare("
            SELECT
                u.*,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.email = ?
            AND u.activo = 1
        ");

        $q->execute([$email]);

        return $q->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // Registro público: siempre Cliente
    public function create(
        string $nombre,
        string $email,
        string $password
    ): bool {

        $rol = $this->db
            ->query("SELECT id FROM roles WHERE nombre = 'Cliente'")
            ->fetchColumn();

        $q = $this->db->prepare("
            INSERT INTO usuarios
            (nombre, email, password_hash, rol_id)
            VALUES (?, ?, ?, ?)
        ");

        return $q->execute([
            $nombre,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $rol
        ]);
    }


    // Crear usuario indicando rol
    // Esto debe utilizarlo únicamente el administrador
    public function createWithRole(
        string $nombre,
        string $email,
        string $password,
        string $rol
    ): bool {

        $rolesPermitidos = [
            'Administrador',
            'Subastador',
            'Cliente',
            'Auditor'
        ];

        if (!in_array($rol, $rolesPermitidos, true)) {
            throw new Exception("Rol no válido.");
        }

        $qRol = $this->db->prepare("
            SELECT id
            FROM roles
            WHERE nombre = ?
        ");

        $qRol->execute([$rol]);

        $rolId = $qRol->fetchColumn();

        if (!$rolId) {
            throw new Exception("El rol no existe.");
        }

        $q = $this->db->prepare("
            INSERT INTO usuarios
            (nombre, email, password_hash, rol_id)
            VALUES (?, ?, ?, ?)
        ");

        return $q->execute([
            $nombre,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $rolId
        ]);
    }


    public function all(): array
    {
        return $this->db->query("
            SELECT
                u.id,
                u.nombre,
                u.email,
                r.nombre AS rol,
                u.activo,
                u.creado_en
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            ORDER BY u.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }


    public function cambiarRol(int $usuarioId, string $rol): bool
    {
        $rolesPermitidos = [
            'Administrador',
            'Subastador',
            'Cliente',
            'Auditor'
        ];

        if (!in_array($rol, $rolesPermitidos, true)) {
            throw new Exception("Rol no válido.");
        }

        $qRol = $this->db->prepare("
            SELECT id
            FROM roles
            WHERE nombre = ?
        ");

        $qRol->execute([$rol]);

        $rolId = $qRol->fetchColumn();

        if (!$rolId) {
            throw new Exception("El rol no existe.");
        }

        $q = $this->db->prepare("
            UPDATE usuarios
            SET rol_id = ?
            WHERE id = ?
        ");

        return $q->execute([
            $rolId,
            $usuarioId
        ]);
    }


    public function activarDesactivar(
        int $usuarioId,
        int $estado
    ): bool {

        $q = $this->db->prepare("
            UPDATE usuarios
            SET activo = ?
            WHERE id = ?
        ");

        return $q->execute([
            $estado,
            $usuarioId
        ]);
    }
}