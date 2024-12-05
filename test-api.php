<?php
function testAPIKey($api_key) {
    echo "Testing API key: " . substr($api_key, 0, 8) . "...\n\n";
    
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/models',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        echo "HTTP Status Code: " . $http_code . "\n";
        echo "Response:\n";
        echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";
        
        if ($http_code === 200) {
            echo "\n✅ API-sleutel is geldig!\n";
            return true;
        } else {
            echo "\n❌ API-sleutel is ongeldig.\n";
            return false;
        }
    } catch (Exception $e) {
        echo "\n❌ Er is een fout opgetreden: " . $e->getMessage() . "\n";
        return false;
    } finally {
        if (isset($ch)) {
            curl_close($ch);
        }
    }
}

// De API-sleutel die we gaan testen
$api_key = 'sk-proj-754224-2890677-4256667';

// Voer de test uit
testAPIKey($api_key);
