#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Standalone script for testing email sending - ENGLISH VERSION
No need for Flask or heavy dependencies
"""
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.utils import formataddr
import sys
import os

# Fix encoding for Windows
if sys.platform == 'win32':
    os.system('chcp 65001 > nul')
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8')
    if hasattr(sys.stderr, 'reconfigure'):
        sys.stderr.reconfigure(encoding='utf-8')

# Email configuration (from your parameters)
MAIL_HOST = "smtp.office365.com"
MAIL_PORT = 587
MAIL_USERNAME = "no-reply@pretconnectloan.com"
MAIL_PASSWORD = "X!HXN*2h2fmha9"
MAIL_ENCRYPTION = "tls"
MAIL_FROM_ADDRESS = "no-reply@pretconnectloan.com"
MAIL_FROM_NAME = "Jungle Alert"

def send_test_email(to_email):
    """Send a simple test email"""
    
    print("\n" + "="*70)
    print("🧪 TEST EMAIL SENDING - JUNGLE ALERT")
    print("="*70)
    
    print(f"\n📧 Configuration:")
    print(f"   SMTP Server: {MAIL_HOST}")
    print(f"   Port: {MAIL_PORT}")
    print(f"   Encryption: {MAIL_ENCRYPTION}")
    print(f"   From: {MAIL_FROM_ADDRESS}")
    print(f"   To: {to_email}")
    
    try:
        print("\n⏳ Creating message...")
        
        # Create message
        msg = MIMEMultipart('alternative')
        msg['Subject'] = "🧪 Test Email - Jungle Alert"
        msg['From'] = formataddr((MAIL_FROM_NAME, MAIL_FROM_ADDRESS))
        msg['To'] = to_email
        
        # Email body in text
        text_body = """
🧪 Test Email - Jungle Alert

Hello,

This is a test email sent from Jungle Alert.

✅ If you receive this email, the SMTP configuration is working correctly!

Technical Information:
- Service: Jungle Alert Email Service
- SMTP Server: smtp.office365.com
- Port: 587
- Encryption: TLS
- Date: January 19, 2026

Best regards,
The Jungle Alert Team
        """
        
        # Email body in HTML
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
        <h1>🧪 Test Email</h1>
        <p>Hello,</p>
        
        <p>This is a test email sent from <strong>Jungle Alert</strong>.</p>
        
        <div class="success-box">
            <strong>✅ Status:</strong> If you receive this email, the SMTP configuration is working correctly!
        </div>
        
        <div class="info-box">
            <strong>📌 About Jungle Alert:</strong><br>
            Jungle Alert is your smart assistant to track Amazon prices and receive personalized alerts.
        </div>
        
        <div class="tech-info">
            <strong>Technical Information:</strong>
            <ul style="margin: 10px 0;">
                <li>Service: Jungle Alert Email Service</li>
                <li>SMTP Server: smtp.office365.com</li>
                <li>Port: 587</li>
                <li>Encryption: TLS</li>
                <li>Date: January 19, 2026</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Best regards,<br><strong>The Jungle Alert Team</strong></p>
            <p>&copy; 2026 Jungle Alert. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
        """
        
        # Attach both parts
        part1 = MIMEText(text_body, 'plain', 'utf-8')
        part2 = MIMEText(html_body, 'html', 'utf-8')
        msg.attach(part1)
        msg.attach(part2)
        
        print("✅ Message created successfully")
        
        # Connect to SMTP server
        print(f"\n⏳ Connecting to SMTP server {MAIL_HOST}:{MAIL_PORT}...")
        
        if MAIL_ENCRYPTION.lower() == 'ssl':
            server = smtplib.SMTP_SSL(MAIL_HOST, MAIL_PORT)
        else:
            server = smtplib.SMTP(MAIL_HOST, MAIL_PORT)
            if MAIL_ENCRYPTION.lower() == 'tls':
                print("⏳ Enabling TLS...")
                server.starttls()
        
        print("✅ Connection established")
        
        # Authentication
        print(f"\n⏳ Authenticating with {MAIL_USERNAME}...")
        server.login(MAIL_USERNAME, MAIL_PASSWORD)
        print("✅ Authentication successful")
        
        # Send email
        print(f"\n⏳ Sending email to {to_email}...")
        server.sendmail(MAIL_FROM_ADDRESS, to_email, msg.as_string())
        print("✅ Email sent successfully!")
        
        # Close connection
        server.quit()
        
        print("\n" + "="*70)
        print("🎉 SUCCESS!")
        print("="*70)
        print(f"\n📬 Email sent successfully to {to_email}")
        print("💡 Check your inbox (and spam folder)")
        print("="*70 + "\n")
        
        return True
        
    except Exception as e:
        print(f"\n❌ ERROR: {str(e)}")
        print("="*70 + "\n")
        return False

def send_welcome_email(to_email):
    """Send a welcome email"""
    
    print("\n" + "="*70)
    print("🎉 WELCOME EMAIL TEST")
    print("="*70)
    
    try:
        msg = MIMEMultipart('alternative')
        msg['Subject'] = "Welcome to Jungle Alert! 🎉"
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
        <h1>🎉 Welcome to Jungle Alert!</h1>
    </div>
    <div class="content">
        <p>Hello,</p>
        
        <p>Thank you for signing up with Jungle Alert! We're excited to have you on board.</p>
        
        <h3>What can you do with Jungle Alert?</h3>
        
        <div class="feature">
            <strong>📊 Track Prices</strong><br>
            Monitor price changes for your favorite products on Amazon.
        </div>
        
        <div class="feature">
            <strong>🔔 Receive Alerts</strong><br>
            Get notified instantly when the price reaches your target.
        </div>
        
        <div class="feature">
            <strong>💰 Save Money</strong><br>
            Buy at the right time and save on your purchases.
        </div>
        
        <p>Start adding your first products now!</p>
        
        <div class="footer">
            <p>&copy; 2026 Jungle Alert. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
        """
        
        text_body = """
Welcome to Jungle Alert!

Hello,

Thank you for signing up with Jungle Alert! We're excited to have you on board.

What can you do with Jungle Alert?

- Track Prices: Monitor price changes for your favorite products on Amazon.
- Receive Alerts: Get notified instantly when the price reaches your target.
- Save Money: Buy at the right time and save on your purchases.

Start adding your first products now!

© 2026 Jungle Alert. All rights reserved.
        """
        
        part1 = MIMEText(text_body, 'plain', 'utf-8')
        part2 = MIMEText(html_body, 'html', 'utf-8')
        msg.attach(part1)
        msg.attach(part2)
        
        print("⏳ Connecting and sending...")
        
        if MAIL_ENCRYPTION.lower() == 'ssl':
            server = smtplib.SMTP_SSL(MAIL_HOST, MAIL_PORT)
        else:
            server = smtplib.SMTP(MAIL_HOST, MAIL_PORT)
            if MAIL_ENCRYPTION.lower() == 'tls':
                server.starttls()
        
        server.login(MAIL_USERNAME, MAIL_PASSWORD)
        server.sendmail(MAIL_FROM_ADDRESS, to_email, msg.as_string())
        server.quit()
        
        print("✅ Welcome email sent successfully!")
        print("="*70 + "\n")
        return True
        
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        print("="*70 + "\n")
        return False

def send_price_alert_demo(to_email):
    """Send a demo price alert email"""
    
    print("\n" + "="*70)
    print("📉 PRICE ALERT EMAIL TEST")
    print("="*70)
    
    try:
        msg = MIMEMultipart('alternative')
        msg['Subject'] = "🔔 Price Alert: Apple AirPods Pro"
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
        <p>Your price alert has been triggered!</p>
    </div>
    <div class="content">
        <p>Hello,</p>
        
        <div class="alert-box">
            <div class="product-name">🎧 Apple AirPods Pro (2nd Generation)</div>
            <p><strong>The price has dropped!</strong></p>
            
            <div class="old-price">Previous price: $249.99</div>
            <div class="price">$199.99</div>
            <div class="savings">💰 Save $50.00 (20%)</div>
            
            <p style="margin-top: 20px;">
                Your target price was <strong>$220.00</strong><br>
                The current price is <strong style="color: #28a745;">below your target</strong>! 🎉
            </p>
        </div>
        
        <p>Don't miss this opportunity!</p>
        
        <a href="https://www.amazon.com/dp/B0CHWRXH8B" class="button">View Product on Amazon</a>
        
        <div class="footer">
            <p>You're receiving this email because you set up an alert for this product.</p>
            <p>&copy; 2026 Jungle Alert. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
        """
        
        text_body = """
🔔 Jungle Alert - Price Alert

Hello,

Your price alert has been triggered!

🎧 Product: Apple AirPods Pro (2nd Generation)
The price has dropped!

Previous price: $249.99
Current price: $199.99
💰 Save $50.00 (20%)

Your target price was $220.00
The current price is below your target! 🎉

View product: https://www.amazon.com/dp/B0CHWRXH8B

You're receiving this email because you set up an alert for this product.
© 2026 Jungle Alert. All rights reserved.
        """
        
        part1 = MIMEText(text_body, 'plain', 'utf-8')
        part2 = MIMEText(html_body, 'html', 'utf-8')
        msg.attach(part1)
        msg.attach(part2)
        
        print("⏳ Connecting and sending...")
        
        if MAIL_ENCRYPTION.lower() == 'ssl':
            server = smtplib.SMTP_SSL(MAIL_HOST, MAIL_PORT)
        else:
            server = smtplib.SMTP(MAIL_HOST, MAIL_PORT)
            if MAIL_ENCRYPTION.lower() == 'tls':
                server.starttls()
        
        server.login(MAIL_USERNAME, MAIL_PASSWORD)
        server.sendmail(MAIL_FROM_ADDRESS, to_email, msg.as_string())
        server.quit()
        
        print("✅ Price alert email sent successfully!")
        print("="*70 + "\n")
        return True
        
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        print("="*70 + "\n")
        return False

def main():
    """Main function"""
    print("\n" + "🎯"*35)
    print("JUNGLE ALERT - EMAIL TESTING (ENGLISH VERSION)")
    print("🎯"*35)
    
    # Check command line arguments
    if len(sys.argv) < 2:
        print("\n📝 Usage:")
        print(f"   python {sys.argv[0]} <recipient_email> [type]")
        print("\nType options:")
        print("   1 - Simple test email")
        print("   2 - Welcome email")
        print("   3 - Price alert email")
        print("   4 - All emails (default)")
        print("\nExample:")
        print(f"   python {sys.argv[0]} test@example.com 4")
        print("\n" + "="*70 + "\n")
        sys.exit(1)
    
    recipient_email = sys.argv[1].strip()
    
    if not recipient_email or '@' not in recipient_email:
        print("❌ Invalid email address!")
        sys.exit(1)
    
    # Test type (default: 4 - all emails)
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
        print("❌ Invalid choice! Use 1, 2, 3 or 4")
        sys.exit(1)
    
    # Summary
    print("\n" + "="*70)
    print("📊 TEST SUMMARY")
    print("="*70)
    success_count = sum(results)
    total = len(results)
    
    print(f"Total: {total} test(s)")
    print(f"✅ Success: {success_count}")
    print(f"❌ Failed: {total - success_count}")
    
    if success_count == total:
        print("\n🎉 All tests passed!")
        print(f"📬 Check the inbox of {recipient_email}")
    
    print("="*70 + "\n")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n❌ Test interrupted by user.\n")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Unexpected error: {str(e)}\n")
        import traceback
        traceback.print_exc()
        sys.exit(1)


