#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Script de test standalone pour l'envoi d'emails
Ne nécessite pas Flask ni les dépendances lourdes
"""
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.utils import formataddr
import sys
import os

# Fixer l'encodage pour Windows
if sys.platform == 'win32':
    os.system('chcp 65001 > nul')
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8')

# Configuration email (à partir de vos paramètres)
MAIL_HOST = "smtp.office365.com"
MAIL_PORT = 587
MAIL_USERNAME = "no-reply@pretconnectloan.com"
MAIL_PASSWORD = "X!HXN*2h2fmha9"
MAIL_ENCRYPTION = "tls"
MAIL_FROM_ADDRESS = "no-reply@pretconnectloan.com"
MAIL_FROM_NAME = "Jungle Alert"

def send_test_email(to_email):
    """Envoie un email de test simple"""
    
    print("\n" + "="*70)
    print("🧪 TEST D'ENVOI D'EMAIL - JUNGLE ALERT")
    print("="*70)
    
    print(f"\n📧 Configuration:")
    print(f"   Serveur SMTP: {MAIL_HOST}")
    print(f"   Port: {MAIL_PORT}")
    print(f"   Encryption: {MAIL_ENCRYPTION}")
    print(f"   Expéditeur: {MAIL_FROM_ADDRESS}")
    print(f"   Destinataire: {to_email}")
    
    try:
        print("\n⏳ Création du message...")
        
        # Créer le message
        msg = MIMEMultipart('alternative')
        msg['Subject'] = "🧪 Test Email - Jungle Alert"
        msg['From'] = formataddr((MAIL_FROM_NAME, MAIL_FROM_ADDRESS))
        msg['To'] = to_email
        
        # Corps de l'email en texte
        text_body = """
🧪 Test d'envoi d'email - Jungle Alert

Bonjour,

Ceci est un email de test envoyé depuis Jungle Alert.

✅ Si vous recevez cet email, la configuration SMTP fonctionne correctement!

Informations techniques:
- Service: Jungle Alert Email Service
- Serveur SMTP: smtp.office365.com
- Port: 587
- Encryption: TLS
- Date: 2026-01-19

Cordialement,
L'équipe Jungle Alert
        """
        
        # Corps de l'email en HTML
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
            border-radius: 5px;
        }
        .tech-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test d'envoi d'email</h1>
        <p>Bonjour,</p>
        
        <p>Ceci est un email de test envoyé depuis <strong>Jungle Alert</strong>.</p>
        
        <div class="success-box">
            <strong>✅ Statut:</strong> Si vous recevez cet email, la configuration SMTP fonctionne correctement!
        </div>
        
        <div class="info-box">
            <strong>📌 À propos de Jungle Alert:</strong><br>
            Jungle Alert est votre assistant intelligent pour suivre les prix sur Amazon et recevoir des alertes personnalisées.
        </div>
        
        <div class="tech-info">
            <strong>Informations techniques:</strong>
            <ul style="margin: 10px 0;">
                <li>Service: Jungle Alert Email Service</li>
                <li>Serveur SMTP: smtp.office365.com</li>
                <li>Port: 587</li>
                <li>Encryption: TLS</li>
                <li>Date: 2026-01-19</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Cordialement,<br><strong>L'équipe Jungle Alert</strong></p>
            <p>&copy; 2026 Jungle Alert. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
        """
        
        # Attacher les deux parties
        part1 = MIMEText(text_body, 'plain', 'utf-8')
        part2 = MIMEText(html_body, 'html', 'utf-8')
        msg.attach(part1)
        msg.attach(part2)
        
        print("✅ Message créé avec succès")
        
        # Se connecter au serveur SMTP
        print(f"\n⏳ Connexion au serveur SMTP {MAIL_HOST}:{MAIL_PORT}...")
        
        if MAIL_ENCRYPTION.lower() == 'ssl':
            server = smtplib.SMTP_SSL(MAIL_HOST, MAIL_PORT)
        else:
            server = smtplib.SMTP(MAIL_HOST, MAIL_PORT)
            if MAIL_ENCRYPTION.lower() == 'tls':
                print("⏳ Activation de TLS...")
                server.starttls()
        
        print("✅ Connexion établie")
        
        # Authentification
        print(f"\n⏳ Authentification avec {MAIL_USERNAME}...")
        server.login(MAIL_USERNAME, MAIL_PASSWORD)
        print("✅ Authentification réussie")
        
        # Envoyer l'email
        print(f"\n⏳ Envoi de l'email à {to_email}...")
        server.sendmail(MAIL_FROM_ADDRESS, to_email, msg.as_string())
        print("✅ Email envoyé avec succès!")
        
        # Fermer la connexion
        server.quit()
        
        print("\n" + "="*70)
        print("🎉 SUCCÈS!")
        print("="*70)
        print(f"\n📬 L'email a été envoyé avec succès à {to_email}")
        print("💡 Vérifiez votre boîte de réception (et le dossier spam)")
        print("="*70 + "\n")
        
        return True
        
    except smtplib.SMTPAuthenticationError as e:
        print("\n❌ ERREUR D'AUTHENTIFICATION")
        print("="*70)
        print("Le nom d'utilisateur ou le mot de passe est incorrect.")
        print("\n💡 Conseils:")
        print("   - Vérifiez vos identifiants Office 365")
        print("   - Si vous avez l'authentification à deux facteurs activée,")
        print("     vous devez utiliser un mot de passe d'application")
        print(f"\nDétails: {str(e)}")
        print("="*70 + "\n")
        return False
        
    except smtplib.SMTPException as e:
        print("\n❌ ERREUR SMTP")
        print("="*70)
        print(f"Une erreur SMTP s'est produite: {str(e)}")
        print("="*70 + "\n")
        return False
        
    except Exception as e:
        print("\n❌ ERREUR INATTENDUE")
        print("="*70)
        print(f"Une erreur inattendue s'est produite: {str(e)}")
        print("\n💡 Conseils de dépannage:")
        print("   - Vérifiez votre connexion Internet")
        print("   - Vérifiez que le serveur SMTP et le port sont corrects")
        print("   - Vérifiez votre pare-feu")
        print("="*70 + "\n")
        import traceback
        traceback.print_exc()
        return False

def send_welcome_email(to_email):
    """Envoie un email de bienvenue"""
    
    print("\n" + "="*70)
    print("🎉 TEST D'EMAIL DE BIENVENUE")
    print("="*70)
    
    try:
        msg = MIMEMultipart('alternative')
        msg['Subject'] = "Bienvenue sur Jungle Alert! 🎉"
        msg['From'] = formataddr((MAIL_FROM_NAME, MAIL_FROM_ADDRESS))
        msg['To'] = to_email
        
        html_body = """
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .feature {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Bienvenue sur Jungle Alert!</h1>
    </div>
    <div class="content">
        <p>Bonjour Utilisateur Test,</p>
        
        <p>Merci de vous être inscrit sur Jungle Alert! Nous sommes ravis de vous compter parmi nous.</p>
        
        <h3>Que pouvez-vous faire avec Jungle Alert?</h3>
        
        <div class="feature">
            <strong>📊 Suivre les prix</strong><br>
            Surveillez l'évolution des prix de vos produits préférés sur Amazon.
        </div>
        
        <div class="feature">
            <strong>🔔 Recevoir des alertes</strong><br>
            Soyez notifié instantanément lorsque le prix atteint votre cible.
        </div>
        
        <div class="feature">
            <strong>💰 Économiser</strong><br>
            Achetez au meilleur moment et économisez sur vos achats.
        </div>
        
        <p>Commencez dès maintenant à ajouter vos premiers produits!</p>
        
        <div class="footer">
            <p>&copy; 2026 Jungle Alert. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
        """
        
        text_body = """
Bienvenue sur Jungle Alert!

Bonjour Utilisateur Test,

Merci de vous être inscrit sur Jungle Alert! Nous sommes ravis de vous compter parmi nous.

Que pouvez-vous faire avec Jungle Alert?

- Suivre les prix: Surveillez l'évolution des prix de vos produits préférés sur Amazon.
- Recevoir des alertes: Soyez notifié instantanément lorsque le prix atteint votre cible.
- Économiser: Achetez au meilleur moment et économisez sur vos achats.

Commencez dès maintenant à ajouter vos premiers produits!

© 2026 Jungle Alert. Tous droits réservés.
        """
        
        part1 = MIMEText(text_body, 'plain', 'utf-8')
        part2 = MIMEText(html_body, 'html', 'utf-8')
        msg.attach(part1)
        msg.attach(part2)
        
        print("⏳ Connexion et envoi...")
        
        if MAIL_ENCRYPTION.lower() == 'ssl':
            server = smtplib.SMTP_SSL(MAIL_HOST, MAIL_PORT)
        else:
            server = smtplib.SMTP(MAIL_HOST, MAIL_PORT)
            if MAIL_ENCRYPTION.lower() == 'tls':
                server.starttls()
        
        server.login(MAIL_USERNAME, MAIL_PASSWORD)
        server.sendmail(MAIL_FROM_ADDRESS, to_email, msg.as_string())
        server.quit()
        
        print("✅ Email de bienvenue envoyé avec succès!")
        print("="*70 + "\n")
        return True
        
    except Exception as e:
        print(f"❌ Erreur: {str(e)}")
        print("="*70 + "\n")
        return False

def send_price_alert_demo(to_email):
    """Envoie un email de démonstration d'alerte de prix"""
    
    print("\n" + "="*70)
    print("📉 TEST D'EMAIL D'ALERTE DE PRIX")
    print("="*70)
    
    try:
        msg = MIMEMultipart('alternative')
        msg['Subject'] = "🔔 Alerte Prix: Apple AirPods Pro"
        msg['From'] = formataddr((MAIL_FROM_NAME, MAIL_FROM_ADDRESS))
        msg['To'] = to_email
        
        html_body = """
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .alert-box {
            background: white;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .product-name {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .price {
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
            margin: 10px 0;
        }
        .old-price {
            font-size: 18px;
            color: #999;
            text-decoration: line-through;
        }
        .savings {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📉 Jungle Alert</h1>
        <p>Votre alerte de prix a été déclenchée!</p>
    </div>
    <div class="content">
        <p>Bonjour,</p>
        
        <div class="alert-box">
            <div class="product-name">🎧 Apple AirPods Pro (2ème génération)</div>
            <p><strong>Le prix a baissé!</strong></p>
            
            <div class="old-price">Prix précédent: 249,99€</div>
            <div class="price">199,99€</div>
            <div class="savings">💰 Économisez 50,00€ (20%)</div>
            
            <p style="margin-top: 20px;">
                Votre prix cible était de <strong>220,00€</strong><br>
                Le prix actuel est <strong style="color: #28a745;">en dessous de votre cible</strong>! 🎉
            </p>
        </div>
        
        <p>Ne manquez pas cette opportunité!</p>
        
        <a href="https://www.amazon.fr/dp/B0CHWRXH8B" class="button">Voir le produit sur Amazon</a>
        
        <div class="footer">
            <p>Vous recevez cet email car vous avez configuré une alerte pour ce produit.</p>
            <p>&copy; 2026 Jungle Alert. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
        """
        
        text_body = """
🔔 Jungle Alert - Alerte Prix

Bonjour,

Votre alerte de prix a été déclenchée!

🎧 Produit: Apple AirPods Pro (2ème génération)
Le prix a baissé!

Prix précédent: 249,99€
Prix actuel: 199,99€
💰 Économisez 50,00€ (20%)

Votre prix cible était de 220,00€
Le prix actuel est en dessous de votre cible! 🎉

Voir le produit: https://www.amazon.fr/dp/B0CHWRXH8B

Vous recevez cet email car vous avez configuré une alerte pour ce produit.
© 2026 Jungle Alert. Tous droits réservés.
        """
        
        part1 = MIMEText(text_body, 'plain', 'utf-8')
        part2 = MIMEText(html_body, 'html', 'utf-8')
        msg.attach(part1)
        msg.attach(part2)
        
        print("⏳ Connexion et envoi...")
        
        if MAIL_ENCRYPTION.lower() == 'ssl':
            server = smtplib.SMTP_SSL(MAIL_HOST, MAIL_PORT)
        else:
            server = smtplib.SMTP(MAIL_HOST, MAIL_PORT)
            if MAIL_ENCRYPTION.lower() == 'tls':
                server.starttls()
        
        server.login(MAIL_USERNAME, MAIL_PASSWORD)
        server.sendmail(MAIL_FROM_ADDRESS, to_email, msg.as_string())
        server.quit()
        
        print("✅ Email d'alerte de prix envoyé avec succès!")
        print("="*70 + "\n")
        return True
        
    except Exception as e:
        print(f"❌ Erreur: {str(e)}")
        print("="*70 + "\n")
        return False

def main():
    """Fonction principale"""
    print("\n" + "🎯"*35)
    print("JUNGLE ALERT - TEST D'ENVOI D'EMAIL (VERSION STANDALONE)")
    print("🎯"*35)
    
    # Vérifier les arguments de ligne de commande
    if len(sys.argv) < 2:
        print("\n📝 Usage:")
        print(f"   python {sys.argv[0]} <email_destinataire> [type]")
        print("\nOptions de type:")
        print("   1 - Email de test simple")
        print("   2 - Email de bienvenue")
        print("   3 - Email d'alerte de prix")
        print("   4 - Tous les emails (par défaut)")
        print("\nExemple:")
        print(f"   python {sys.argv[0]} test@example.com 4")
        print("\n" + "="*70 + "\n")
        sys.exit(1)
    
    recipient_email = sys.argv[1].strip()
    
    if not recipient_email or '@' not in recipient_email:
        print("❌ Adresse email invalide!")
        sys.exit(1)
    
    # Type de test (par défaut: 4 - tous les emails)
    choice = sys.argv[2] if len(sys.argv) > 2 else '4'
    
    results = []
    
    if choice == '1':
        results.append(send_test_email(recipient_email))
    elif choice == '2':
        results.append(send_welcome_email(recipient_email))
    elif choice == '3':
        results.append(send_price_alert_demo(recipient_email))
    elif choice == '4':
        results.append(send_test_email(recipient_email))
        results.append(send_welcome_email(recipient_email))
        results.append(send_price_alert_demo(recipient_email))
    else:
        print("❌ Choix invalide! Utilisez 1, 2, 3 ou 4")
        sys.exit(1)
    
    # Résumé
    print("\n" + "="*70)
    print("📊 RÉSUMÉ DES TESTS")
    print("="*70)
    success_count = sum(results)
    total = len(results)
    
    print(f"Total: {total} test(s)")
    print(f"✅ Réussi: {success_count}")
    print(f"❌ Échoué: {total - success_count}")
    
    if success_count == total:
        print("\n🎉 Tous les tests ont réussi!")
        print(f"📬 Vérifiez la boîte de réception de {recipient_email}")
    
    print("="*70 + "\n")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n❌ Test interrompu par l'utilisateur.\n")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Erreur inattendue: {str(e)}\n")
        import traceback
        traceback.print_exc()
        sys.exit(1)

