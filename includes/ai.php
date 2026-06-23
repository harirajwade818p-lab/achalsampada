<?php
/**
 * AI Integration Helper
 * Functions to call Python Flask AI Backend
 */

// AI Backend URL
define('AI_BACKEND_URL', 'http://localhost:5000');

/**
 * Call AI Backend API
 */
function callAI($endpoint, $data) {
    $url = AI_BACKEND_URL . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => 'AI service unavailable', 'http_code' => $httpCode];
    }
    
    return json_decode($response, true);
}

/**
 * Detect fraud in property listing
 */
function detectPropertyFraud($propertyData) {
    return callAI('/api/ai/fraud-detection', $propertyData);
}

/**
 * Detect duplicate properties
 */
function detectDuplicateProperties($propertyData, $existingProperties) {
    return callAI('/api/ai/duplicate-detection', [
        'property' => $propertyData,
        'existing_properties' => $existingProperties
    ]);
}

/**
 * Generate property description
 */
function generatePropertyDescription($propertyData) {
    return callAI('/api/ai/generate-description', $propertyData);
}

/**
 * Check image quality
 */
function checkImageQuality($imagePath) {
    // Convert image to base64
    $imageData = file_get_contents(BASE_PATH . $imagePath);
    $base64 = base64_encode($imageData);
    
    return callAI('/api/ai/image-quality', ['image' => $base64]);
}

/**
 * Get property recommendations
 */
function getPropertyRecommendations($userPreferences, $allProperties) {
    return callAI('/api/ai/recommendations', [
        'preferences' => $userPreferences,
        'properties' => $allProperties
    ]);
}

/**
 * Estimate property price
 */
function estimatePropertyPrice($propertyFeatures) {
    return callAI('/api/ai/price-estimation', $propertyFeatures);
}

/**
 * Check if AI backend is healthy
 */
function isAIHealthy() {
    $ch = curl_init(AI_BACKEND_URL . '/api/ai/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}
?>
