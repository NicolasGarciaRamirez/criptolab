<?php

namespace Tests\Unit\Crypto;

use App\Services\Crypto\QuizService;
use Tests\TestCase;

class QuizServiceTest extends TestCase
{
    public function test_correct_answers_produce_full_score(): void
    {
        $service = app(QuizService::class);
        $answers = [];

        foreach ($service->questions('simetrico') as $question) {
            $answers[$question['id']] = match ($question['id']) {
                's1' => 'b',
                's2' => 'c',
                's3' => 'b',
                's4' => 'c',
                's5' => 'a',
            };
        }

        $result = $service->evaluate('simetrico', $answers);

        $this->assertSame(5, $result['score']);
        $this->assertSame(5, $result['total']);
        $this->assertSame(100.0, $result['percentage']);
    }

    public function test_answers_are_not_included_in_question_payload(): void
    {
        $question = app(QuizService::class)->questions('hash')[0];

        $this->assertArrayNotHasKey('answer', $question);
        $this->assertArrayNotHasKey('explanation', $question);
    }
}
