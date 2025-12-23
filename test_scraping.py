#!/usr/bin/env python
"""
Script de test pour le scraping Amazon
"""

import requests
import json
import sys

API_URL = "http://localhost:5000/api/v1"

def test_scrape_preview(url):
    """Tester le scraping d'un produit Amazon"""
    print("=" * 60)
    print("🧪 Test du scraping Amazon")
    print("=" * 60)
    print(f"\n📦 URL à scraper: {url}")
    print("\n⏳ Scraping en cours (cela peut prendre 30-60 secondes)...")
    
    try:
        # Endpoint de prévisualisation (ne nécessite pas d'authentification si configuré)
        response = requests.post(
            f"{API_URL}/products/scrape-preview",
            json={"amazon_url": url},
            timeout=120  # Timeout de 2 minutes
        )
        
        print(f"\n📊 Status Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print("\n✅ Scraping réussi!")
            print("\n" + "=" * 60)
            print("📋 Données extraites:")
            print("=" * 60)
            
            product_data = data.get('data', {})
            
            print(f"\n📝 Titre: {product_data.get('title', 'N/A')}")
            print(f"💰 Prix: {product_data.get('price', 'N/A')} {product_data.get('currency', '')}")
            
            if product_data.get('original_price'):
                print(f"💰 Prix original: {product_data.get('original_price')} {product_data.get('currency', '')}")
            
            if product_data.get('discount_percentage'):
                print(f"🎯 Réduction: {product_data.get('discount_percentage')}%")
            
            print(f"⭐ Note: {product_data.get('rating', 'N/A')}/5")
            print(f"📊 Nombre d'avis: {product_data.get('review_count', 'N/A')}")
            print(f"📦 Disponibilité: {product_data.get('availability', 'N/A')}")
            print(f"🏷️  ASIN: {product_data.get('asin', 'N/A')}")
            print(f"🌍 Marketplace: {product_data.get('marketplace', 'N/A')}")
            print(f"🏢 Marque: {product_data.get('brand', 'N/A')}")
            
            if product_data.get('images'):
                print(f"🖼️  Images: {len(product_data.get('images', []))} image(s)")
                if product_data.get('image_url'):
                    print(f"   Image principale: {product_data.get('image_url')[:80]}...")
            
            if product_data.get('features'):
                print(f"\n✨ Caractéristiques ({len(product_data.get('features', []))}):")
                for i, feature in enumerate(product_data.get('features', [])[:5], 1):
                    print(f"   {i}. {feature[:80]}...")
            
            print("\n" + "=" * 60)
            print("📄 Données complètes (JSON):")
            print("=" * 60)
            print(json.dumps(data, indent=2, ensure_ascii=False))
            
            return True
        else:
            print(f"\n❌ Erreur: {response.status_code}")
            try:
                error_data = response.json()
                print(f"Message: {error_data.get('message', 'Erreur inconnue')}")
                if 'errors' in error_data:
                    print(f"Erreurs: {error_data['errors']}")
            except:
                print(f"Réponse: {response.text}")
            return False
            
    except requests.exceptions.Timeout:
        print("\n❌ Timeout - Le scraping prend trop de temps")
        return False
    except requests.exceptions.ConnectionError:
        print("\n❌ Erreur de connexion - L'API n'est pas accessible")
        print("💡 Vérifiez que l'application est démarrée: docker-compose ps")
        return False
    except Exception as e:
        print(f"\n❌ Erreur: {e}")
        import traceback
        traceback.print_exc()
        return False


def main():
    """Fonction principale"""
    # URL par défaut ou depuis les arguments
    if len(sys.argv) > 1:
        url = sys.argv[1]
    else:
        # URL d'exemple
        url = "https://www.amazon.fr/dp/B08N5WRWNW"
        print(f"ℹ️  Aucune URL fournie, utilisation de l'URL par défaut")
        print(f"   Vous pouvez fournir une URL: python test_scraping.py <URL>")
    
    success = test_scrape_preview(url)
    
    if success:
        print("\n✨ Test terminé avec succès!")
    else:
        print("\n⚠️  Test échoué")
        sys.exit(1)


if __name__ == "__main__":
    main()

