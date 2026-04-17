<?php

require_once __DIR__ . '/../vendor/autoload.php';

use OpenAI;

function generateQuestions($transcript, $numQuestions = 1) {

    $client = OpenAI::client("YOUR_OPENAI_API_KEY");

    $prompt = "
    Generate $numQuestions multiple-choice questions based ONLY on this transcript.

    Each question must:
    - Have 4 answer choices (A, B, C, D)
    - Only ONE correct answer
    - Be directly supported by the transcript

    Return JSON in this format:
    [
      {
        \"question\": \"...\",
        \"choices\": {
          \"A\": \"...\",
          \"B\": \"...\",
          \"C\": \"...\",
          \"D\": \"...\"
        },
        \"correct_answer\": \"A\"
      }
    ]

    Transcript:
    " . substr($transcript, 0, 4000);

    $response = $client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
    ]);

    return $response->choices[0]->message->content;
}