#!/usr/bin/env python3

import requests
import json

def test_extract_product():
    base_url = 'http://31.97.185.5:8001'
    
    print('🧪 Test d\'extraction de produit Amazon')
    print('=====================================')
    
    # 1. Test Health Check
    print('\n1. Test Health Check...')
    try:
        response = requests.get(f'{base_url}/api/health')
        if response.status_code == 200:
            print('✅ Health Check: OK')
            print(f'   Response: {response.json()}')
        else:
            print(f'❌ Health Check: FAILED ({response.status_code})')
            print(f'   Response: {response.text}')
    except requests.exceptions.RequestException as e:
        print(f'❌ Health Check: ERROR - {e}')
        return
    
    # 2. Test Register
    print('\n2. Test Register...')
    try:
        register_data = {
            'email': 'test@example.com',
            'username': 'testuser',
            'password': 'TestPassword123!',
            'first_name': 'Test',
            'last_name': 'User',
        }
        response = requests.post(f'{base_url}/api/v1/auth/register', json=register_data)
        
        if response.status_code == 201:
            data = response.json()
            access_token = data['access_token']
            print('✅ Register: OK')
            print(f'   User ID: {data["user"]["id"]}')
        else:
            print(f'❌ Register: FAILED ({response.status_code})')
            print(f'   Response: {response.text}')
            return
    except requests.exceptions.RequestException as e:
        print(f'❌ Register: ERROR - {e}')
        return
    
    headers = {'Authorization': f'Bearer {access_token}', 'Content-Type': 'application/json'}
    
    # 3. Test Scrape Preview
    print('\n3. Test Scrape Preview...')
    try:
        scrape_url = 'https://www.amazon.fr/dp/B0C735J188'  # Example Amazon URL
        response = requests.post(f'{base_url}/api/v1/products/scrape-preview', 
                               headers=headers, 
                               json={'amazon_url': scrape_url})
        
        if response.status_code == 200:
            data = response.json()
            print('✅ Scrape Preview: OK')
            print(f'   Product Title: {data["data"]["title"][:50]}...')
            print(f'   Current Price: {data["data"]["price"]}')
            print(f'   Image URL: {data["data"]["image_url"][:50]}...')
        else:
            print(f'❌ Scrape Preview: FAILED ({response.status_code})')
            print(f'   Response: {response.text}')
    except requests.exceptions.RequestException as e:
        print(f'❌ Scrape Preview: ERROR - {e}')
    
    # 4. Test Create Product with Scraping
    print('\n4. Test Create Product with Scraping...')
    try:
        product_data = {
            'amazon_url': 'https://www.amazon.fr/dp/B0C735J188',
            'target_price': 150.00,
            'scrape_data': True
        }
        response = requests.post(f'{base_url}/api/v1/products', 
                               headers=headers, 
                               json=product_data)
        
        if response.status_code == 201:
            data = response.json()
            print('✅ Create Product: OK')
            print(f'   Product ID: {data["product"]["id"]}')
            print(f'   Product Title: {data["product"]["title"][:50]}...')
            print(f'   Current Price: {data["product"]["current_price"]}')
        else:
            print(f'❌ Create Product: FAILED ({response.status_code})')
            print(f'   Response: {response.text}')
    except requests.exceptions.RequestException as e:
        print(f'❌ Create Product: ERROR - {e}')
    
    print('\n🎉 Test terminé!')

if __name__ == '__main__':
    test_extract_product()


