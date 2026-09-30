<?php
class AuthController {
    public function __construct(private PDO $db) {}
    public function login(): void {
        verify_csrf();
        $u=(new Usuario($this->db))->findByEmail(trim($_POST['email']??''));
        if ($u && password_verify($_POST['password']??'', $u['password_hash'])) {
            $_SESSION['user']=['id'=>$u['id'],'nombre'=>$u['nombre'],'email'=>$u['email'],'rol'=>$u['rol']];
            $this->db->prepare("INSERT INTO auditoria(usuario_id,evento,detalle) VALUES(?,?,?)")->execute([$u['id'],'LOGIN','Inicio de sesión']);
            redirect('index.php?page=dashboard');
        }
        $_SESSION['flash']='Credenciales inválidas.'; redirect('index.php?page=login');
    }
    public function registro(): void {
        verify_csrf();
        $nombre=trim($_POST['nombre']??''); $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
        if(!$nombre || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<6) {
            $_SESSION['flash']='Revise los datos. La contraseña debe tener al menos 6 caracteres.'; redirect('index.php?page=registro');
        }
        try {(new Usuario($this->db))->create($nombre,$email,$pass); $_SESSION['flash']='Registro creado. Ya puede iniciar sesión.';}
        catch(Throwable $e){$_SESSION['flash']='No se pudo registrar; posiblemente el correo ya existe.';}
        redirect('index.php?page=login');
    }
    public function logout(): void { session_destroy(); redirect('index.php?page=login'); }
}
