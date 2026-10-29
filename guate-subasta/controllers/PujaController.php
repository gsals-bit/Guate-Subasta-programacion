<?php
class PujaController {
    public function __construct(private PDO $db) {}
    public function crear(): void {
        require_role(['Cliente']); verify_csrf();
        $subastaId = (int)$_POST['subasta_id'];
        $usuarioId = (int)$_SESSION['user']['id'];
        $monto = (float)$_POST['monto'];

        [$ok,$msg]=(new Puja($this->db))->realizar($subastaId,$usuarioId,$monto);

        // NUEVO: si la puja se guardó, avisar a los demás usuarios.
        // Va fuera de la transacción de la puja: si el aviso fallara, la puja no se ve afectada.
        if ($ok && class_exists('AgendaSubastas')) {
            try {
                (new AgendaSubastas($this->db))->avisarPuja($subastaId,$usuarioId,$monto);
            } catch (Throwable $ignorado) {}
        }

        $_SESSION['flash']=$msg;
        redirect('index.php?page=detalle&id='.$subastaId);
    }
}