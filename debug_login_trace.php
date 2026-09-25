<?php

function quipux_login_trace($label, $data = []) {
    $file = __DIR__ . '/saml/debug_logs/login_trace.log';

    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0700, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $label;

    if (!empty($data)) {
        $line .= ' | ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    file_put_contents($file, $line . PHP_EOL, FILE_APPEND);
}
