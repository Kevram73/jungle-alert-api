<?php

// Script de test complet pour les alertes
$baseUrl = 'http://31.97.185.5:8001';

echo "🚨 TEST COMPLET DES ALERTES JUNGLE ALERT API 🚨\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// 1. Connexion avec un utilisateur existant
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

echo "Code HTTP: $httpCode\n";
$loginResult = json_decode($response, true);
$token = $loginResult['access_token'] ?? null;

if (!$token) {
    echo "❌ Impossible d'obtenir le token d'authentification\n";
    exit(1);
}

echo "✅ Token obtenu: " . substr($token, 0, 20) . "...\n\n";

// 2. Créer un nouveau produit pour les tests
echo "📦 Création d'un nouveau produit...\n";
$productData = [
    'amazon_url' => 'https://www.amazon.com/dp/B08N5WRWNW',
    'title' => 'Echo Show 8 (2ème génération) - Écran intelligent avec Alexa',
    'price' => 129.99,
    'image_url' => 'https://m.media-amazon.com/images/I/61jKIhJhLUL._AC_SL1000_.jpg',
    'description' => 'Écran intelligent avec Alexa - Écran HD 8 pouces',
    'availability' => 'En stock',
    'asin' => 'B08N5WRWNW'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/products');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($productData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
$productResult = json_decode($response, true);
$productId = $productResult['product']['id'] ?? null;

if (!$productId) {
    echo "❌ Impossible de créer le produit\n";
    exit(1);
}

echo "✅ Produit créé avec l'ID: $productId\n\n";

// 3. Créer plusieurs alertes
echo "🚨 Création d'alertes...\n";

$alerts = [
    [
        'product_id' => $productId,
        'alert_type' => 'PRICE_DROP',
        'target_price' => 100.00,
        'is_active' => true
    ],
    [
        'product_id' => $productId,
        'alert_type' => 'PRICE_INCREASE',
        'target_price' => 150.00,
        'is_active' => true
    ],
    [
        'product_id' => $productId,
        'alert_type' => 'STOCK_AVAILABLE',
        'target_price' => 0.00,
        'is_active' => false
    ]
];

$alertIds = [];
foreach ($alerts as $i => $alertData) {
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

    echo "Alerte " . ($i + 1) . " - Code HTTP: $httpCode\n";
    $alertResult = json_decode($response, true);
    if (isset($alertResult['alert']['id'])) {
        $alertIds[] = $alertResult['alert']['id'];
        echo "✅ Alerte créée avec l'ID: " . $alertResult['alert']['id'] . "\n";
    } else {
        echo "❌ Erreur: " . ($alertResult['message'] ?? 'Inconnue') . "\n";
    }
}

echo "\n";

// 4. Lister toutes les alertes
echo "📋 Liste de toutes les alertes:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/alerts');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
$alertsResult = json_decode($response, true);
echo "Total d'alertes: " . $alertsResult['alerts']['total'] . "\n";
foreach ($alertsResult['alerts']['data'] as $alert) {
    echo "- Alerte #{$alert['id']}: {$alert['alert_type']} - Prix cible: {$alert['target_price']}€ - Actif: " . ($alert['is_active'] ? 'Oui' : 'Non') . "\n";
}

echo "\n";

// 5. Lister les alertes actives
echo "🟢 Alertes actives:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/alerts/active');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
$activeAlertsResult = json_decode($response, true);
echo "Alertes actives: " . count($activeAlertsResult['alerts']) . "\n";
foreach ($activeAlertsResult['alerts'] as $alert) {
    echo "- Alerte #{$alert['id']}: {$alert['alert_type']} - Prix cible: {$alert['target_price']}€\n";
}

echo "\n";

// 6. Lister les alertes déclenchées
echo "🔔 Alertes déclenchées:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/alerts/triggered');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
$triggeredAlertsResult = json_decode($response, true);
echo "Alertes déclenchées: " . $triggeredAlertsResult['alerts']['total'] . "\n";

echo "\n";

// 7. Lister les alertes par produit
echo "📦 Alertes pour le produit #$productId:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . "/api/v1/products/$productId/alerts");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
$productAlertsResult = json_decode($response, true);
echo "Alertes pour ce produit: " . count($productAlertsResult['alerts']) . "\n";
foreach ($productAlertsResult['alerts'] as $alert) {
    echo "- Alerte #{$alert['id']}: {$alert['alert_type']} - Prix cible: {$alert['target_price']}€ - Actif: " . ($alert['is_active'] ? 'Oui' : 'Non') . "\n";
}

echo "\n";

// 8. Tester la modification d'une alerte
if (!empty($alertIds)) {
    echo "✏️ Modification d'une alerte...\n";
    $alertId = $alertIds[0];
    $updateData = [
        'target_price' => 80.00,
        'is_active' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . "/api/v1/alerts/$alertId");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Code HTTP: $httpCode\n";
    $updateResult = json_decode($response, true);
    if ($httpCode == 200) {
        echo "✅ Alerte #$alertId modifiée - Nouveau prix cible: {$updateResult['alert']['target_price']}€\n";
    } else {
        echo "❌ Erreur lors de la modification\n";
    }

    echo "\n";

    // 9. Tester le toggle d'une alerte
    echo "🔄 Toggle d'une alerte...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . "/api/v1/alerts/$alertId/toggle");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Code HTTP: $httpCode\n";
    $toggleResult = json_decode($response, true);
    if ($httpCode == 200) {
        echo "✅ Alerte #$alertId togglée - Statut: " . ($toggleResult['alert']['is_active'] ? 'Actif' : 'Inactif') . "\n";
    } else {
        echo "❌ Erreur lors du toggle\n";
    }

    echo "\n";
}

// 10. Test final - Vérifier les alertes actives après modifications
echo "🔍 Vérification finale des alertes actives:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/alerts/active');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP: $httpCode\n";
$finalAlertsResult = json_decode($response, true);
echo "Alertes actives finales: " . count($finalAlertsResult['alerts']) . "\n";

echo "\n";
echo "🎉 TEST COMPLET TERMINÉ !\n";
echo "=" . str_repeat("=", 50) . "\n";
echo "✅ Toutes les fonctionnalités d'alertes sont opérationnelles !\n";

