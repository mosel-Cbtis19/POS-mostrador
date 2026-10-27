<?php
// =============================================================================
// PROYECTO: SysVentas - Sistema de Punto de Venta de Mostrador (POS)
// INSTITUCIÓN: CBTis No. 19 "Miguel Hidalgo y Costilla" - Colima, Col., México
// ARCHIVO: app/controllers/UsuarioController.php
// DESCRIPCIÓN: Controlador para la gestión y baja lógica de cajeros por el Administrador.
// =============================================================================

require_once __DIR__ . '/BaseController.php';

class UsuarioController extends BaseController {

    /**
     * Muestra el panel de administración de cajeros (Exclusivo Administrador)
     */
    public function index(): void {
        $this->requireAuth();
        $this->requireAdmin();

        $usuarioModel = new Usuario();
        $cajeros = $usuarioModel->obtenerCajeros();

        $mensaje = '';
        if (isset($_GET['msg'])) {
            if ($_GET['msg'] === 'creado') {
                $mensaje = 'Cajero registrado exitosamente en el sistema.';
            } elseif ($_GET['msg'] === 'actualizado') {
                $mensaje = 'Estado del cajero actualizado correctamente.';
            } elseif ($_GET['msg'] === 'error_usuario') {
                $mensaje = 'Error: El nombre de usuario ya se encuentra registrado.';
            } elseif ($_GET['msg'] === 'error_pass') {
                $mensaje = 'Error: La contraseña debe tener al menos 6 caracteres.';
            }
        }

        $this->view('usuarios/index', [
            'cajeros' => $cajeros,
            'mensaje' => $mensaje
        ]);
    }

    /**
     * Procesa el registro de un nuevo cajero con contraseña encriptada (BCRYPT)
     */
    public function guardar(): void {
        $this->requireAuth();
        $this->requireAdmin();
        $this->requireCsrf();

        $nombreCompleto = $this->sanitize($_POST['nombre_completo'] ?? '');
        $usuario = strtolower(trim($this->sanitize($_POST['usuario'] ?? '')));
        $telefono = $this->sanitize($_POST['telefono'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nombreCompleto) || empty($usuario) || empty($password)) {
            $this->redirect('?c=usuario&a=index&msg=error_campos');
        }

        if (strlen($password) < 6) {
            $this->redirect('?c=usuario&a=index&msg=error_pass');
        }

        $usuarioModel = new Usuario();

        // Evitar duplicidad de nombres de usuario
        if ($usuarioModel->existeUsuario($usuario)) {
            $this->redirect('?c=usuario&a=index&msg=error_usuario');
        }

        $datos = [
            'rol_id' => 2, // Rol 2: Cajero
            'nombre_completo' => $nombreCompleto,
            'usuario' => $usuario,
            'password' => $password,
            'telefono' => $telefono
        ];

        $usuarioModel->crearUsuario($datos);

        $this->redirect('?c=usuario&a=index&msg=creado');
    }

    /**
     * Alterna el estado del cajero (Baja Lógica / Reactivación)
     */
    public function cambiarEstado(): void {
        $this->requireAuth();
        $this->requireAdmin();

        $id = $this->validateInt($_GET['id'] ?? 0);
        $nuevoEstado = isset($_GET['estado']) && $_GET['estado'] === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';

        if ($id > 0) {
            $usuarioModel = new Usuario();
            $usuarioModel->cambiarEstado($id, $nuevoEstado);
        }

        $this->redirect('?c=usuario&a=index&msg=actualizado');
    }
}
