<?php

function generateSummaries($patientsData) {
    $apiKey = "your-openai-api-key"; // Replace with your API key
    $url = "https://api.openai.com/v1/chat/completions";

    $summaries = []; // To store all patient summaries

    foreach ($patientsData as $patientId => $questionsAndAnswers) {
        // Prepare the prompt for each patient
        $messages = [
            ["role" => "system", "content" => "You are a medical assistant generating a patient report summary."],
            ["role" => "user", "content" => "Here are the questions and answers for patient ID $patientId. Please summarize them into a coherent report:"]
        ];

        foreach ($questionsAndAnswers as $question => $answer) {
            $messages[] = ["role" => "user", "content" => "Q: $question\nA: $answer"];
        }

        // API request payload
        $postData = [
            "model" => "gpt-4", // Use "gpt-3.5-turbo" if you prefer
            "messages" => $messages,
            "temperature" => 0.7
        ];

        $headers = [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ];

        // cURL initialization
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute the request
        $response = curl_exec($ch);
        curl_close($ch);

        // Parse and store the response
        $responseData = json_decode($response, true);
        if (isset($responseData['choices'][0]['message']['content'])) {
            $summaries[$patientId] = $responseData['choices'][0]['message']['content'];
        } else {
            $summaries[$patientId] = "Error: Unable to generate summary for patient ID $patientId.";
        }
    }

    return $summaries;
}

// Example patient data
$patientsData = [
    "Patient1" => [
        "What is your main concern?" => "I have a persistent headache.",
        "How long have you been experiencing this?" => "About two weeks.",
        "Have you taken any medication?" => "Yes, paracetamol but it doesn't help.",
        "Do you have any other symptoms?" => "Occasionally nausea.",
        "Have you experienced this before?" => "No, this is the first time."
    ],
    "Patient2" => [
        "What is your main concern?" => "I have a sore throat.",
        "How long have you been experiencing this?" => "For three days.",
        "Have you taken any medication?" => "No, not yet.",
        "Do you have any other symptoms?" => "Fever and difficulty swallowing.",
        "Have you experienced this before?" => "Yes, last year around the same time."
    ]
];

// Generate summaries
$summaries = generateSummaries($patientsData);

// Display all summaries
foreach ($summaries as $patientId => $summary) {
    echo "<h3>Summary for $patientId:</h3>";
    echo "<p>$summary</p>";
}

?>
