<?php

function getTranscript($video_id) {
    $apiKey = "YOUR_RAPIDAPI_KEY";

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => "https://youtube-transcript-api.p.rapidapi.com/transcript?video_id=$video_id",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "X-RapidAPI-Key: $apiKey",
            "X-RapidAPI-Host: youtube-transcript-api.p.rapidapi.com"
        ],
    ]);

    $response = curl_exec($ch);

    return $response;
}