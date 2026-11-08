# CriptoLab · Criptografía aplicada en PHP/Laravel

Aplicación web educativa para la actividad evaluativa **“Criptografía en Java”**, implementada en **PHP 8.5 + Laravel 13 + PostgreSQL 18** y ejecutada con Docker. Cubre cifrado simétrico, asimétrico, firmas digitales, hashes, derivación de llaves, autenticación real con Argon2id y cifrado de datos sensibles en reposo.

> **Nota para el docente:** el enunciado original menciona Java y `javax.crypto`. Esta entrega usa PHP/Laravel, pero implementa los mismos conceptos con OpenSSL y Sodium. Al final de este README se incluye la tabla de equivalencias **Java ↔ PHP** para justificar la adaptación.

---

## 1. Qué incluye

- Registro, login por `username`, logout, “recordarme”, verificación de email y recuperación de contraseña.
- Roles `user` y `admin`; el administrador consulta usuarios y el registro de actividad.
- Laboratorio AJAX interactivo:
  - **Simétrico:** AES-256-GCM, AES-256-CBC, AES-128-CTR, ChaCha20-Poly1305 y XSalsa20-Poly1305.
  - **Asimétrico:** RSA-OAEP 2048/4096, Sodium sealed box (X25519) y cifrado híbrido RSA+AES-256-GCM.
  - **Firmas:** RSA-SHA256 y Ed25519.
  - **Hash:** SHA-256, SHA-512, SHA3-256, BLAKE2b-512, HMAC-SHA256, bcrypt y Argon2id.
  - Derivación PBKDF2-SHA256 y Argon2id con salt visible.
- Pantalla **5.4 “Formulario de autenticación cifrado”**: usuario con cifrado simétrico, contraseña con RSA-OAEP (o familias invertidas), tabla con texto claro/cifrado, descifrado por fila y exportación JSON.
- Cifrado en reposo de email, teléfono, llaves privadas y campos sensibles de la demo.
- Historial de operaciones sin textos claros ni llaves privadas.
- Páginas de error personalizadas, cabeceras de seguridad, rate limiting y mensajes en español.
- Pruebas unitarias y de integración con PHPUnit 12.

---

## 2. Stack

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP 8.5 (Docker `php:8.5-fpm-bookworm`) |
| Framework | Laravel 13.x (`13.40.0` al desarrollar) |
| Autenticación | Laravel Breeze 2.4.2 (Blade) |
| Base de datos | PostgreSQL 18 Alpine |
| Servidor web | Nginx 1.30 + PHP-FPM |
| Frontend | Blade + Tailwind CSS 3.4 + Alpine.js |
| Build | Vite 8 + Node 24 LTS |
| Pruebas | PHPUnit 12.5 |
| Calidad | Laravel Pint (PSR-12) |
| Correo local | Mailpit |
| Administración BD | Adminer (perfil opcional) |
| Contenedores | Docker + Docker Compose |

Extensiones PHP habilitadas: `openssl`, `sodium`, `mbstring`, `pdo_pgsql`, `pgsql`, `bcmath`, `gd`, `zip`, `intl`, `opcache`.

---

## 3. Requisitos previos

- Docker Engine 24+ y Docker Compose v2+.
- Puertos libres: `8080` (app), `8025` (Mailpit), `8081` (Adminer, opcional). Si están ocupados, cámbialos en `.env`.
- No es necesario tener PHP, Composer ni Node instalados en el host: todo corre en contenedores.

---

## 4. Instalación rápida

```bash
git clone <URL-DEL-REPOSITORIO> criptografia
cd criptografia

cp .env.example .env
# Opcional: el entrypoint también crea src/.env si no existe.
cp src/.env.example src/.env

docker compose up -d --build
```

El primer arranque puede tardar varios minutos. El `entrypoint.sh` del contenedor `app`:

1. Crea `src/.env` si falta.
2. Genera `APP_KEY`, `CRYPTO_MASTER_KEY` y `CRYPTO_BLIND_INDEX_KEY` si están vacías.
3. Instala dependencias de Composer y Node si faltan.
4. Compila assets con Vite si falta el manifest.
5. Ejecuta migraciones.
6. Siembra datos de demostración de forma idempotente (`AUTO_SEED=true` en el `.env` raíz).

Abre:

- Aplicación: <http://localhost:8080>
- Mailpit: <http://localhost:8025>
- Adminer (opcional): `docker compose --profile tools up -d adminer` → <http://localhost:8081>

### Si un puerto está ocupado

Edita `.env`:

```dotenv
APP_PORT=8090
APP_URL=http://localhost:8090
ADMINER_PORT=8091
```

y ejecuta de nuevo `docker compose up -d`.

### Credenciales demo

| Rol | Usuario | Contraseña | Email |
|---|---|---|---|
| Administrador | `admin` | `Admin1234!` | `admin@criptolab.test` |
| Estudiante | `demo` | `Demo1234!` | `demo@criptolab.test` |

`demo` tiene dos registros precargados en la pantalla 5.4. Se recomienda cambiar estas claves si el entorno deja de ser local.

---

## 5. Comandos útiles

```bash
make up            # docker compose up -d --build
make down          # detener
make logs          # logs en vivo
make shell         # bash dentro del contenedor app
make migrate       # php artisan migrate
make seed          # php artisan db:seed
make fresh         # migrate:fresh --seed (destructivo)
make test          # php artisan test
make test-filter FILTER=AuthDemoTest
make pint          # Laravel Pint
make npm           # npm run build
make db            # psql dentro del contenedor db
make adminer       # Adminer opcional
```

También puede usarse `docker compose exec app php artisan ...`.

---

## 6. Rutas principales

| Ruta | Descripción |
|---|---|
| `/` | Landing pública |
| `/teoria` | Simétrico vs asimétrico vs hash + equivalencias Java/PHP |
| `/login`, `/register` | Autenticación real (hash Argon2id) |
| `/dashboard` | Panel del usuario autenticado |
| `/lab` | Laboratorio criptográfico |
| `/lab/simetrico` | Cifrado simétrico |
| `/lab/asimetrico` | Cifrado asimétrico, híbrido y firmas |
| `/lab/hash` | Hashes, HMAC y contraseñas |
| `/lab/autenticacion-cifrada` | **Pantalla 5.4** |
| `/lab/historial` | Historial de operaciones |
| `/lab/llaves` | Llaves públicas guardadas |
| `/admin/usuarios` | Listado de usuarios (solo admin) |
| `/admin/auditoria` | Registro de actividad (solo admin) |

---

## 7. Algoritmos implementados

| Familia | Algoritmo | Implementación |
|---|---|---|
| Simétrico | AES-256-GCM | `app/Services/Crypto/Symmetric/Aes256Gcm.php` |
| Simétrico | AES-256-CBC | `app/Services/Crypto/Symmetric/Aes256Cbc.php` |
| Simétrico | AES-128-CTR | `app/Services/Crypto/Symmetric/Aes128Ctr.php` |
| Simétrico | ChaCha20-Poly1305 | `app/Services/Crypto/Symmetric/ChaCha20Poly1305.php` |
| Simétrico | XSalsa20-Poly1305 | `app/Services/Crypto/Symmetric/Xsalsa20Poly1305.php` |
| Asimétrico | RSA-OAEP 2048/4096 | `app/Services/Crypto/Asymmetric/RsaOaep.php` |
| Asimétrico | Sealed box X25519 | `app/Services/Crypto/Asymmetric/SealedBox.php` |
| Asimétrico | Híbrido RSA + AES-256-GCM | `app/Services/Crypto/Asymmetric/HybridEncryption.php` |
| Firma | RSA-SHA256 | `app/Services/Crypto/Asymmetric/RsaSigner.php` |
| Firma | Ed25519 | `app/Services/Crypto/Asymmetric/Ed25519Signer.php` |
| Hash | SHA-256, SHA-512, SHA3-256, BLAKE2b-512 (Sodium) | `app/Services/Crypto/Hash/HashService.php` |
| Hash | HMAC-SHA256 | `app/Services/Crypto/Hash/HashService.php` |
| Contraseñas | bcrypt, Argon2id | `app/Services/Crypto/Hash/PasswordHasher.php` y `HashService.php` |
| Derivación | PBKDF2-SHA256, Argon2id | `app/Services/Crypto/KeyDerivationService.php` |
| Registro de algoritmos | Registries + `CryptoServiceProvider` | `app/Services/Crypto/Registries/` |

Los contratos comunes están en `app/Services/Crypto/Contracts/`.

### Detalles técnicos

- Los ciphertext viajan en un sobre JSON versionado: `version`, `algorithm`, `ciphertext`, `iv`, `tag`, `meta`.
- Los IV/nonce se generan con `random_bytes()` en cada operación.
- GCM, ChaCha20-Poly1305 y XSalsa20 detectan manipulación; CBC y CTR se muestran como comparación y se advierte que no autentican.
- RSA-OAEP usa el relleno OAEP de OpenSSL/PHP. El máximo es **214 bytes para 2048 bits** y **470 bytes para 4096 bits**; para textos largos se ofrece el cifrado híbrido.
- Las comparaciones de HMAC usan `hash_equals()`; las firmas se validan con `openssl_verify()`/Sodium.
- No se implementan algoritmos propios.

---

## 8. Cifrado en reposo y modelo de datos

| Dato | Protección |
|---|---|
| `users.password` | Hash Argon2id (`HASH_DRIVER=argon2id`) |
| `users.email` | Cast `encrypted` de Eloquent |
| `users.email_hash` | HMAC-SHA256 con `CRYPTO_BLIND_INDEX_KEY` (índice ciego para login/recuperación/unicidad) |
| `users.phone` | Cast `encrypted` |
| `password_reset_tokens.email` | Hash ciego (el token repository personalizado nunca guarda el email en claro) |
| `crypto_keypairs.private_key_encrypted` | XSalsa20-Poly1305 con `CRYPTO_MASTER_KEY` mediante `EncryptedWithMasterKey` |
| `crypto_demo_entries.username_plain` / `password_plain` | Casts `encrypted`; la demo los muestra descifrados solo al propietario |
| `crypto_demo_entries.username_encrypted` / `password_encrypted` | Ciphertext real de la demostración |
| `crypto_demo_entries.meta.symmetric_key_encrypted` | Llave simétrica temporal cifrada con la llave maestra |
| `sessions.payload` | Sesión cifrada (`SESSION_ENCRYPT=true`) |
| `crypto_operations` | Solo tipo, algoritmo, contexto, éxito y fecha |
| `audit_logs` | Acción, usuario, IP, user-agent y metadatos filtrados |

### Tablas

- `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs` (Laravel).
- `crypto_demo_entries`.
- `crypto_keypairs`.
- `crypto_operations`.
- `audit_logs`.

El email cifrado no es directamente buscable; por eso se usa `email_hash`. El login real se hace por `username`, lo que evita depender del email para autenticar.

### Verificación directa en PostgreSQL

```bash
make db
```

```sql
SELECT username, left(email, 60) AS email_cifrado, left(email_hash, 20) AS hash_ciego,
       left(phone, 40) AS telefono_cifrado, left(password, 30) AS password_hash
FROM users;

SELECT id, username_plain, password_plain,
       left(username_encrypted, 50) AS usuario_cifrado,
       left(password_encrypted, 50) AS password_cifrado
FROM crypto_demo_entries;

SELECT id, algorithm, left(private_key_encrypted, 40) AS privada_cifrada
FROM crypto_keypairs;

SELECT email, left(token, 20) FROM password_reset_tokens;
```

Los campos anteriores deben verse como ciphertext/hash, nunca como el valor original. El SQL no modifica nada.

---

## 9. Simétrico vs asimétrico vs hash

- **Simétrico:** una misma llave cifra y descifra. Rápido; requiere proteger y compartir la llave. En la demo se usa para el usuario.
- **Asimétrico:** la llave pública cifra/verifica y la privada descifra/firma. Resuelve el intercambio de llaves y permite firmas; es más lento y tiene límite de tamaño. En la demo se usa para la contraseña.
- **Hash:** transformación irreversible (sin llave, salvo HMAC) para integridad y contraseñas. **No se descifra**. La derivación PBKDF2/Argon2id convierte una frase en una llave.
- **Base64:** codificación reversible sin llave; **no es cifrado ni hash**.

La pantalla 5.4 es la única donde se muestran contraseñas en claro, y lo hace con un aviso visible. El login real nunca almacena contraseñas reversibles.

---

## 10. Equivalencias Java ↔ PHP

| Java (`javax.crypto` / `java.security`) | PHP equivalente |
|---|---|
| `Cipher` | `openssl_encrypt` / `openssl_decrypt`, `sodium_crypto_*` |
| `KeyGenerator` / `SecretKey` | `random_bytes()`, `sodium_crypto_secretbox_keygen()` |
| `SecretKeySpec` / `IvParameterSpec` | Llave + IV en `openssl_encrypt` |
| `KeyPairGenerator` / `KeyPair` | `openssl_pkey_new()`, `sodium_crypto_box_keypair()` |
| `PublicKey` / `PrivateKey` | `openssl_pkey_get_public()` / `openssl_pkey_get_private()` |
| `Signature` | `openssl_sign()` / `openssl_verify()` |
| `MessageDigest` | `hash()` / `hash_algos()` |
| `Mac` | `hash_hmac()` |
| `SecretKeyFactory` (PBKDF2) | `hash_pbkdf2()`, `sodium_crypto_pwhash()` |
| `SecureRandom` | `random_bytes()` / `random_int()` |
| `Base64` | `base64_encode()` / `base64_decode()` |

---

## 11. Pruebas

```bash
make test
# o
docker compose exec app php artisan test
```

La suite incluye:

- Roundtrip de todos los cifrados simétricos.
- Dos cifrados del mismo texto producen IV y ciphertext distintos.
- Detección de manipulación en GCM, ChaCha20-Poly1305 y XSalsa20.
- RSA-OAEP, sealed box, híbrido, RSA-SHA256 y Ed25519.
- Vectores conocidos de SHA-256 y HMAC-SHA256.
- PBKDF2 determinista por salt y Argon2id.
- Registro, login por username, verificación, recuperación y perfil.
- Pantalla 5.4: cifrado, descifrado, modo invertido, exportación y propiedad de los registros.
- Que los logs no contengan textos claros.
- Admin, 403 personalizado, cabeceras de seguridad y configuración de sesión.

Las pruebas usan SQLite en memoria y fuerzan su propia configuración en `phpunit.xml` para no tocar PostgreSQL. La aplicación en ejecución usa PostgreSQL 18.

---

## 12. Guía de entrega y capturas

### Captura 1 — formulario con texto en claro

1. Ejecuta `docker compose up -d --build` y abre <http://localhost:8080>.
2. Inicia sesión como `demo` / `Demo1234!`.
3. Ve a **Laboratorio → Formulario de autenticación cifrado** (`/lab/autenticacion-cifrada`).
4. Escribe, por ejemplo:
   - Usuario: `ana.estudiante`
   - Contraseña: `ClaveDemo#2026`
5. Deja los algoritmos por defecto: **AES-256-GCM** y **RSA-OAEP 2048**.
6. Toma la captura **antes de enviar**: se ven los campos en claro y el aviso educativo.

Archivo sugerido: `captura-1-formulario.png`.

### Captura 2 — texto en claro vs texto cifrado

1. Pulsa **“Guardar y cifrar”**.
2. En la tabla verás: usuario claro, usuario cifrado, password claro, password cifrado, algoritmos y fecha.
3. Pulsa **“Descifrar”** en la fila para mostrar el panel de reversibilidad.
4. Toma la captura con la tabla y, si es posible, el panel de descifrado.

Archivo sugerido: `captura-2-resultado.png`.

### Evidencia opcional de cifrado en reposo

```bash
make db
```

Ejecuta la consulta de la sección 8 y captura cómo `email`, `username_plain`, `password_plain` y `private_key_encrypted` aparecen cifrados.

> No uses contraseñas reales en las capturas. Los valores de ejemplo son de demostración.

### Entrega

Comprime el proyecto **sin** `src/vendor`, `src/node_modules`, `src/.env`, `src/storage/logs` ni `src/public/build`. Incluye `docker-compose.yml`, `README.md`, `.env.example`, `Makefile`, `docker/`, `src/composer.json`, `src/composer.lock`, `src/package.json`, `src/package-lock.json` y el código de `src/app`, `src/database`, `src/resources`, `src/routes`, `src/tests` y `src/config`.

---

## 13. Llaves y rotación

Las claves **no** se versionan. En desarrollo el entrypoint las genera si están vacías. En producción deben existir antes del primer arranque y respaldarse.

| Variable | Uso | Rotación |
|---|---|---|
| `APP_KEY` | Cifrado de casts de Eloquent (email, teléfono, campos claros de la demo), sesiones y cookies | Cambiarla invalida esos datos; hay que descifrarlos con la clave anterior y volver a cifrarlos. No hagas rotación sin un plan de re-cifrado y respaldo. |
| `CRYPTO_MASTER_KEY` | Cifra llaves privadas y llaves simétricas de la demo | Cambiarla inutiliza los datos existentes; se debe re-cifrar con la llave anterior antes de retirarla. |
| `CRYPTO_BLIND_INDEX_KEY` | `email_hash` y tokens de recuperación | Después de cambiarla ejecuta `php artisan crypto:rehash-emails` para recalcular índices. Los tokens de recuperación se invalidan automáticamente. |

Comandos útiles:

```bash
docker compose exec app php artisan crypto:rehash-emails
```

Limitación educativa: el proyecto no incluye una herramienta automática de re-cifrado para rotar `APP_KEY` o `CRYPTO_MASTER_KEY`; en un sistema real se usaría un KMS/HSM y un procedimiento de rotación con ventana de mantenimiento.

---

## 14. Seguridad y limitaciones

- Proyecto **educativo**. La pantalla 5.4 muestra contraseñas en claro a propósito; nunca debe replicarse en producción.
- El login real usa Argon2id y no almacena contraseñas reversibles.
- CSRF activo, escape de salida con Blade, validación con FormRequest, rate limiting en login, registro, recuperación y laboratorio.
- Sesiones cifradas, cookies `httponly`, `samesite=lax`; en producción activa `SESSION_SECURE_COOKIE=true` y HTTPS.
- `URL::forceScheme('https')` se aplica en `APP_ENV=production`.
- Cabeceras: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`; HSTS en producción sobre HTTPS.
- Nginx no registra la query string en el access log para evitar emails/tokens en URLs.
- No se registran textos claros, contraseñas ni llaves privadas en `crypto_operations`, `audit_logs` ni logs de aplicación.
- No hay CSP estricta para no romper Alpine.js; es una mejora pendiente.
- No se implementó la passphrase opcional por usuario para llaves privadas; se usa la llave maestra de la aplicación.
- Las pruebas automatizadas usan SQLite en memoria; PostgreSQL se valida con la aplicación en ejecución y las consultas directas.
- La exportación es JSON; no se incluye PDF.
- El proyecto no sustituye una auditoría criptográfica profesional.

### Checklist para producción

1. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`.
2. Contraseña de PostgreSQL robusta y distinta de la de ejemplo.
3. `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `HASH_DRIVER=argon2id`.
4. `AUTO_SEED=false` en el `.env` raíz y eliminar/rotar credenciales demo.
5. Respaldar `APP_KEY`, `CRYPTO_MASTER_KEY` y `CRYPTO_BLIND_INDEX_KEY` en un gestor de secretos.
6. Configurar HTTPS, proxy de confianza y HSTS.
7. Ejecutar `php artisan config:cache route:cache view:cache`.
8. No exponer Adminer ni Mailpit públicamente.
9. Revisar logs y activar alertas de accesos administrativos.

---

## 15. Estructura del proyecto

```text
.
├── docker/
│   ├── php/{Dockerfile,entrypoint.sh,php.ini,php-fpm.conf}
│   └── nginx/default.conf
├── docker-compose.yml
├── Makefile
├── .env.example
├── README.md
├── AGENTS.md
└── src/
    ├── app/
    │   ├── Auth/                 # provider y token repository con hash ciego
    │   ├── Casts/                # cifrado con llave maestra
    │   ├── Http/Controllers/     # Crypto, Admin, auth, páginas
    │   ├── Http/Requests/        # validación con FormRequest
    │   ├── Models/
    │   ├── Services/Crypto/      # contratos, algoritmos, registries, demo
    │   └── Services/Audit/
    ├── database/migrations|seeders|factories
    ├── resources/views/crypto|admin|pages|errors|auth|profile
    ├── resources/js/crypto.js
    ├── routes/web.php|auth.php
    └── tests/Unit|Feature
```

---

## 16. Referencias

- Laravel Encryption: <https://laravel.com/docs/13.x/encryption>
- Laravel Hashing: <https://laravel.com/docs/13.x/hashing>
- PHP OpenSSL: <https://www.php.net/manual/es/book.openssl.php>
- PHP Sodium: <https://www.php.net/manual/es/book.sodium.php>
- OWASP Cryptographic Storage Cheat Sheet: <https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html>
- Videos del módulo:
  - <https://youtu.be/G54d8CG9Zuc>
  - <https://youtu.be/qLJaG4i2bS8>

---

**Advertencia final:** este repositorio es una entrega académica. Mostrar contraseñas en claro solo es aceptable en la pantalla de demostración solicitada por la actividad. En un sistema real, las contraseñas se almacenan únicamente como hashes lentos con salt (Argon2id/bcrypt), nunca con cifrado reversible.
