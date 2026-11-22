<?php

return [
    // En Laragon, configurar la ruta completa a mysqldump.exe si no está en PATH.
    // Ejemplo en .env: SITA_MYSQLDUMP_BIN="C:/laragon/bin/mysql/mysql-8.x/bin/mysqldump.exe"
    'mysqldump_bin' => env('SITA_MYSQLDUMP_BIN', 'mysqldump'),
];
