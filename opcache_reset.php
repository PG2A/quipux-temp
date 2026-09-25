<?php
header('Content-Type: text/plain; charset=utf-8');
$ok = function_exists('opcache_reset') ? opcache_reset() : null;
if ($ok === true) {
    echo "OK: opcache reseteado correctamente.\n";
} elseif ($ok === false) {
    echo "AVISO: opcache_reset() devolvio false (opcache deshabilitado o sin permisos).\n";
} else {
    echo "AVISO: la extension opcache no esta disponible en este PHP.\n";
}
if (function_exists('opcache_get_status')) {
    $st = @opcache_get_status(false);
    if (is_array($st)) {
        echo "opcache habilitado: " . (!empty($st['opcache_enabled']) ? 'si' : 'no') . "\n";
        if (isset($st['opcache_statistics']['num_cached_scripts'])) {
            echo "scripts en cache ahora: " . $st['opcache_statistics']['num_cached_scripts'] . "\n";
        }
    }
}
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
