<?php

declare(strict_types=1);

namespace App\Services\Crypto;

final class QuizService
{
    /**
     * @var array<string, array{label: string, questions: list<array{id: string, question: string, options: array<string, string>, answer: string, explanation: string}>}>
     */
    private const BANK = [
        'simetrico' => [
            'label' => 'Cifrado simétrico',
            'questions' => [
                [
                    'id' => 's1',
                    'question' => '¿Por qué no se debe reutilizar el IV/nonce?',
                    'options' => ['a' => 'Porque el ciphertext se hace más largo', 'b' => 'Porque puede filtrar relaciones entre textos claros y romper la autenticación', 'c' => 'Porque la llave cambia automáticamente'],
                    'answer' => 'b',
                    'explanation' => 'Con el mismo nonce, el keystream se repite; XOR de ciphertexts revela XOR de textos claros.',
                ],
                [
                    'id' => 's2',
                    'question' => '¿Qué modo detecta manipulación del ciphertext?',
                    'options' => ['a' => 'AES-CBC sin HMAC', 'b' => 'AES-CTR', 'c' => 'AES-GCM'],
                    'answer' => 'c',
                    'explanation' => 'GCM incluye un tag de autenticación que falla si el ciphertext o el tag cambian.',
                ],
                [
                    'id' => 's3',
                    'question' => '¿Por qué ECB es peligroso?',
                    'options' => ['a' => 'Porque no usa llave', 'b' => 'Porque revela patrones repetidos en el texto claro', 'c' => 'Porque es demasiado lento'],
                    'answer' => 'b',
                    'explanation' => 'Los bloques idénticos producen bloques cifrados idénticos.',
                ],
                [
                    'id' => 's4',
                    'question' => 'En AES-256-GCM, ¿qué tamaño tiene la llave?',
                    'options' => ['a' => '16 bytes', 'b' => '24 bytes', 'c' => '32 bytes'],
                    'answer' => 'c',
                    'explanation' => 'AES-256 usa llaves de 256 bits, es decir, 32 bytes.',
                ],
                [
                    'id' => 's5',
                    'question' => '¿Qué genera el IV aleatorio?',
                    'options' => ['a' => 'random_bytes()', 'b' => 'md5(time())', 'c' => 'La fecha del sistema'],
                    'answer' => 'a',
                    'explanation' => 'Los IV/nonce deben generarse con una fuente criptográficamente segura.',
                ],
            ],
        ],
        'asimetrico' => [
            'label' => 'Cifrado asimétrico y firmas',
            'questions' => [
                [
                    'id' => 'a1',
                    'question' => 'Para cifrar un mensaje a otra persona se usa...',
                    'options' => ['a' => 'Su llave pública', 'b' => 'Su llave privada', 'c' => 'La llave maestra de la aplicación'],
                    'answer' => 'a',
                    'explanation' => 'La llave pública cifra; solo la llave privada correspondiente descifra.',
                ],
                [
                    'id' => 'a2',
                    'question' => '¿Para qué sirve una firma digital?',
                    'options' => ['a' => 'Para ocultar el mensaje', 'b' => 'Para demostrar autenticidad e integridad', 'c' => 'Para comprimir el mensaje'],
                    'answer' => 'b',
                    'explanation' => 'La firma no oculta; demuestra quién firmó y que el mensaje no cambió.',
                ],
                [
                    'id' => 'a3',
                    'question' => '¿Por qué RSA necesita cifrado híbrido para mensajes largos?',
                    'options' => ['a' => 'Porque RSA tiene un límite de tamaño según el relleno', 'b' => 'Porque RSA no admite texto', 'c' => 'Porque las llaves públicas son demasiado grandes'],
                    'answer' => 'a',
                    'explanation' => 'RSA-OAEP 2048 solo permite mensajes pequeños; para textos largos se cifra una llave AES con RSA.',
                ],
                [
                    'id' => 'a4',
                    'question' => 'En X25519, el secreto compartido se calcula con...',
                    'options' => ['a' => 'La llave privada propia y la pública del otro', 'b' => 'Las dos llaves públicas', 'c' => 'Las dos llaves privadas'],
                    'answer' => 'a',
                    'explanation' => 'Diffie-Hellman usa la privada propia y la pública del par.',
                ],
                [
                    'id' => 'a5',
                    'question' => '¿Qué ventaja tiene Ed25519 frente a RSA para firmar?',
                    'options' => ['a' => 'No necesita llave', 'b' => 'Llaves y firmas más pequeñas con buen rendimiento', 'c' => 'Permite cifrar archivos grandes'],
                    'answer' => 'b',
                    'explanation' => 'Ed25519 produce firmas compactas y rápidas con seguridad moderna.',
                ],
            ],
        ],
        'hash' => [
            'label' => 'Hash y contraseñas',
            'questions' => [
                [
                    'id' => 'h1',
                    'question' => 'Un hash criptográfico es...',
                    'options' => ['a' => 'Reversible con la llave', 'b' => 'Reversible sin llave', 'c' => 'Irreversible'],
                    'answer' => 'c',
                    'explanation' => 'Un hash no se descifra; solo se compara recalculándolo.',
                ],
                [
                    'id' => 'h2',
                    'question' => '¿Para qué sirve un salt en una contraseña?',
                    'options' => ['a' => 'Para que dos contraseñas iguales produzcan hashes distintos', 'b' => 'Para cifrar el hash', 'c' => 'Para reducir el tamaño del hash'],
                    'answer' => 'a',
                    'explanation' => 'El salt aleatorio evita tablas precomputadas y hashes iguales para la misma contraseña.',
                ],
                [
                    'id' => 'h3',
                    'question' => '¿Cuál es más adecuado para contraseñas?',
                    'options' => ['a' => 'MD5', 'b' => 'SHA-256 solo', 'c' => 'Argon2id'],
                    'answer' => 'c',
                    'explanation' => 'Argon2id es lento y consume memoria, lo que dificulta ataques de fuerza bruta.',
                ],
                [
                    'id' => 'h4',
                    'question' => '¿Qué aporta HMAC frente a un hash simple?',
                    'options' => ['a' => 'Una llave secreta para autenticar el mensaje', 'b' => 'Cifrado reversible', 'c' => 'Compresión'],
                    'answer' => 'a',
                    'explanation' => 'HMAC combina hash y llave; sin la llave no se puede recalcular el código.',
                ],
                [
                    'id' => 'h5',
                    'question' => '¿Base64 es cifrado?',
                    'options' => ['a' => 'Sí, con llave', 'b' => 'No, es una codificación reversible sin llave', 'c' => 'Sí, pero solo para archivos'],
                    'answer' => 'b',
                    'explanation' => 'Base64 solo representa bytes en texto; cualquiera puede decodificarlo.',
                ],
            ],
        ],
        'autenticacion' => [
            'label' => 'Autenticación cifrada 5.4',
            'questions' => [
                [
                    'id' => 'd1',
                    'question' => 'En la pantalla 5.4, el usuario se cifra por defecto con...',
                    'options' => ['a' => 'Criptografía simétrica', 'b' => 'Solo hash', 'c' => 'Base64'],
                    'answer' => 'a',
                    'explanation' => 'Por defecto el usuario usa un algoritmo simétrico y la contraseña RSA-OAEP.',
                ],
                [
                    'id' => 'd2',
                    'question' => '¿Por qué el login real no muestra contraseñas en claro?',
                    'options' => ['a' => 'Porque usa hash irreversible y no cifrado reversible', 'b' => 'Porque las contraseñas son demasiado largas', 'c' => 'Porque no guarda usuarios'],
                    'answer' => 'a',
                    'explanation' => 'El login real usa Argon2id; la demo 5.4 es una excepción educativa.',
                ],
                [
                    'id' => 'd3',
                    'question' => '¿Qué demuestra el botón Descifrar de la tabla?',
                    'options' => ['a' => 'Que el ciphertext se puede revertir con las llaves guardadas', 'b' => 'Que todo cifrado es inseguro', 'c' => 'Que el password está hasheado'],
                    'answer' => 'a',
                    'explanation' => 'La demo guarda las llaves necesarias para mostrar la reversibilidad del cifrado.',
                ],
                [
                    'id' => 'd4',
                    'question' => '¿Dónde se guarda la llave privada de la demo?',
                    'options' => ['a' => 'En texto plano', 'b' => 'Cifrada con la llave maestra', 'c' => 'En el navegador'],
                    'answer' => 'b',
                    'explanation' => 'La llave privada se cifra con CRYPTO_MASTER_KEY antes de guardarse.',
                ],
                [
                    'id' => 'd5',
                    'question' => '¿Qué significa el aviso educativo de la pantalla 5.4?',
                    'options' => ['a' => 'Que mostrar contraseñas en claro solo es aceptable en esta demo', 'b' => 'Que la demo es para producción', 'c' => 'Que las contraseñas no importan'],
                    'answer' => 'a',
                    'explanation' => 'En un sistema real nunca deben mostrarse contraseñas en claro.',
                ],
            ],
        ],
    ];

    /**
     * @return array<string, string>
     */
    public function modules(): array
    {
        return array_map(fn (array $module): string => $module['label'], self::BANK);
    }

    /**
     * @return list<array{id: string, question: string, options: array<string, string>}>
     */
    public function questions(string $module): array
    {
        $questions = $this->module($module)['questions'];

        return array_map(fn (array $question): array => [
            'id' => $question['id'],
            'question' => $question['question'],
            'options' => $question['options'],
        ], $questions);
    }

    /**
     * @param  array<string, string>  $answers
     * @return array{module: string, score: int, total: int, percentage: float, details: list<array<string, mixed>>}
     */
    public function evaluate(string $module, array $answers): array
    {
        $questions = $this->module($module)['questions'];
        $details = [];
        $score = 0;

        foreach ($questions as $question) {
            $selected = $answers[$question['id']] ?? null;
            $correct = $selected === $question['answer'];

            if ($correct) {
                $score++;
            }

            $details[] = [
                'id' => $question['id'],
                'question' => $question['question'],
                'selected' => $selected,
                'correct_answer' => $question['answer'],
                'correct' => $correct,
                'explanation' => $question['explanation'],
            ];
        }

        $total = count($questions);

        return [
            'module' => $this->module($module)['label'],
            'score' => $score,
            'total' => $total,
            'percentage' => $total > 0 ? round(($score / $total) * 100, 1) : 0,
            'details' => $details,
        ];
    }

    /**
     * @return array{label: string, questions: list<array<string, mixed>>}
     */
    private function module(string $module): array
    {
        return self::BANK[$module] ?? throw new \InvalidArgumentException('Módulo de quiz no válido.');
    }
}
