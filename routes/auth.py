from flask import Blueprint, request, jsonify, current_app
from extensions import db
from models.user import User
from flask_jwt_extended import create_access_token, jwt_required, get_jwt_identity
from datetime import datetime

auth_bp = Blueprint('auth', __name__)

@auth_bp.route('/auth/register', methods=['POST'])
def register():
    """
    Register a new user
    ---
    tags:
      - Authentication
    parameters:
      - in: body
        name: body
        required: true
        schema:
          type: object
          required:
            - email
            - username
            - password
            - first_name
            - last_name
          properties:
            email:
              type: string
              format: email
              example: user@example.com
            username:
              type: string
              example: johndoe
            password:
              type: string
              example: SecurePass123!
            first_name:
              type: string
              example: John
            last_name:
              type: string
              example: Doe
    responses:
      201:
        description: User registered successfully
        schema:
          type: object
          properties:
            message:
              type: string
            user:
              type: object
      422:
        description: Validation error
    """
    try:
        data = request.get_json()
        
        # Validation
        if not data.get('email'):
            return jsonify({'message': 'Email is required'}), 422
        if not data.get('username'):
            return jsonify({'message': 'Username is required'}), 422
        if not data.get('password'):
            return jsonify({'message': 'Password is required'}), 422
        if not data.get('first_name'):
            return jsonify({'message': 'First name is required'}), 422
        if not data.get('last_name'):
            return jsonify({'message': 'Last name is required'}), 422
        
        # Check if user exists
        if User.query.filter_by(email=data['email']).first():
            return jsonify({'message': 'Email already exists', 'errors': {'email': ['Email already exists']}}), 422
        
        if User.query.filter_by(username=data['username']).first():
            return jsonify({'message': 'Username already exists', 'errors': {'username': ['Username already exists']}}), 422
        
        # Create user
        user = User(
            email=data['email'],
            username=data['username'],
            first_name=data['first_name'],
            last_name=data['last_name'],
            subscription_tier='FREE',
            is_active=True,
            is_verified=False,
            email_notifications=True,
            whatsapp_notifications=False,
            push_notifications=True,
            gdpr_consent=False,
            data_retention_consent=False,
        )
        user.set_password(data['password'])
        
        db.session.add(user)
        db.session.commit()
        
        # Create access token (identity must be a string)
        access_token = create_access_token(identity=str(user.id))
        
        return jsonify({
            'message': 'User created successfully',
            'user': user.to_dict(),
            'access_token': access_token,
            'token_type': 'Bearer',
        }), 201
        
    except Exception as e:
        db.session.rollback()
        return jsonify({
            'message': 'Failed to create user',
            'error': str(e) if current_app.config.get('DEBUG') else 'An error occurred'
        }), 500

@auth_bp.route('/auth/login', methods=['POST'])
def login():
    """
    Login user and get access token
    ---
    tags:
      - Authentication
    parameters:
      - in: body
        name: body
        required: true
        schema:
          type: object
          required:
            - email
            - password
          properties:
            email:
              type: string
              format: email
              example: user@example.com
            password:
              type: string
              example: SecurePass123!
    responses:
      200:
        description: Login successful
        schema:
          type: object
          properties:
            access_token:
              type: string
            refresh_token:
              type: string
            token_type:
              type: string
            user:
              type: object
      401:
        description: Invalid credentials
      422:
        description: Validation error
    """
    try:
        data = request.get_json()
        
        if not data.get('email') or not data.get('password'):
            return jsonify({'message': 'Email and password are required'}), 422
        
        user = User.query.filter_by(email=data['email']).first()
        
        if not user or not user.check_password(data['password']):
            return jsonify({'message': 'Invalid credentials'}), 401
        
        # Update last login
        user.last_login = datetime.utcnow()
        db.session.commit()
        
        # Create access token (identity must be a string)
        access_token = create_access_token(identity=str(user.id))
        
        return jsonify({
            'access_token': access_token,
            'refresh_token': access_token,  # For simplicity
            'token_type': 'Bearer',
            'user': user.to_dict(),
        }), 200
        
    except Exception as e:
        return jsonify({
            'message': 'Login failed',
            'error': str(e) if current_app.config.get('DEBUG') else 'An error occurred'
        }), 500

@auth_bp.route('/auth/logout', methods=['POST'])
@jwt_required()
def logout():
    """Logout user"""
    # With JWT, logout is handled client-side by removing the token
    return jsonify({'message': 'Logged out successfully'}), 200

@auth_bp.route('/auth/me', methods=['GET'])
@jwt_required()
def me():
    """Get current user"""
    user_id = get_jwt_identity()
    user = User.query.get(user_id)
    
    if not user:
        return jsonify({'message': 'User not found'}), 404
    
    return jsonify(user.to_dict()), 200

