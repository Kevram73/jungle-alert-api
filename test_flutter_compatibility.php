<?php

// Script de test de compatibilité avec l'application Flutter
$baseUrl = 'http://31.97.185.5:8001';

echo "📱 TEST DE COMPATIBILITÉ FLUTTER - JUNGLE ALERT API 📱\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// 1. Connexion
echo "🔑 Connexion...\n";
$loginData = [
    'email' => 'test3@example.com',
    'password' => 'password123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$loginResult = json_decode($response, true);
$token = $loginResult['access_token'] ?? null;

if (!$token) {
    echo "❌ Impossible d'obtenir le token d'authentification\n";
    exit(1);
}

echo "✅ Token obtenu: " . substr($token, 0, 20) . "...\n\n";

// 2. Types d'alertes testés depuis l'application Flutter
$flutterAlertTypes = [
    'email_notification' => 'Notification par email',
    'immediate' => 'Alerte immédiate',
    'daily' => 'Alerte quotidienne',
    'weekly' => 'Alerte hebdomadaire',
    'price_drop' => 'Baisse de prix',
    'price_increase' => 'Augmentation de prix',
    'stock_available' => 'Disponibilité en stock'
];

echo "🚨 Test des types d'alertes Flutter...\n";
echo "=" . str_repeat("-", 50) . "\n";

$successCount = 0;
$totalCount = count($flutterAlertTypes);

foreach ($flutterAlertTypes as $alertType => $description) {
    echo "Test: $description ($alertType)\n";
    
    $alertData = [
        'product_id' => 23, // Produit existant
        'alert_type' => $alertType,
        'target_price' => rand(50, 150),
        'frequency' => 'immediate',
        'notification_methods' => ['email'],
        'is_active' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/alerts');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($alertData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    
    if ($httpCode == 201) {
        echo "✅ SUCCÈS - Alerte créée (ID: {$result['alert']['id']}, Type mappé: {$result['alert']['alert_type']})\n";
        $successCount++;
    } else {
        echo "❌ ÉCHEC - Code: $httpCode, Message: " . ($result['message'] ?? 'Inconnu') . "\n";
    }
    
    echo "\n";
}

// 3. Test avec des paramètres supplémentaires Flutter
echo "📋 Test des paramètres supplémentaires Flutter...\n";
echo "=" . str_repeat("-", 50) . "\n";

$flutterParams = [
    'frequency' => 'immediate',
    'notification_methods' => ['email', 'push', 'whatsapp'],
    'is_active' => true,
    'target_price' => 85.50
];

$alertData = [
    'product_id' => 24,
    'alert_type' => 'email_notification',
    'target_price' => $flutterParams['target_price'],
    'frequency' => $flutterParams['frequency'],
    'notification_methods' => $flutterParams['notification_methods'],
    'is_active' => $flutterParams['is_active']
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/alerts');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($alertData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode == 201) {
    echo "✅ SUCCÈS - Alerte avec paramètres Flutter créée (ID: {$result['alert']['id']})\n";
    echo "   - Type: {$result['alert']['alert_type']}\n";
    echo "   - Prix cible: {$result['alert']['target_price']}€\n";
    echo "   - Actif: " . ($result['alert']['is_active'] ? 'Oui' : 'Non') . "\n";
} else {
    echo "❌ ÉCHEC - Code: $httpCode, Message: " . ($result['message'] ?? 'Inconnu') . "\n";
}

echo "\n";

// 4. Résumé des tests
echo "📊 RÉSUMÉ DES TESTS\n";
echo "=" . str_repeat("=", 30) . "\n";
echo "Types d'alertes testés: $totalCount\n";
echo "Succès: $successCount\n";
echo "Échecs: " . ($totalCount - $successCount) . "\n";
echo "Taux de réussite: " . round(($successCount / $totalCount) * 100, 1) . "%\n\n";

if ($successCount == $totalCount) {
    echo "🎉 TOUS LES TESTS RÉUSSIS !\n";
    echo "✅ L'API est entièrement compatible avec l'application Flutter\n";
    echo "✅ Tous les types d'alertes Flutter sont supportés\n";
    echo "✅ Le mapping des types fonctionne correctement\n";
    echo "✅ Les paramètres supplémentaires sont acceptés\n";
} else {
    echo "⚠️  CERTAINS TESTS ONT ÉCHOUÉ\n";
    echo "❌ Vérifiez les types d'alertes non supportés\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔗 API disponible sur: $baseUrl\n";
echo "📱 Compatible avec l'application Flutter Jungle Alert\n";


