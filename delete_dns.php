<?php
     // Cloudflare API credentials
      $apiKey = 'API';
      $email = 'EMAIL';
      $zoneId = 'ID ZONE';

// Cloudflare API endpoints
$dnsRecordsUrl = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records";

// cURL function to send requests
function sendRequest($url, $method = 'GET', $data = null) {
    global $apiKey, $email;
    
    $ch = curl_init();

    $headers = [
        "X-Auth-Email: $email",
        "X-Auth-Key: $apiKey",
        "Content-Type: application/json"
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    } elseif ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Handle delete single record
if (isset($_POST['record_id'])) {
    $recordId = $_POST['record_id'];
    $deleteUrl = "$dnsRecordsUrl/$recordId";
    $deleteResponse = sendRequest($deleteUrl, 'DELETE');

    echo json_encode($deleteResponse);
    exit;
}

// Handle delete selected records
if (isset($_POST['record_ids'])) {
    $recordIds = $_POST['record_ids'];
    $success = true;

    foreach ($recordIds as $recordId) {
        $deleteUrl = "$dnsRecordsUrl/$recordId";
        $deleteResponse = sendRequest($deleteUrl, 'DELETE');
        if (!$deleteResponse['success']) {
            $success = false;
            break;
        }
    }

    echo json_encode(['success' => $success]);
    exit;
}

// Handle delete all records
if (isset($_POST['delete_all']) && $_POST['delete_all'] == true) {
    // Fetch all DNS records
    $response = sendRequest($dnsRecordsUrl);
    if ($response['success']) {
        $dnsRecords = $response['result'];
        $success = true;
        foreach ($dnsRecords as $record) {
            $recordId = $record['id'];
            $deleteUrl = "$dnsRecordsUrl/$recordId";
            $deleteResponse = sendRequest($deleteUrl, 'DELETE');
            if (!$deleteResponse['success']) {
                $success = false;
                break;
            }
        }
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'errors' => $response['errors']]);
    }
    exit;
}

echo json_encode(['success' => false]);
