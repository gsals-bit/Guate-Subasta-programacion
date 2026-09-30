<?php
class PujaController {
    public function __construct(private PDO $db) {}
    public function crear(): void {
        require_role(['Cliente']); verify_csrf();
        [$ok,$msg]=(new Puja($this->db))->realizar((int)$_POST['subasta_id'],(int)$_SESSION['user']['id'],(float)$_POST['monto']);
        $_SESSION['flash']=$msg;
        redirect('index.php?page=detalle&id='.(int)$_POST['subasta_id']);
    }
}
