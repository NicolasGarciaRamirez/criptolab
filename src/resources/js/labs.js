document.addEventListener('alpine:init', () => {
    window.Alpine.data('fileLab', (config) => ({
        catalog: config.catalog ?? [],
        routes: config.routes ?? {},
        maxBytes: config.maxBytes ?? 5242880,
        algorithm: config.catalog?.[0]?.id ?? 'aes-256-gcm',
        key: '',
        keyHex: '',
        file: null,
        cryptFile: null,
        result: null,
        decrypted: null,
        error: '',
        loading: false,

        get selected() {
            return this.catalog.find((item) => item.id === this.algorithm) ?? this.catalog[0] ?? null;
        },

        get maxLabel() {
            return `${Math.round(this.maxBytes / 1024 / 1024 * 100) / 100} MB`;
        },

        init() {
            this.generateKey();
        },

        async generateKey() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.keygen, { algorithm: this.algorithm });
                this.key = data.key;
                this.keyHex = data.key_hex;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        pickFile(event) {
            this.file = event.target.files?.[0] ?? null;
            this.result = null;
        },

        pickCryptFile(event) {
            this.cryptFile = event.target.files?.[0] ?? null;
            this.decrypted = null;
        },

        async encrypt() {
            this.loading = true;
            this.error = '';
            this.result = null;

            try {
                if (!this.file) {
                    throw new Error('Selecciona un archivo para cifrar.');
                }

                const data = new FormData();
                data.append('file', this.file);
                data.append('algorithm', this.algorithm);
                data.append('key', this.key);

                const response = await window.CryptoLab.postForm(this.routes.encrypt, data);
                this.result = response;
                window.CryptoLab.downloadText(`${response.original_name}.crypt`, response.envelope_json, 'application/json');
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async decrypt() {
            this.loading = true;
            this.error = '';
            this.decrypted = null;

            try {
                if (!this.cryptFile) {
                    throw new Error('Selecciona un archivo .crypt para descifrar.');
                }

                const data = new FormData();
                data.append('file', this.cryptFile);
                data.append('algorithm', this.algorithm);
                data.append('key', this.key);

                const response = await window.CryptoLab.postForm(this.routes.decrypt, data);
                this.decrypted = response;
                window.CryptoLab.downloadBase64(response.name, response.contents_base64, response.mime);
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        downloadResult() {
            if (this.result) {
                window.CryptoLab.downloadText(`${this.result.original_name}.crypt`, this.result.envelope_json, 'application/json');
            }
        },
    }));

    window.Alpine.data('aesModesLab', (config) => ({
        routes: config.routes ?? {},
        plaintext: 'BloqueA123456789BlqueA123456789BloqueA123456789',
        comparison: null,
        algorithm: 'aes-256-gcm',
        first: 'texto uno',
        second: 'texto dos',
        nonce: null,
        error: '',
        loading: false,

        async compare() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.compare, { plaintext: this.plaintext });
                this.comparison = data.result;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async demonstrateNonceReuse() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.nonce, {
                    algorithm: this.algorithm,
                    first: this.first,
                    second: this.second,
                });
                this.nonce = data.result;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('aadLab', (config) => ({
        routes: config.routes ?? {},
        key: '',
        aad: 'usuario=42;contexto=login',
        plaintext: 'Mensaje autenticado con datos asociados',
        payload: '',
        result: null,
        decrypted: '',
        error: '',
        loading: false,

        init() {
            this.generateKey();
        },

        async generateKey() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.keygen, { algorithm: 'aes-256-gcm' });
                this.key = data.key;
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
                const data = await window.CryptoLab.post(this.routes.encrypt, {
                    plaintext: this.plaintext,
                    key: this.key,
                    aad: this.aad,
                });
                this.result = data;
                this.payload = data.envelope_json;
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
                const data = await window.CryptoLab.post(this.routes.decrypt, {
                    payload: this.payload,
                    key: this.key,
                    aad: this.aad,
                });
                this.decrypted = data.plaintext;
            } catch (error) {
                this.error = error.message;
                this.decrypted = '';
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('x25519Lab', (config) => ({
        routes: config.routes ?? {},
        alice: { public_key: '', private_key: '' },
        bob: { public_key: '', private_key: '' },
        message: 'Mensaje cifrado con la llave derivada',
        result: null,
        error: '',
        loading: false,

        async generate() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.generate, {});
                this.alice = data.alice;
                this.bob = data.bob;
                this.result = null;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async shared() {
            this.loading = true;
            this.error = '';

            try {
                if (!this.alice.public_key || !this.bob.public_key) {
                    throw new Error('Primero genera los pares de Alice y Bob.');
                }

                this.result = await window.CryptoLab.post(this.routes.shared, {
                    alice_private_key: this.alice.private_key,
                    alice_public_key: this.alice.public_key,
                    bob_private_key: this.bob.private_key,
                    bob_public_key: this.bob.public_key,
                    message: this.message,
                });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('signEncryptLab', (config) => ({
        routes: config.routes ?? {},
        signerCatalog: config.signerCatalog ?? [],
        cipherCatalog: config.cipherCatalog ?? [],
        signing: { public_key: '', private_key: '', algorithm: '', label: '' },
        encryption: { public_key: '', private_key: '', algorithm: '', label: '' },
        message: 'Mensaje firmado y luego cifrado',
        payload: '',
        result: null,
        error: '',
        loading: false,

        async generate() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.keys, {});
                this.signing = data.signing;
                this.encryption = data.encryption;
                this.result = null;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async signAndEncrypt() {
            this.loading = true;
            this.error = '';

            try {
                if (!this.signing.public_key || !this.encryption.public_key) {
                    throw new Error('Primero genera los pares de firma y cifrado.');
                }

                const data = await window.CryptoLab.post(this.routes.encrypt, {
                    message: this.message,
                    signer_algorithm: this.signing.algorithm,
                    cipher_algorithm: this.encryption.algorithm,
                    signing_private_key: this.signing.private_key,
                    signing_public_key: this.signing.public_key,
                    encryption_public_key: this.encryption.public_key,
                });
                this.result = { envelope: data.envelope };
                this.payload = data.envelope_json;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async decryptAndVerify() {
            this.loading = true;
            this.error = '';

            try {
                if (!this.encryption.private_key || !this.payload) {
                    throw new Error('Falta la llave privada de cifrado o el paquete cifrado.');
                }

                const data = await window.CryptoLab.post(this.routes.decrypt, {
                    payload: this.payload,
                    cipher_algorithm: this.encryption.algorithm,
                    encryption_private_key: this.encryption.private_key,
                });
                this.result = data;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('certificateLab', (config) => ({
        routes: config.routes ?? {},
        commonName: 'criptolab.local',
        organization: 'CriptoLab',
        country: 'CO',
        days: 365,
        certificate: null,
        inspected: null,
        inspectPem: '',
        error: '',
        loading: false,

        async generate() {
            this.loading = true;
            this.error = '';

            try {
                this.certificate = await window.CryptoLab.post(this.routes.generate, {
                    common_name: this.commonName,
                    organization: this.organization,
                    country: this.country,
                    days: this.days,
                });
                this.inspectPem = this.certificate.certificate;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        async inspect() {
            this.loading = true;
            this.error = '';

            try {
                this.inspected = await window.CryptoLab.post(this.routes.inspect, { certificate: this.inspectPem });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        downloadPem(filename, content) {
            window.CryptoLab.downloadText(filename, content, 'application/x-pem-file');
        },
    }));

    window.Alpine.data('keyImportLab', (config) => ({
        routes: config.routes ?? {},
        algorithms: config.algorithms ?? [],
        publicKey: '',
        privateKey: '',
        algorithm: config.algorithms?.[0]?.id ?? 'rsa-oaep-2048',
        name: 'Llave importada',
        save: false,
        result: null,
        error: '',
        loading: false,

        async inspect() {
            this.loading = true;
            this.error = '';

            try {
                this.result = await window.CryptoLab.post(this.routes.inspect, {
                    public_key: this.publicKey,
                    private_key: this.privateKey || null,
                    algorithm: this.algorithm,
                    name: this.name,
                    save: this.save,
                });
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('timingLab', (config) => ({
        routes: config.routes ?? {},
        first: 'abcdefghijklmnopqrstuvwxyz0123456789',
        second: 'abcdefghijklmnopqrstuvwxyz0123456780',
        iterations: 50000,
        result: null,
        error: '',
        loading: false,

        async compare() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.compare, {
                    first: this.first,
                    second: this.second,
                    iterations: this.iterations,
                });
                this.result = data.result;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('rainbowLab', (config) => ({
        routes: config.routes ?? {},
        dictionary: config.dictionary ?? [],
        password: 'admin',
        result: null,
        error: '',
        loading: false,

        async consult() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.consult, { password: this.password });
                this.result = data;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('benchmarkLab', (config) => ({
        routes: config.routes ?? {},
        result: null,
        error: '',
        loading: false,

        async run() {
            this.loading = true;
            this.error = '';
            this.result = null;

            try {
                this.result = await window.CryptoLab.post(this.routes.run, {});
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    }));

    window.Alpine.data('quizLab', (config) => ({
        module: config.module ?? 'simetrico',
        modules: config.modules ?? {},
        questions: config.questions ?? [],
        routes: config.routes ?? {},
        answers: {},
        result: null,
        error: '',
        loading: false,

        async evaluate() {
            this.loading = true;
            this.error = '';

            try {
                const data = await window.CryptoLab.post(this.routes.evaluate, {
                    module: this.module,
                    answers: this.answers,
                });
                this.result = data.result;
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },

        reset() {
            this.answers = {};
            this.result = null;
            this.error = '';
        },
    }));
});
