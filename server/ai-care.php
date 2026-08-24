<?php
require_once __DIR__ . '/config.php';

function generate_care_notes($name, $species, $owner_notes){
    $api_key = getenv('OPENAI_API_KEY');
    if($api_key === false || $api_key === '') $api_key = OPENAI_API_KEY;

    if($api_key === '') return ["success" => false, "error" => "AI care notes are not configured yet"];
    if(!function_exists('curl_init')) return ["success" => false, "error" => "PHP cURL is required for AI care notes"];

    $owner_context = $owner_notes === '' ? 'None supplied.' : $owner_notes;
    $prompt = "Create concise, practical care notes for a plant-sitting marketplace.\n\n"
        . "Plant name: $name\nSpecies: $species\nOwner notes: $owner_context\n\n"
        . "Write 3-5 short plain-text sentences. Include watering, light, and one observation tip only when appropriate. "
        . "Do not invent exact schedules or present uncertain care guidance as fact. If species is Unknown, say that the sitter should follow the owner's notes and check in with the owner. "
        . "Do not use markdown, headings, bullet points, or safety disclaimers.";

    $payload = [
        "model" => OPENAI_MODEL,
        "input" => $prompt,
        "max_output_tokens" => 220,
        "store" => false
    ];

    $curl = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25
    ]);
    $raw_response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if($raw_response === false || $http_code < 200 || $http_code >= 300){
        return ["success" => false, "error" => $curl_error !== '' ? "AI request failed: $curl_error" : "AI request failed"];
    }

    $api_response = json_decode($raw_response, true);
    $care_notes = isset($api_response['output_text']) ? trim($api_response['output_text']) : '';
    if($care_notes === '') return ["success" => false, "error" => "AI returned no care notes"];

    return ["success" => true, "data" => $care_notes];
}
?>
