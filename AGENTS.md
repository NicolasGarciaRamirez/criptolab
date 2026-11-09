# AGENTS.md — CriptoLab

## Propósito

Aplicación educativa de criptografía con Laravel 13, PostgreSQL 18, OpenSSL y Sodium. El código de `src/` es el proyecto Laravel; la raíz contiene Docker, Makefile y documentación.

## Comandos

```bash
make up                 # docker compose up -d --build
make down
make logs
make shell
make migrate
make seed
make fresh              # destructivo, solo local
make test               # php artisan test dentro del contenedor
make test-filter FILTER=NombreTest
make pint               # Laravel Pint
make npm                # compilar assets
make db                 # psql
```

Ejecutar comandos de Laravel siempre dentro del contenedor `app` (o con `make`). No ejecutar `php artisan` en el host contra PostgreSQL.

## Arquitectura

- `src/app/Http/Controllers/Crypto/`: controladores delgados del laboratorio, llaves, historial y pantalla 5.4.
- `src/app/Http/Requests/Crypto/`: validación de entradas criptográficas.
- `src/app/Services/Crypto/Contracts/`: interfaces comunes.
- `src/app/Services/Crypto/Symmetric/`, `Asymmetric/`, `Hash/`: implementaciones con OpenSSL/Sodium.
- `src/app/Services/Crypto/Registries/`: catálogo de algoritmos; se registran en `CryptoServiceProvider`.
- `src/app/Services/Crypto/AuthDemoService.php`: creación/descifrado/re-cifrado de registros de la pantalla 5.4.
- `src/app/Services/Crypto/KeypairService.php`: generación, importación y almacenamiento cifrado de llaves.
- `src/app/Services/Crypto/FileCryptoService.php`: archivos en memoria, máximo 5 MB, solo cifrados autenticados.
- `src/app/Services/Crypto/KeyAgreementService.php`: X25519 y derivación HKDF.
- `src/app/Services/Crypto/SignedEncryptionService.php`: firma + cifrado.
- `src/app/Services/Crypto/X509Service.php`: certificados autofirmados.
- `src/app/Services/Crypto/{AesModesComparisonService,NonceReuseDemoService}.php`: demos educativos aislados.
- `src/app/Services/Crypto/{TimingComparisonService,RainbowTableService,BenchmarkService,QuizService}.php`: utilidades docentes.
- `src/app/Auth/`: provider de usuario y token repository de recuperación basados en `email_hash`.
- `src/app/Casts/EncryptedWithMasterKey.php`: cast para llaves privadas.
- `src/resources/js/crypto.js`: componentes Alpine existentes y cliente fetch con CSRF.
- `src/resources/js/labs.js`: componentes Alpine de los módulos avanzados.
- `src/resources/views/components/lab-guide.blade.php`: instructivo reutilizable de cada laboratorio.
- `src/resources/views/crypto/partials/subnav.blade.php`: subnavegación común.

## Convenciones

- PSR-12 y `declare(strict_types=1);` en servicios, DTOs y clases nuevas de dominio.
- Controladores delgados; la lógica criptográfica vive en servicios.
- Usar FormRequest para validar; no validar criptografía solo en el controlador.
- Comentarios en español explicando el paso criptográfico cuando aporten valor académico.
- No crear algoritmos propios: solo OpenSSL y Sodium.
- Cada IV/nonce debe generarse con `random_bytes()` y nunca reutilizarse.
- Comparaciones sensibles con `hash_equals()` o las funciones de verificación de la librería.
- No registrar contraseñas, textos claros ni llaves privadas.
- No mostrar llaves privadas después de generarlas; solo la primera respuesta de generación.
- Usar `CryptoEnvelope` para el formato versionado de ciphertext.
- Los errores criptográficos deben ser mensajes en español sin filtrar llaves.
- El laboratorio y la pantalla 5.4 requieren usuario autenticado y email verificado.
- El admin solo accede a usuarios y auditoría; no puede editar cuentas desde la interfaz.
- Cualquier demo insegura (ECB, MD5/SHA-1, nonce reuse) debe quedar en un endpoint aislado, con advertencia visible y sin reutilizar los servicios criptográficos reales.
- Los módulos de archivos no deben persistir archivos; procesar en memoria y respetar el límite configurado.
- Los instructivos deben incluir objetivo, pasos, resultado esperado, interpretación y nota para el docente.

## Seguridad

- `.env` y las claves nunca se versionan. Solo `.env.example` con placeholders.
- `CRYPTO_MASTER_KEY` y `CRYPTO_BLIND_INDEX_KEY` se generan en desarrollo; en producción deben respaldarse.
- `users.password` siempre con hash Argon2id. No usar cifrado reversible para contraseñas reales.
- `users.email` está cifrado; usar `email_hash` para búsquedas por igualdad.
- Los campos `password_plain`/`username_plain` de la demo son intencionalmente visibles en la interfaz, pero se guardan cifrados con el cast `encrypted`.
- La pantalla 5.4 debe mantener el aviso de que es solo educativa.
- No introducir dependencias criptográficas externas sin justificar; la actividad exige OpenSSL/Sodium.
- Mantener `SESSION_ENCRYPT=true`, cookies `httponly` y `samesite=lax`.
- `SecurityHeaders` debe seguir registrado globalmente.
- Al agregar campos sensibles, usar casts/cifrado y excluirlos de `dontFlash` y de logs.

## Pruebas

- Antes de terminar un cambio: `make pint` y `make test`.
- Agregar pruebas unitarias para servicios criptográficos y feature tests para flujos HTTP.
- Las pruebas usan SQLite en memoria forzado en `phpunit.xml`; no deben tocar PostgreSQL.
- Nunca escribir pruebas que dependan de claves reales del `.env`; usar las claves de prueba de `phpunit.xml`.
- Cubrir: roundtrip, aleatoriedad de IV, detección de manipulación, verificación de firmas, autorización por propietario y ausencia de datos sensibles en logs.
- Cubrir también archivos, AAD, X25519, ECDSA, firma+cifrado, X.509, importación PEM, timing, rainbow, benchmark, quiz y los instructivos presentes en las vistas.
- Evitar pruebas frágiles basadas en tiempos exactos; validar estructura y resultados criptográficos.

## Docker

- El contenedor `app` corre como usuario `app` (UID/GID configurables en `.env`).
- El entrypoint instala dependencias, genera claves, migra y siembra si `AUTO_SEED=true`.
- PostgreSQL 18 usa el volumen en `/var/lib/postgresql` (no `/var/lib/postgresql/data`).
- Mailpit es local; Adminer está en el perfil `tools`.
- No ejecutar procesos como root dentro de `app`.
