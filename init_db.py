#!/usr/bin/env python
"""Script pour initialiser la base de données"""
from app import create_app
from extensions import db
import os

def init_database():
    """Initialise la base de données"""
    app = create_app('development')
    
    with app.app_context():
        # Créer toutes les tables
        db.create_all()
        print("✅ Base de données initialisée avec succès!")
        print("📊 Tables créées:")
        for table in db.metadata.tables:
            print(f"   - {table}")

if __name__ == '__main__':
    init_database()












