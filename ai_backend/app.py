"""
Property Marketplace AI Backend
Python Flask API for AI Features
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import pandas as pd
from datetime import datetime
import re
import os
from PIL import Image
import io
import base64

app = Flask(__name__)
CORS(app)

# Configuration
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max file size

# ==================== AI FEATURE 1: Fraud Detection ====================

def detect_fraud(property_data):
    """
    Detect potential fraudulent property listings
    Returns fraud score and risk factors
    """
    risk_score = 0
    risk_factors = []
    
    # Check for suspiciously low price
    if property_data.get('price', 0) < 100000:
        risk_score += 30
        risk_factors.append("Suspiciously low price")
    
    # Check for missing critical information
    required_fields = ['title', 'description', 'area_size', 'price']
    missing_fields = [field for field in required_fields if not property_data.get(field)]
    if missing_fields:
        risk_score += len(missing_fields) * 10
        risk_factors.append(f"Missing fields: {', '.join(missing_fields)}")
    
    # Check for generic description
    description = property_data.get('description', '')
    if len(description) < 50:
        risk_score += 20
        risk_factors.append("Too short description")
    
    # Check for suspicious keywords
    suspicious_keywords = ['urgent', 'immediate', 'cash only', 'no documents', 'secret']
    for keyword in suspicious_keywords:
        if keyword in description.lower():
            risk_score += 15
            risk_factors.append(f"Suspicious keyword: '{keyword}'")
    
    # Normalize score to 0-100
    fraud_score = min(risk_score, 100)
    
    return {
        'fraud_score': fraud_score,
        'risk_level': 'high' if fraud_score > 60 else 'medium' if fraud_score > 30 else 'low',
        'risk_factors': risk_factors,
        'is_suspicious': fraud_score > 50
    }


# ==================== AI FEATURE 2: Duplicate Detection ====================

def detect_duplicates(property_data, existing_properties):
    """
    Detect duplicate or similar property listings
    """
    duplicates = []
    
    title = property_data.get('title', '').lower()
    price = property_data.get('price', 0)
    area = property_data.get('area_size', 0)
    
    for prop in existing_properties:
        similarity_score = 0
        
        # Title similarity
        prop_title = prop.get('title', '').lower()
        words_in_title = set(title.split())
        words_in_prop = set(prop_title.split())
        
        if words_in_title & words_in_prop:
            similarity_score += len(words_in_title & words_in_prop) * 10
        
        # Price similarity (within 5%)
        prop_price = prop.get('price', 0)
        if price > 0 and prop_price > 0:
            price_diff = abs(price - prop_price) / price
            if price_diff < 0.05:
                similarity_score += 30
        
        # Area similarity (within 10%)
        prop_area = prop.get('area_size', 0)
        if area > 0 and prop_area > 0:
            area_diff = abs(area - prop_area) / area
            if area_diff < 0.10:
                similarity_score += 20
        
        # Location similarity
        if (property_data.get('city_id') == prop.get('city_id') and 
            property_data.get('area_id') == prop.get('area_id')):
            similarity_score += 40
        
        if similarity_score >= 50:
            duplicates.append({
                'property_id': prop.get('id'),
                'similarity_score': similarity_score,
                'title': prop.get('title'),
                'price': prop.get('price')
            })
    
    return {
        'has_duplicates': len(duplicates) > 0,
        'duplicate_count': len(duplicates),
        'duplicates': duplicates[:5]  # Return top 5
    }


# ==================== AI FEATURE 3: Property Description Generator ====================

def generate_description(property_data):
    """
    Generate a professional property description based on property features
    """
    property_type = property_data.get('type_name', 'property')
    area = property_data.get('area_size', 0)
    area_unit = property_data.get('area_unit', 'sqft')
    bhk = property_data.get('bhk', 0)
    city = property_data.get('city_name', '')
    
    description = f"Spacious {property_type} located in {city}. "
    
    if bhk > 0:
        description += f"This {bhk} BHK property offers "
    else:
        description += f"This property offers "
    
    description += f"{area} {area_unit.upper()} of well-designed living space. "
    
    # Add amenities
    amenities = property_data.get('amenities', [])
    if amenities:
        description += f"The property features {', '.join(amenities[:5])}. "
    
    # Add furnishing
    furnishing = property_data.get('furnishing', '')
    if furnishing:
        description += f"It comes {furnishing.replace('_', ' ')}. "
    
    # Add construction status
    construction = property_data.get('construction_status', '')
    if construction:
        description += f"Property is {construction.replace('_', ' ')}. "
    
    description += "Ideal for families looking for a comfortable and convenient lifestyle."
    
    return {
        'generated_description': description,
        'word_count': len(description.split())
    }


# ==================== AI FEATURE 4: Image Quality Check ====================

def check_image_quality(image_base64):
    """
    Check image quality and provide recommendations
    """
    try:
        # Decode base64 image
        image_data = base64.b64decode(image_base64)
        image = Image.open(io.BytesIO(image_data))
        
        width, height = image.size
        quality_score = 100
        issues = []
        
        # Check resolution
        if width < 800 or height < 600:
            quality_score -= 30
            issues.append("Low resolution (recommended: 800x600 or higher)")
        
        # Check aspect ratio
        aspect_ratio = width / height
        if aspect_ratio < 0.5 or aspect_ratio > 2:
            quality_score -= 20
            issues.append("Unusual aspect ratio (recommended: 4:3 or 16:9)")
        
        # Check if image is too small
        if width < 400 or height < 300:
            quality_score -= 40
            issues.append("Image too small for good display")
        
        # Check file size (approximate from base64 length)
        file_size = len(image_data) / 1024  # KB
        if file_size < 50:
            quality_score -= 20
            issues.append("Image might be heavily compressed")
        
        return {
            'quality_score': max(quality_score, 0),
            'quality_level': 'excellent' if quality_score > 80 else 'good' if quality_score > 60 else 'poor',
            'resolution': f"{width}x{height}",
            'file_size_kb': round(file_size, 2),
            'issues': issues,
            'is_acceptable': quality_score >= 50
        }
    except Exception as e:
        return {
            'error': str(e),
            'quality_score': 0,
            'is_acceptable': False
        }


# ==================== AI FEATURE 5: Property Recommendations ====================

def recommend_properties(user_preferences, all_properties):
    """
    Recommend properties based on user preferences
    """
    recommendations = []
    
    preferred_type = user_preferences.get('property_type', '')
    preferred_city = user_preferences.get('city', '')
    min_price = user_preferences.get('min_price', 0)
    max_price = user_preferences.get('max_price', float('inf'))
    min_area = user_preferences.get('min_area', 0)
    
    for prop in all_properties:
        score = 0
        
        # Type match
        if preferred_type and prop.get('type_slug') == preferred_type:
            score += 30
        
        # City match
        if preferred_city and prop.get('city_name') == preferred_city:
            score += 25
        
        # Price range
        price = prop.get('price', 0)
        if min_price <= price <= max_price:
            score += 20
        
        # Area match
        area = prop.get('area_size', 0)
        if area >= min_area:
            score += 15
        
        # Featured/Verified bonus
        if prop.get('is_featured'):
            score += 5
        if prop.get('is_verified'):
            score += 5
        
        if score > 0:
            recommendations.append({
                'property_id': prop.get('id'),
                'title': prop.get('title'),
                'score': score,
                'price': price,
                'city': prop.get('city_name')
            })
    
    # Sort by score and return top 10
    recommendations.sort(key=lambda x: x['score'], reverse=True)
    
    return {
        'recommendations': recommendations[:10],
        'total_found': len(recommendations)
    }


# ==================== AI FEATURE 6: Price Estimation ====================

def estimate_price(property_features):
    """
    Estimate property price based on features
    Simple linear regression-based estimation
    """
    # Base prices per property type (in INR)
    base_prices = {
        'flat-apartment': 5000,
        'house-villa': 6000,
        'plot-land': 3000,
        'commercial': 8000
    }
    
    property_type = property_features.get('type_slug', 'flat-apartment')
    area = property_features.get('area_size', 1000)
    city = property_features.get('city_name', '').lower()
    
    # Base price per sqft
    base_price_per_sqft = base_prices.get(property_type, 5000)
    
    # City multiplier
    city_multipliers = {
        'mumbai': 2.5,
        'delhi': 2.0,
        'bangalore': 1.8,
        'pune': 1.5,
        'hyderabad': 1.4
    }
    
    city_multiplier = 1.0
    for city_name, multiplier in city_multipliers.items():
        if city_name in city:
            city_multiplier = multiplier
            break
    
    # BHK multiplier
    bhk = property_features.get('bhk', 2)
    bhk_multiplier = 1 + (bhk * 0.1)
    
    # Furnishing multiplier
    furnishing = property_features.get('furnishing', 'unfurnished')
    furnishing_multipliers = {
        'unfurnished': 1.0,
        'semi_furnished': 1.15,
        'fully_furnished': 1.3
    }
    furnishing_multiplier = furnishing_multipliers.get(furnishing, 1.0)
    
    # Calculate estimated price
    estimated_price = (area * base_price_per_sqft * 
                      city_multiplier * 
                      bhk_multiplier * 
                      furnishing_multiplier)
    
    # Add confidence interval (±15%)
    min_price = estimated_price * 0.85
    max_price = estimated_price * 1.15
    
    return {
        'estimated_price': round(estimated_price, 2),
        'price_range': {
            'min': round(min_price, 2),
            'max': round(max_price, 2)
        },
        'price_per_sqft': round(estimated_price / area, 2) if area > 0 else 0,
        'confidence': 'medium'
    }


# ==================== API ROUTES ====================

@app.route('/api/ai/fraud-detection', methods=['POST'])
def fraud_detection_api():
    """API endpoint for fraud detection"""
    try:
        property_data = request.json
        result = detect_fraud(property_data)
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/ai/duplicate-detection', methods=['POST'])
def duplicate_detection_api():
    """API endpoint for duplicate detection"""
    try:
        data = request.json
        property_data = data.get('property', {})
        existing_properties = data.get('existing_properties', [])
        result = detect_duplicates(property_data, existing_properties)
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/ai/generate-description', methods=['POST'])
def generate_description_api():
    """API endpoint for description generation"""
    try:
        property_data = request.json
        result = generate_description(property_data)
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/ai/image-quality', methods=['POST'])
def image_quality_api():
    """API endpoint for image quality check"""
    try:
        data = request.json
        image_base64 = data.get('image')
        result = check_image_quality(image_base64)
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/ai/recommendations', methods=['POST'])
def recommendations_api():
    """API endpoint for property recommendations"""
    try:
        data = request.json
        user_preferences = data.get('preferences', {})
        all_properties = data.get('properties', [])
        result = recommend_properties(user_preferences, all_properties)
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/ai/price-estimation', methods=['POST'])
def price_estimation_api():
    """API endpoint for price estimation"""
    try:
        property_features = request.json
        result = estimate_price(property_features)
        return jsonify(result)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/ai/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'timestamp': datetime.now().isoformat(),
        'version': '1.0.0'
    })


# ==================== MAIN ====================

if __name__ == '__main__':
    print("Starting Property Marketplace AI Backend...")
    print("Available endpoints:")
    print("  - POST /api/ai/fraud-detection")
    print("  - POST /api/ai/duplicate-detection")
    print("  - POST /api/ai/generate-description")
    print("  - POST /api/ai/image-quality")
    print("  - POST /api/ai/recommendations")
    print("  - POST /api/ai/price-estimation")
    print("  - GET  /api/ai/health")
    
    app.run(host='0.0.0.0', port=5000, debug=True)
