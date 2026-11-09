const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(data),
    });

    let payload = null;

    try {
        payload = await response.json();
    } catch {
        throw new Error(`La respuesta del servidor no es JSON (HTTP ${response.status}).`);
    }

    if (!response.ok) {
        if (payload?.errors) {
            const first = Object.values(payload.errors).flat()[0];
            throw new Error(first ?? payload.message ?? 'Datos inválidos.');
        }

        throw new Error(payload?.message ?? `Error HTTP ${response.status}.`);
    }

    return payload;
}

function copyText(value) {
    return navigator.clipboard.writeText(String(value ?? ''));
}

function formatBytes(bytes) {
    if (bytes === null || bytes === undefined) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    return `${(bytes / 1024).toFixed(1)} KB`;
}

function downloadText(filename, text, mime = 'text/plain') {
    const blob = new Blob([text], { type: `${mime};charset=utf-8` });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
}

function downloadJson(filename, data) {
    downloadText(filename, JSON.stringify(data, null, 2), 'application/json');
}

function downloadBase64(filename, base64, mime = 'application/octet-stream') {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }

    const blob = new Blob([bytes], { type: mime });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
}

function downloadCsv(filename, rows) {
    const escape = (value) => {
        const text = String(value ?? '');
        return `"${text.replaceAll('"', '""')}"`;
    };

    downloadText(filename, rows.map((row) => row.map(escape).join(',')).join('\n'), 'text/csv');
}

async function postForm(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: formData,
    });

    let payload = null;

    try {
        payload = await response.json();
    } catch {
        throw new Error(`La respuesta del servidor no es JSON (HTTP ${response.status}).`);
    }

    if (!response.ok) {
        if (payload?.errors) {
            throw new Error(Object.values(payload.errors).flat()[0] ?? payload.message);
        }

        throw new Error(payload?.message ?? `Error HTTP ${response.status}.`);
    }

    return payload;
}

async function getJson(url) {
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });
    const payload = await response.json();

    if (!response.ok) {
        throw new Error(payload?.message ?? `Error HTTP ${response.status}.`);
    }

    return payload;
}

window.CryptoLab = {
    post: postJson,
    postForm,
    get: getJson,
    copy: copyText,
    bytes: formatBytes,
    downloadText,
    downloadJson,
    downloadBase64,
    downloadCsv,
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('symmetricLab', (config) => ({
        catalog: config.catalog ?? [],
        routes: config.routes ?? {},
        algorithm: config.defaultAlgorithm ?? 'aes-256-gcm',
        plaintext: 'Mensaje secreto de ejemplo',
        key: '',
        keyHex: '',
        keyBytes: 0,
        payload: '',
        result: null,
        error: '',
        loading: false,
        copied: '',
        session: [],
        kdfMethod: 'pbkdf2',
        passphrase: 'frase secreta de ejemplo',
        salt: '',
        iterations: 200000,
        kdfBytes: 32,
        derivation: null,

        get selected() {
            return this.catalog.find((item) => item.id === this.algorithm) ?? this.catalog[0] ?? null;
        },

        get resultJson() {
            return this.result ? JSON.stringify(this.result, null, 2) : '';
        },

        init() {
            this.generateKey();
        },

        async onAlgorithmChange() {
            this.result = null;
            this.derivation = null;
            await this.generateKey();
        },

        async generateKey() {
            this.loading = true;
            this.error = '';

            try {
                const data = await CryptoLab.post(this.routes.keygen, { algorithm: this.algorithm });
                this.key = data.key;
                this.keyHex = data.key_hex;
                this.keyBytes = data.key_bytes;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async encrypt() {
            this.loading = true;
            this.error = '';

            try {
                const data = await CryptoLab.post(this.routes.encrypt, {
                    algorithm: this.algorithm,
                    plaintext: this.plaintext,
                    key: this.key,
                });
                this.result = data;
                this.payload = data.envelope_json;
                this.addSession('cifrar', data);
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async decrypt() {
            this.loading = true;
            this.error = '';

            try {
                const data = await CryptoLab.post(this.routes.decrypt, {
                    algorithm: this.algorithm,
                    key: this.key,
                    payload: this.payload,
                });
                this.result = data;
                this.addSession('descifrar', data);
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async demonstrateIv() {
            this.loading = true;
            this.error = '';

            try {
                const data = await CryptoLab.post(this.routes.iv, {
                    algorithm: this.algorithm,
                    plaintext: this.plaintext,
                    key: this.key,
                });
                this.result = data;
                this.addSession('iv-demo', data);
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async deriveKey() {
            this.loading = true;
            this.error = '';

            try {
                const data = await CryptoLab.post(this.routes.derive, {
                    method: this.kdfMethod,
                    passphrase: this.passphrase,
                    salt: this.salt || null,
                    iterations: this.iterations,
                    bytes: this.kdfBytes,
                });
                this.derivation = data.derivation;
                this.key = data.derivation.key;
                this.keyHex = data.derivation.key_hex;
                this.keyBytes = data.derivation.bytes;
                this.salt = data.derivation.salt;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        addSession(operation, data) {
            this.session.unshift({ operation, at: new Date().toISOString(), data });
        },

        exportSession(format) {
            const rows = this.session.map((item) => ({
                operation: item.operation,
                at: item.at,
                algorithm: item.data.algorithm ?? item.data.label ?? '',
                plaintext_bytes: item.data.sizes?.plaintext ?? '',
                ciphertext_bytes: item.data.sizes?.ciphertext ?? '',
                result: JSON.stringify(item.data),
            }));

            if (format === 'csv') {
                window.CryptoLab.downloadCsv('criptolab-simetrico.csv', [
                    ['operacion', 'fecha', 'algoritmo', 'bytes_claro', 'bytes_cifrado', 'resultado'],
                    ...rows.map((row) => [row.operation, row.at, row.algorithm, row.plaintext_bytes, row.ciphertext_bytes, row.result]),
                ]);
            } else {
                window.CryptoLab.downloadJson('criptolab-simetrico.json', { session: this.session });
            }
        },

        async tamperAndDecrypt() {
            this.error = '';
            this.loading = true;

            try {
                if (!this.selected?.metadata?.authenticated) {
                    throw new Error('Este algoritmo no autentica; la manipulación no se puede detectar. Prueba AES-GCM, ChaCha20 o XSalsa20.');
                }

                if (!this.payload) {
                    throw new Error('Primero cifra un texto para tener un sobre que manipular.');
                }

                const envelope = JSON.parse(this.payload);
                const raw = atob(envelope.ciphertext ?? '');
                const bytes = new Uint8Array(raw.length);

                for (let i = 0; i < raw.length; i++) {
                    bytes[i] = raw.charCodeAt(i);
                }

                bytes[0] ^= 0x01;
                envelope.ciphertext = btoa(String.fromCharCode(...bytes));

                try {
                    await CryptoLab.post(this.routes.decrypt, {
                        algorithm: this.algorithm,
                        key: this.key,
                        payload: JSON.stringify(envelope),
                    });
                    this.error = 'El descifrado aceptó el ciphertext manipulado: esto sería un fallo.';
                } catch (error) {
                    this.error = 'Manipulación detectada correctamente: ' + error.message;
                }
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async copy(value, label = 'valor') {
            try {
                await CryptoLab.copy(value);
                this.copied = label;
                setTimeout(() => {
                    if (this.copied === label) {
                        this.copied = '';
                    }
                }, 1500);
            } catch {
                this.error = 'No se pudo copiar al portapapeles.';
            }
        },
    }));

    window.Alpine.data('asymmetricLab', (config) => ({
        cipherCatalog: config.cipherCatalog ?? [],
        signerCatalog: config.signerCatalog ?? [],
        routes: config.routes ?? {},
        keyAlgorithm: config.defaultCipher ?? 'rsa-oaep-2048',
        keyName: '',
        saveKey: false,
        keyPassphrase: '',
        pair: { public_key: '', private_key: '', fingerprint: '', algorithm: '', label: '' },
        keyError: '',
        keyLoading: false,

        encryptAlgorithm: config.defaultCipher ?? 'rsa-oaep-2048',
        plaintext: 'Contraseña de ejemplo',
        publicKey: '',
        privateKey: '',
        decryptPassphrase: '',
        payload: '',
        encrypted: null,
        decrypted: '',
        operationError: '',
        operationLoading: false,
        session: [],

        signerAlgorithm: config.defaultSigner ?? 'rsa-sha256-2048',
        signerPublic: '',
        signerPrivate: '',
        signature: '',
        message: 'Documento que quiero firmar',
        verifySignature: '',
        verifyPublic: '',
        verifyMessage: 'Documento que quiero firmar',
        verifyResult: null,
        signerError: '',
        signerLoading: false,

        get selectedCipher() {
            return this.cipherCatalog.find((item) => item.id === this.encryptAlgorithm) ?? this.cipherCatalog[0] ?? null;
        },

        get maxBytes() {
            return this.selectedCipher?.metadata?.max_plaintext_bytes ?? null;
        },

        get pairJson() {
            return this.pair ? JSON.stringify(this.pair, null, 2) : '';
        },

        get encryptedJson() {
            return this.encrypted ? JSON.stringify(this.encrypted, null, 2) : '';
        },

        async generateKeypair() {
            this.keyLoading = true;
            this.keyError = '';

            try {
                const data = await CryptoLab.post(this.routes.keygen, {
                    algorithm: this.keyAlgorithm,
                    name: this.keyName || null,
                    save: this.saveKey,
                    passphrase: this.keyPassphrase || null,
                });
                this.pair = data;
                this.publicKey = data.public_key;
                this.privateKey = data.private_key;
                this.encryptAlgorithm = data.algorithm;
            } catch (error) {
                this.keyError = error.message;
            } finally {
                this.keyLoading = false;
            }
        },

        async generateSignerKeypair() {
            this.signerLoading = true;
            this.signerError = '';

            try {
                const data = await CryptoLab.post(this.routes.signerKeygen, {
                    algorithm: this.signerAlgorithm,
                    name: this.keyName || null,
                    save: this.saveKey,
                });
                this.signerPublic = data.public_key;
                this.signerPrivate = data.private_key;
                this.verifyPublic = data.public_key;
            } catch (error) {
                this.signerError = error.message;
            } finally {
                this.signerLoading = false;
            }
        },

        async encrypt() {
            this.operationLoading = true;
            this.operationError = '';

            try {
                const data = await CryptoLab.post(this.routes.encrypt, {
                    algorithm: this.encryptAlgorithm,
                    plaintext: this.plaintext,
                    public_key: this.publicKey,
                });
                this.encrypted = data;
                this.payload = data.envelope_json;
                this.addSession('cifrar', data);
            } catch (error) {
                this.operationError = error.message;
            } finally {
                this.operationLoading = false;
            }
        },

        async decrypt() {
            this.operationLoading = true;
            this.operationError = '';

            try {
                const data = await CryptoLab.post(this.routes.decrypt, {
                    algorithm: this.encryptAlgorithm,
                    payload: this.payload,
                    private_key: this.privateKey,
                    passphrase: this.decryptPassphrase || null,
                });
                this.decrypted = data.plaintext;
                this.addSession('descifrar', data);
            } catch (error) {
                this.operationError = error.message;
            } finally {
                this.operationLoading = false;
            }
        },

        async sign() {
            this.signerLoading = true;
            this.signerError = '';

            try {
                const data = await CryptoLab.post(this.routes.sign, {
                    algorithm: this.signerAlgorithm,
                    message: this.message,
                    private_key: this.signerPrivate,
                    passphrase: this.keyPassphrase || null,
                });
                this.signature = data.signature;
                this.verifySignature = data.signature;
                this.verifyMessage = this.message;
                this.addSession('firmar', data);
            } catch (error) {
                this.signerError = error.message;
            } finally {
                this.signerLoading = false;
            }
        },

        async verify() {
            this.signerLoading = true;
            this.signerError = '';

            try {
                this.verifyResult = await CryptoLab.post(this.routes.verify, {
                    algorithm: this.signerAlgorithm,
                    message: this.verifyMessage,
                    signature: this.verifySignature,
                    public_key: this.verifyPublic,
                });
                this.addSession('verificar', this.verifyResult);
            } catch (error) {
                this.signerError = error.message;
            } finally {
                this.signerLoading = false;
            }
        },

        addSession(operation, data) {
            this.session.unshift({ operation, at: new Date().toISOString(), data });
        },

        exportSession(format) {
            const rows = this.session.map((item) => ({
                operation: item.operation,
                at: item.at,
                algorithm: item.data.algorithm ?? '',
                result: JSON.stringify(item.data),
            }));

            if (format === 'csv') {
                window.CryptoLab.downloadCsv('criptolab-asimetrico.csv', [
                    ['operacion', 'fecha', 'algoritmo', 'resultado'],
                    ...rows.map((row) => [row.operation, row.at, row.algorithm, row.result]),
                ]);
            } else {
                window.CryptoLab.downloadJson('criptolab-asimetrico.json', { session: this.session });
            }
        },

        async tamperAndDecrypt() {
            this.operationError = '';
            this.operationLoading = true;

            try {
                if (!this.payload || !this.privateKey) {
                    throw new Error('Primero cifra un texto y conserva la llave privada para poder probar la manipulación.');
                }

                const envelope = JSON.parse(this.payload);
                const raw = atob(envelope.ciphertext ?? '');
                const bytes = new Uint8Array(raw.length);

                for (let i = 0; i < raw.length; i++) {
                    bytes[i] = raw.charCodeAt(i);
                }

                bytes[0] ^= 0x01;
                envelope.ciphertext = btoa(String.fromCharCode(...bytes));

                try {
                    await CryptoLab.post(this.routes.decrypt, {
                        algorithm: this.encryptAlgorithm,
                        payload: JSON.stringify(envelope),
                        private_key: this.privateKey,
                        passphrase: this.decryptPassphrase || null,
                    });
                    this.operationError = 'El descifrado aceptó el ciphertext manipulado: esto sería un fallo.';
                } catch (error) {
                    this.operationError = 'Manipulación detectada correctamente: ' + error.message;
                }
            } catch (error) {
                this.operationError = error.message;
            } finally {
                this.operationLoading = false;
            }
        },

        async copy(value, label = 'valor') {
            try {
                await CryptoLab.copy(value);
                this.copied = label;
                setTimeout(() => {
                    if (this.copied === label) {
                        this.copied = '';
                    }
                }, 1500);
            } catch {
                this.operationError = 'No se pudo copiar al portapapeles.';
            }
        },

        download(filename, content) {
            window.CryptoLab.downloadText(filename, content);
        },

        copied: '',
    }));

    window.Alpine.data('hashLab', (config) => ({
        algorithms: config.algorithms ?? {},
        routes: config.routes ?? {},
        digestAlgorithm: 'sha256',
        text: 'Hola CriptoLab',
        digestResult: null,
        hmacText: 'Mensaje autenticado',
        hmacKey: 'llave secreta',
        hmacExpected: '',
        hmacResult: null,
        password: 'Password123!',
        passwordAlgorithm: 'argon2id',
        passwordResult: null,
        verifyPassword: 'Password123!',
        verifyHash: '',
        verifyResult: null,
        error: '',
        loading: false,
        session: [],

        async digest() {
            this.loading = true;
            this.error = '';

            try {
                const data = await CryptoLab.post(this.routes.digest, {
                    algorithm: this.digestAlgorithm,
                    text: this.text,
                });
                this.digestResult = data.result;
                this.session.unshift({ operation: 'digest', at: new Date().toISOString(), data: data.result });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async hmac() {
            this.loading = true;
            this.error = '';

            try {
                this.hmacResult = await CryptoLab.post(this.routes.hmac, {
                    text: this.hmacText,
                    key: this.hmacKey,
                    expected: this.hmacExpected || null,
                });
                this.session.unshift({ operation: 'hmac', at: new Date().toISOString(), data: this.hmacResult });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async passwordHash() {
            this.loading = true;
            this.error = '';

            try {
                const data = await CryptoLab.post(this.routes.password, {
                    algorithm: this.passwordAlgorithm,
                    password: this.password,
                });
                this.passwordResult = data.result;
                this.verifyHash = data.result.hash;
                this.session.unshift({ operation: 'password_hash', at: new Date().toISOString(), data: data.result });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async verifyPasswordHash() {
            this.loading = true;
            this.error = '';

            try {
                this.verifyResult = await CryptoLab.post(this.routes.passwordVerify, {
                    password: this.verifyPassword,
                    hash: this.verifyHash,
                });
                this.session.unshift({ operation: 'password_verify', at: new Date().toISOString(), data: this.verifyResult });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        exportSession(format) {
            const rows = this.session.map((item) => ({
                operation: item.operation,
                at: item.at,
                result: JSON.stringify(item.data),
            }));

            if (format === 'csv') {
                window.CryptoLab.downloadCsv('criptolab-hash.csv', [
                    ['operacion', 'fecha', 'resultado'],
                    ...rows.map((row) => [row.operation, row.at, row.result]),
                ]);
            } else {
                window.CryptoLab.downloadJson('criptolab-hash.json', { session: this.session });
            }
        },

        async copy(value, label = 'valor') {
            try {
                await CryptoLab.copy(value);
                this.copied = label;
                setTimeout(() => {
                    if (this.copied === label) {
                        this.copied = '';
                    }
                }, 1500);
            } catch {
                this.error = 'No se pudo copiar al portapapeles.';
            }
        },

        copied: '',
    }));

    window.Alpine.data('authDemoPage', (config) => ({
        decryptTemplate: config.decryptTemplate ?? '',
        tamperTemplate: config.tamperTemplate ?? '',
        rawTemplate: config.rawTemplate ?? '',
        decryptingId: null,
        decrypted: null,
        raw: null,
        error: '',
        copied: '',
        statusMessage: '',

        async decrypt(id) {
            this.decryptingId = id;
            this.decrypted = null;
            this.error = '';
            this.statusMessage = '';

            try {
                const url = this.decryptTemplate.replace('__ID__', String(id));
                this.decrypted = await CryptoLab.post(url, {});
            } catch (error) {
                this.error = error.message;
            } finally {
                this.decryptingId = null;
            }
        },

        async showRaw(id) {
            this.error = '';
            this.raw = null;

            try {
                const url = this.rawTemplate.replace('__ID__', String(id));
                const data = await CryptoLab.get(url);
                this.raw = data.row;
            } catch (error) {
                this.error = error.message;
            }
        },

        async tamper(id) {
            this.error = '';
            this.statusMessage = '';

            try {
                const url = this.tamperTemplate.replace('__ID__', String(id));
                await CryptoLab.post(url, {});
                this.error = 'El descifrado aceptó el ciphertext manipulado: esto sería un fallo.';
            } catch (error) {
                this.error = 'Manipulación detectada correctamente: ' + error.message;
            }
        },

        async copy(value, label = 'valor') {
            try {
                await CryptoLab.copy(value);
                this.copied = label;
                setTimeout(() => {
                    if (this.copied === label) {
                        this.copied = '';
                    }
                }, 1500);
            } catch {
                this.error = 'No se pudo copiar al portapapeles.';
            }
        },
    }));
});
