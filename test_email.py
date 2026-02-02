#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Script de test pour l'envoi d'emails
"""
import sys
import os

# Fixer l'encodage pour Windows
if sys.platform == 'win32':
    os.system('chcp 65001 > nul')
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8')

from app import create_app
from services.email_service import EmailService

def test_welcome_email(email_service, recipient_email):
    """Test l'envoi d'un email de bienvenue"""
    print("\n" + "="*60)
    print("TEST: Email de bienvenue")
    print("="*60)
    
    success = email_service.send_welcome_email(
        user_email=recipient_email,
        user_name="Utilisateur Test"
    )
    
    if success:
        print("✅ Email de bienvenue envoyé avec succès!")
        print(f"📧 Destinataire: {recipient_email}")
    else:
        print("❌ Échec de l'envoi de l'email de bienvenue")
    
    return success

def test_price_alert_email(email_service, recipient_email):
    """Test l'envoi d'un email d'alerte de prix"""
    print("\n" + "="*60)
    print("TEST: Email d'alerte de prix")
    print("="*60)
    
    success = email_service.send_price_alert_email(
        user_email=recipient_email,
        user_name="Utilisateur Test",
        product_name="Apple AirPods Pro (2ème génération)",
        product_url="https://www.amazon.fr/dp/B0CHWRXH8B",
        current_price=199.99,
        target_price=220.00,
        alert_type="PRICE_DROP"
    )
    
    if success:
        print("✅ Email d'alerte de prix envoyé avec succès!")
        print(f"📧 Destinataire: {recipient_email}")
    else:
        print("❌ Échec de l'envoi de l'email d'alerte de prix")
    
    return success

def test_simple_email(email_service, recipient_email):
    """Test l'envoi d'un email simple"""
    print("\n" + "="*60)
    print("TEST: Email simple")
    print("="*60)
    
    html_body = """
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                max-width: 600px;
                margin: 0 auto;
            }
            h1 {
                color: #667eea;
            }
            .info-box {
                background: #e7f3ff;
                padding: 15px;
                border-left: 4px solid #667eea;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧪 Test d'envoi d'email</h1>
            <p>Ceci est un email de test envoyé depuis <strong>Jungle Alert</strong>.</p>
            
            <div class="info-box">
                <strong>✅ Statut:</strong> Si vous recevez cet email, la configuration SMTP fonctionne correctement!
            </div>
            
            <p>Informations techniques:</p>
            <ul>
                <li>Service: Jungle Alert Email Service</li>
                <li>Type: Test simple</li>
                <li>Date: 2026-01-19</li>
            </ul>
            
            <p>Cordialement,<br>L'équipe Jungle Alert</p>
        </div>
    </body>
    </html>
    """
    
    text_body = """
    🧪 Test d'envoi d'email
    
    Ceci est un email de test envoyé depuis Jungle Alert.
    
    ✅ Statut: Si vous recevez cet email, la configuration SMTP fonctionne correctement!
    
    Informations techniques:
    - Service: Jungle Alert Email Service
    - Type: Test simple
    - Date: 2026-01-19
    
    Cordialement,
    L'équipe Jungle Alert
    """
    
    success = email_service.send_email(
        to_email=recipient_email,
        subject="🧪 Test Email - Jungle Alert",
        html_body=html_body,
        text_body=text_body
    )
    
    if success:
        print("✅ Email simple envoyé avec succès!")
        print(f"📧 Destinataire: {recipient_email}")
    else:
        print("❌ Échec de l'envoi de l'email simple")
    
    return success

def display_config_info(app):
    """Affiche les informations de configuration email (masquées)"""
    print("\n" + "="*60)
    print("CONFIGURATION EMAIL")
    print("="*60)
    print(f"Serveur SMTP: {app.config.get('MAIL_SERVER')}")
    print(f"Port: {app.config.get('MAIL_PORT')}")
    print(f"Utilise TLS: {app.config.get('MAIL_USE_TLS')}")
    print(f"Utilise SSL: {app.config.get('MAIL_USE_SSL')}")
    print(f"Utilisateur: {app.config.get('MAIL_USERNAME')}")
    
    password = app.config.get('MAIL_PASSWORD')
    if password:
        print(f"Mot de passe: {'*' * len(password)} (configuré)")
    else:
        print("Mot de passe: ❌ NON CONFIGURÉ")
    
    print(f"Expéditeur: {app.config.get('MAIL_DEFAULT_SENDER')}")
    print(f"Nom expéditeur: {app.config.get('MAIL_FROM_NAME')}")
    print("="*60)

def main():
    """Fonction principale"""
    print("\n" + "🎯"*30)
    print("JUNGLE ALERT - TEST D'ENVOI D'EMAIL")
    print("🎯"*30)
    
    # Vérifier les arguments de ligne de commande
    if len(sys.argv) < 2:
        print("\n📝 Usage:")
        print(f"   python {sys.argv[0]} <email_destinataire> [type]")
        print("\nOptions de type:")
        print("   1 - Email simple (par défaut)")
        print("   2 - Email de bienvenue")
        print("   3 - Email d'alerte de prix")
        print("   4 - Tous les emails")
        print("\nExemple:")
        print(f"   python {sys.argv[0]} test@example.com 4")
        print("\n" + "="*60 + "\n")
        sys.exit(1)
    
    recipient_email = sys.argv[1].strip()
    
    if not recipient_email or '@' not in recipient_email:
        print("❌ Adresse email invalide!")
        sys.exit(1)
    
    # Type de test (par défaut: 4 - tous les emails)
    choice = sys.argv[2] if len(sys.argv) > 2 else '4'
    
    # Créer l'application Flask
    app = create_app()
    
    with app.app_context():
        # Afficher la configuration
        display_config_info(app)
        
        # Vérifier si la configuration est complète
        if not app.config.get('MAIL_PASSWORD'):
            print("\n⚠️  ATTENTION: Le mot de passe email n'est pas configuré!")
            print("📝 Assurez-vous de configurer MAIL_PASSWORD dans votre fichier .env")
            print("\nExemple dans .env:")
            print("MAIL_USERNAME=votre-email@domain.com")
            print("MAIL_PASSWORD=votre-mot-de-passe")
            return
        
        # Créer le service email
        email_service = EmailService()
        
        # Exécuter les tests selon le choix
        results = []
        
        if choice == '1':
            results.append(test_simple_email(email_service, recipient_email))
        elif choice == '2':
            results.append(test_welcome_email(email_service, recipient_email))
        elif choice == '3':
            results.append(test_price_alert_email(email_service, recipient_email))
        elif choice == '4':
            results.append(test_simple_email(email_service, recipient_email))
            results.append(test_welcome_email(email_service, recipient_email))
            results.append(test_price_alert_email(email_service, recipient_email))
        else:
            print("❌ Choix invalide!")
            return
        
        # Résumé des tests
        print("\n" + "="*60)
        print("RÉSUMÉ DES TESTS")
        print("="*60)
        total = len(results)
        success_count = sum(results)
        failed_count = total - success_count
        
        print(f"Total: {total} test(s)")
        print(f"✅ Réussi: {success_count}")
        print(f"❌ Échoué: {failed_count}")
        
        if failed_count == 0:
            print("\n🎉 Tous les tests ont réussi!")
            print(f"📬 Vérifiez la boîte de réception de {recipient_email}")
        else:
            print("\n⚠️  Certains tests ont échoué. Vérifiez les logs ci-dessus pour plus de détails.")
            print("\n💡 Conseils de dépannage:")
            print("   - Vérifiez vos identifiants SMTP dans le fichier .env")
            print("   - Vérifiez que le serveur SMTP et le port sont corrects")
            print("   - Assurez-vous que l'authentification à deux facteurs est désactivée ou utilisez un mot de passe d'application")
            print("   - Vérifiez que votre fournisseur email autorise les connexions SMTP")
        
        print("="*60 + "\n")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n❌ Test interrompu par l'utilisateur.")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Erreur inattendue: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

