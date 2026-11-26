<?php
declare(strict_types=1);
require __DIR__.'/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("No disponible por HTTP.\n");
}

$accounts = [
    ['Administrador M&T', 'MT_ADMIN_EMAIL', 'MT_ADMIN_PASSWORD', 'Administrador'],
    ['Vendedor M&T', 'MT_SELLER_EMAIL', 'MT_SELLER_PASSWORD', 'Vendedor']
];

// El repartidor es opcional; se crea únicamente si se configuran ambas variables.
if (getenv('MT_COURIER_EMAIL') !== false || getenv('MT_COURIER_PASSWORD') !== false) {
    $accounts[] = ['Repartidor M&T', 'MT_COURIER_EMAIL', 'MT_COURIER_PASSWORD', 'Repartidor'];
}

foreach ($accounts as [$name, $emailKey, $passwordKey, $profile]) {
    $email = strtolower(trim((string)getenv($emailKey)));
    $password = (string)getenv($passwordKey);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || strlen($password) > 200) {
        fwrite(STDERR, "Configura {$emailKey} y {$passwordKey}; la contraseña debe tener al menos 12 caracteres.\n");
        exit(2);
    }
    $q = db()->prepare('SELECT id FROM users WHERE email=?');
    $q->execute([$email]);
    if ($q->fetchColumn()) {
        echo "{$profile}: la cuenta {$email} ya existe; no se modificó.\n";
        continue;
    }
    $insert = db()->prepare('INSERT INTO users(name,email,password_hash,profile) VALUES(?,?,?,?)');
    $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $profile]);
    echo "{$profile}: cuenta creada para {$email}.\n";
}
echo "Instalación de personal terminada. Guarda las credenciales en un gestor seguro y elimina este script del servidor.\n";
