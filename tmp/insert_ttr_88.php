<?php
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/config.php';
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/include/db/ConnectionHandler.php';
$db = new ConnectionHandler( '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca');

$res = $db->conn->Execute("SELECT * FROM sgd_ttr_transaccion order by sgd_ttr_codigo desc limit 5");
while (!$res->EOF) {
    print_r($res->fields);
    $res->MoveNext();
}

try {
    $db->conn->Execute("INSERT INTO sgd_ttr_transaccion (sgd_ttr_codigo, sgd_ttr_descrip) VALUES (88, 'Asociar Documento a Carpeta Virtual') ON CONFLICT DO NOTHING");
    echo "Inserted 88\n";
} catch (Exception $e) {
    echo "Error inserting: " . $e->getMessage() . "\n";
}
?>
