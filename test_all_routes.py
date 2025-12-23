#!/usr/bin/env python
"""
Script de test complet pour toutes les routes de l'API
"""

import requests
import json
import sys
from datetime import datetime

API_URL = "http://localhost:5000/api/v1"
BASE_URL = "http://localhost:5000"

# Variables globales pour stocker les données de test
test_user = {
    'email': f'test_{datetime.now().strftime("%Y%m%d%H%M%S")}@test.com',
    'username': f'testuser_{datetime.now().strftime("%Y%m%d%H%M%S")}',
    'password': 'Test1234!',
    'first_name': 'Test',
    'last_name': 'User'
}
auth_token = None
test_product_id = None
test_alert_id = None

def print_section(title):
    """Afficher un titre de section"""
    print("\n" + "=" * 60)
    print(f"  {title}")
    print("=" * 60)

def print_result(name, status_code, success=True, details=""):
    """Afficher le résultat d'un test"""
    icon = "✅" if success else "❌"
    print(f"{icon} {name}: {status_code}", end="")
    if details:
        print(f" - {details}")
    else:
        print()

def test_health():
    """Test de l'endpoint health"""
    print_section("🏥 Health Check")
    try:
        response = requests.get(f"{BASE_URL}/api/health", timeout=5)
        print_result("GET /api/health", response.status_code, response.status_code == 200)
        if response.status_code == 200:
            print(f"   Response: {response.json()}")
        return response.status_code == 200
    except Exception as e:
        print_result("GET /api/health", 0, False, str(e))
        return False

def test_auth_register():
    """Test de l'inscription"""
    print_section("🔐 Authentication - Register")
    try:
        response = requests.post(
            f"{API_URL}/auth/register",
            json=test_user,
            timeout=10
        )
        success = response.status_code in [200, 201]
        print_result("POST /auth/register", response.status_code, success)
        if success:
            data = response.json()
            print(f"   User created: {data.get('user', {}).get('email', 'N/A')}")
        else:
            print(f"   Error: {response.json()}")
        return success
    except Exception as e:
        print_result("POST /auth/register", 0, False, str(e))
        return False

def test_auth_login():
    """Test de la connexion"""
    global auth_token
    print_section("🔐 Authentication - Login")
    try:
        response = requests.post(
            f"{API_URL}/auth/login",
            json={
                'email': test_user['email'],
                'password': test_user['password']
            },
            timeout=10
        )
        success = response.status_code == 200
        print_result("POST /auth/login", response.status_code, success)
        if success:
            data = response.json()
            # Le token peut être dans 'access_token' ou 'token'
            auth_token = data.get('access_token') or data.get('token')
            if auth_token:
                print(f"   Token obtained: {auth_token[:30]}...")
            else:
                print(f"   Response data: {data}")
        else:
            print(f"   Error: {response.json()}")
        return success and auth_token is not None
    except Exception as e:
        print_result("POST /auth/login", 0, False, str(e))
        return False

def test_auth_me():
    """Test de l'endpoint /auth/me"""
    print_section("🔐 Authentication - Me")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        headers = {'Authorization': f'Bearer {auth_token}'}
        response = requests.get(
            f"{API_URL}/auth/me",
            headers=headers,
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /auth/me", response.status_code, success)
        if not success:
            print(f"   Error: {response.json()}")
            print(f"   Token used: {auth_token[:50]}...")
        else:
            data = response.json()
            print(f"   User: {data.get('email', 'N/A')}")
        return success
    except Exception as e:
        print_result("GET /auth/me", 0, False, str(e))
        return False

def test_products_scrape_preview():
    """Test du scraping preview"""
    print_section("📦 Products - Scrape Preview")
    try:
        response = requests.post(
            f"{API_URL}/products/scrape-preview",
            json={'amazon_url': 'https://amzn.eu/d/bvp7pE1'},
            timeout=120
        )
        success = response.status_code == 200
        print_result("POST /products/scrape-preview", response.status_code, success)
        if success:
            data = response.json()
            print(f"   Product: {data.get('data', {}).get('title', 'N/A')[:50]}...")
        return success
    except Exception as e:
        print_result("POST /products/scrape-preview", 0, False, str(e))
        return False

def test_products_create():
    """Test de création de produit"""
    global test_product_id
    print_section("📦 Products - Create")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.post(
            f"{API_URL}/products",
            headers={'Authorization': f'Bearer {auth_token}'},
            json={
                'amazon_url': 'https://amzn.eu/d/bvp7pE1',
                'target_price': 30.0
            },
            timeout=120
        )
        success = response.status_code in [200, 201]
        print_result("POST /products", response.status_code, success)
        if success:
            data = response.json()
            test_product_id = data.get('product', {}).get('id')
            print(f"   Product ID: {test_product_id}")
        else:
            error_data = response.json()
            print(f"   Error: {error_data}")
            print(f"   Response text: {response.text[:200]}")
        return success
    except Exception as e:
        print_result("POST /products", 0, False, str(e))
        return False

def test_products_list():
    """Test de liste des produits"""
    print_section("📦 Products - List")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.get(
            f"{API_URL}/products",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /products", response.status_code, success)
        if success:
            data = response.json()
            count = len(data.get('products', {}).get('data', []))
            print(f"   Products count: {count}")
        return success
    except Exception as e:
        print_result("GET /products", 0, False, str(e))
        return False

def test_products_get():
    """Test de récupération d'un produit"""
    print_section("📦 Products - Get")
    if not auth_token or not test_product_id:
        print("⚠️  Skipped: No auth token or product ID")
        return False
    try:
        response = requests.get(
            f"{API_URL}/products/{test_product_id}",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result(f"GET /products/{test_product_id}", response.status_code, success)
        return success
    except Exception as e:
        print_result(f"GET /products/{test_product_id}", 0, False, str(e))
        return False

def test_alerts_create():
    """Test de création d'alerte"""
    global test_alert_id
    print_section("🔔 Alerts - Create")
    if not auth_token or not test_product_id:
        print("⚠️  Skipped: No auth token or product ID")
        return False
    try:
        response = requests.post(
            f"{API_URL}/alerts",
            headers={'Authorization': f'Bearer {auth_token}'},
            json={
                'product_id': test_product_id,
                'alert_type': 'PRICE_DROP',
                'target_price': 25.0
            },
            timeout=10
        )
        success = response.status_code in [200, 201]
        print_result("POST /alerts", response.status_code, success)
        if success:
            data = response.json()
            test_alert_id = data.get('alert', {}).get('id')
            print(f"   Alert ID: {test_alert_id}")
        return success
    except Exception as e:
        print_result("POST /alerts", 0, False, str(e))
        return False

def test_alerts_list():
    """Test de liste des alertes"""
    print_section("🔔 Alerts - List")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.get(
            f"{API_URL}/alerts",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /alerts", response.status_code, success)
        if success:
            data = response.json()
            count = len(data.get('alerts', {}).get('data', []))
            print(f"   Alerts count: {count}")
        return success
    except Exception as e:
        print_result("GET /alerts", 0, False, str(e))
        return False

def test_dashboard():
    """Test du dashboard"""
    print_section("📊 Dashboard")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.get(
            f"{API_URL}/dashboard",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /dashboard", response.status_code, success)
        if success:
            data = response.json()
            stats = data.get('stats', {})
            print(f"   Products: {stats.get('total_products', 0)}")
            print(f"   Active alerts: {stats.get('active_alerts', 0)}")
        return success
    except Exception as e:
        print_result("GET /dashboard", 0, False, str(e))
        return False

def test_users_me():
    """Test de récupération du profil utilisateur"""
    print_section("👤 Users - Me")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.get(
            f"{API_URL}/users/me",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /users/me", response.status_code, success)
        return success
    except Exception as e:
        print_result("GET /users/me", 0, False, str(e))
        return False

def test_subscriptions_list():
    """Test de liste des abonnements"""
    print_section("💳 Subscriptions - List")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.get(
            f"{API_URL}/subscriptions",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /subscriptions", response.status_code, success)
        return success
    except Exception as e:
        print_result("GET /subscriptions", 0, False, str(e))
        return False

def test_subscriptions_plans():
    """Test de liste des plans d'abonnement"""
    print_section("💳 Subscriptions - Plans")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.get(
            f"{API_URL}/subscriptions/plans",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /subscriptions/plans", response.status_code, success)
        return success
    except Exception as e:
        print_result("GET /subscriptions/plans", 0, False, str(e))
        return False

def test_newsletter_consent():
    """Test de consentement newsletter"""
    print_section("📧 Newsletter - Consent")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        # GET consent
        response = requests.get(
            f"{API_URL}/newsletter/consent",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /newsletter/consent", response.status_code, success)
        
        # PUT consent
        response = requests.put(
            f"{API_URL}/newsletter/consent",
            headers={'Authorization': f'Bearer {auth_token}'},
            json={'consent': True},
            timeout=10
        )
        success = success and response.status_code == 200
        print_result("PUT /newsletter/consent", response.status_code, response.status_code == 200)
        return success
    except Exception as e:
        print_result("Newsletter consent", 0, False, str(e))
        return False

def test_gdpr_export():
    """Test d'export GDPR"""
    print_section("🔒 GDPR - Export Data")
    if not auth_token:
        print("⚠️  Skipped: No auth token")
        return False
    try:
        response = requests.get(
            f"{API_URL}/gdpr/export-data",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result("GET /gdpr/export-data", response.status_code, success)
        if success:
            data = response.json()
            print(f"   Data exported: {len(str(data))} bytes")
        return success
    except Exception as e:
        print_result("GET /gdpr/export-data", 0, False, str(e))
        return False

def test_affiliate_buy_link():
    """Test de lien d'affiliation"""
    print_section("🔗 Affiliate - Buy Link")
    if not auth_token or not test_product_id:
        print("⚠️  Skipped: No auth token or product ID")
        return False
    try:
        response = requests.get(
            f"{API_URL}/affiliate/products/{test_product_id}/buy-link",
            headers={'Authorization': f'Bearer {auth_token}'},
            timeout=10
        )
        success = response.status_code == 200
        print_result(f"GET /affiliate/products/{test_product_id}/buy-link", response.status_code, success)
        return success
    except Exception as e:
        print_result(f"GET /affiliate/products/{test_product_id}/buy-link", 0, False, str(e))
        return False

def main():
    """Fonction principale"""
    print("=" * 60)
    print("🧪 Test complet de toutes les routes de l'API")
    print("=" * 60)
    print(f"\n📝 Test user: {test_user['email']}")
    print(f"🌐 API URL: {API_URL}\n")
    
    results = {}
    
    # Tests publics
    results['health'] = test_health()
    results['scrape_preview'] = test_products_scrape_preview()
    
    # Tests d'authentification
    results['register'] = test_auth_register()
    results['login'] = test_auth_login()
    results['auth_me'] = test_auth_me()
    
    # Tests nécessitant authentification
    if auth_token:
        results['products_create'] = test_products_create()
        results['products_list'] = test_products_list()
        results['products_get'] = test_products_get()
        results['alerts_create'] = test_alerts_create()
        results['alerts_list'] = test_alerts_list()
        results['dashboard'] = test_dashboard()
        results['users_me'] = test_users_me()
        results['subscriptions_list'] = test_subscriptions_list()
        results['subscriptions_plans'] = test_subscriptions_plans()
        results['newsletter_consent'] = test_newsletter_consent()
        results['gdpr_export'] = test_gdpr_export()
        results['affiliate_buy_link'] = test_affiliate_buy_link()
    
    # Résumé
    print_section("📊 Résumé des tests")
    total = len(results)
    passed = sum(1 for v in results.values() if v)
    failed = total - passed
    
    print(f"✅ Réussis: {passed}/{total}")
    print(f"❌ Échoués: {failed}/{total}")
    print(f"📈 Taux de réussite: {(passed/total*100):.1f}%")
    
    print("\n" + "=" * 60)
    if failed == 0:
        print("✨ Tous les tests sont passés avec succès!")
    else:
        print("⚠️  Certains tests ont échoué")
        print("\nTests échoués:")
        for name, success in results.items():
            if not success:
                print(f"  ❌ {name}")
    print("=" * 60)
    
    return failed == 0

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)

