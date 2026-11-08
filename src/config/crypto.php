<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Llave maestra de cifrado en reposo
    |--------------------------------------------------------------------------
    |
    | Se usa para cifrar llaves privadas RSA/Ed25519 y las llaves simétricas
    | temporales de la pantalla de demostración. Debe ser una llave de 32 bytes
    | codificada en Base64 con el prefijo "base64:". Nunca debe versionarse.
    |
    */
    'master_key' => env('CRYPTO_MASTER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Llave del índice ciego
    |--------------------------------------------------------------------------
    |
    | Se usa para calcular email_hash (HMAC-SHA256). Permite buscar por email
    | sin almacenar el email en claro. Si se rota, hay que recalcular los hashes.
    |
    */
    'blind_index_key' => env('CRYPTO_BLIND_INDEX_KEY'),
];
