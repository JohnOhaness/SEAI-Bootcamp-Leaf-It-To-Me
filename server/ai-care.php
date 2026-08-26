<?php
// ai-care.php

// Safely load config.php if it exists
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

function generate_care_notes($name, $species, $owner_notes) {
    // 1. Resolve API Key
    $api_key = "AQ.Ab8RN6K3mCM-00ac31Wsw_FMHVC76FYEE4w2Yeiu3AZLfT_szg";
    
    if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
        $api_key = GEMINI_API_KEY;
    } elseif (getenv('GEMINI_API_KEY') !== false && getenv('GEMINI_API_KEY') !== '') {
        $api_key = getenv('GEMINI_API_KEY');
    } else {
        // Fallback hardcoded key (replace with your actual key if not using config.php)
        $api_key = 'YOUR_GEMINI_API_KEY_HERE';
    }

    if (empty($api_key) || $api_key === 'YOUR_GEMINI_API_KEY_HERE') {
        return ["success" => false, "error" => "Gemini API key is not configured"];
    }

    // 2. Check cURL extension
    if (!function_exists('curl_init')) {
        return ["success" => false, "error" => "PHP cURL extension is not enabled on this server"];
    }

    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-3.6-flash';
    $owner_context = empty($owner_notes) ? 'None supplied.' : $owner_notes;

    $prompt = "Create concise, practical care notes for a plant-sitting marketplace.\n\n"
        . "Plant name: $name\nSpecies: $species\nOwner notes: $owner_context\n\n"
        . "Write 3-5 short plain-text sentences. Include watering, light, and one observation tip only when appropriate. "
        . "Do not invent exact schedules or present uncertain care guidance as fact. If species is Unknown, say that the sitter should follow the owner's notes and check in with the owner. "
        . "Do not use markdown, headings, bullet points, or safety disclaimers.";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "maxOutputTokens" => 2000
        ]
    ];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $api_key
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $raw_response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($raw_response === false) {
        return ["success" => false, "error" => "cURL network error: " . $curl_error];
    }

    $api_response = json_decode($raw_response, true);

    if ($http_code < 200 || $http_code >= 300) {
        $api_error_message = isset($api_response['error']['message']) 
            ? $api_response['error']['message'] 
            : "HTTP $http_code";
        return ["success" => false, "error" => "Gemini API Error: " . $api_error_message];
    }

    $care_notes = isset($api_response['candidates'][0]['content']['parts'][0]['text'])
        ? trim($api_response['candidates'][0]['content']['parts'][0]['text'])
        : '';

    if ($care_notes === '') {
        return ["success" => false, "error" => "Gemini returned an empty response"];
    }

    return ["success" => true, "data" => $care_notes];
}
?>