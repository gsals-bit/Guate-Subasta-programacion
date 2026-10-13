<?php

class AuthController
{
    public function __construct(private PDO $db)
    {
    }

    public function login(): void
    {
        verify_csrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuario = (new Usuario($this->db))
            ->findByEmail($email);

        if (
            $usuario &&
            password_verify(
                $password,
                $usuario['password_hash']
            )
        ) {

            $_SESSION['user'] = [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol']
            ];

            $this->db
                ->prepare("
                    INSERT INTO auditoria
                    (usuario_id, evento, detalle)
                    VALUES (?, ?, ?)
                ")
                ->execute([
                    $usuario['id'],
                    'LOGIN',
                    'Inicio de sesión'
                ]);


            // REDIRECCIÓN SEGÚN ROL

            switch ($usuario['rol']) {

                case 'Administrador':
                    redirect(
                        'index.php?page=admin_dashboard'
                    );
                    break;

                case 'Subastador':
                    redirect(
                        'index.php?page=subastador_dashboard'
                    );
                    break;

                case 'Auditor':
                    redirect(
                        'index.php?page=auditor_dashboard'
                    );
                    break;

                case 'Cliente':
                default:
                    redirect(
                        'index.php?page=dashboard'
                    );
                    break;
            }
        }

        $_SESSION['flash'] =
            'Correo electrónico o contraseña incorrectos.';

        redirect('index.php?page=login');
    }


    // Registro público
    // Siempre crea Cliente
    public function registro(): void
    {
        verify_csrf();

        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';

        if (
            !$nombre ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($pass) < 6
        ) {

            $_SESSION['flash'] =
                'Revise los datos. La contraseña debe tener al menos 6 caracteres.';

            redirect('index.php?page=registro');
        }

        try {

            (new Usuario($this->db))->create(
                $nombre,
                $email,
                $pass
            );

            $_SESSION['flash'] =
                'Registro creado correctamente. Ya puede iniciar sesión.';

        } catch (Throwable $e) {

            $_SESSION['flash'] =
                'No se pudo registrar. Posiblemente el correo ya existe.';
        }

        redirect('index.php?page=login');
    }


    public function logout(): void
    {
        session_destroy();

        redirect('index.php?page=login');
    }
}