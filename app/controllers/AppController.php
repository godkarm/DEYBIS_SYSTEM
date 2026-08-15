<?php
/**
 * AppController — Sirve el shell SPA
 */
class AppController {
    public function index(): void {
        Auth::requireLogin();

        // Base detectado automáticamente — sin depender de config/app.php
        $scriptBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        Response::view('app/shell', [
            'usuario'    => Session::usuario(),
            'permisos'   => Session::permisos(),
            'appName'    => APP_NAME,
            'appVersion' => APP_VERSION,
            'baseUrl'    => $scriptBase,   // ← se inyecta al JS como window.DS.base
        ]);
    }
}
