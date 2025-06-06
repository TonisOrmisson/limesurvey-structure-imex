<?php

// Bootstrap file for PHPUnit tests
// This sets up the LimeSurvey environment for testing in both development and CI environments

// Prevent session issues during testing (from LimeSurvey's own bootstrap)
ob_start();

// Include our plugin's composer autoloader first (for OpenSpout, etc.)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Detect environment and setup accordingly
$isInsideLimeSurvey = file_exists(__DIR__ . '/../../../../application/config/version.php');
$hasVendorLimeSurvey = file_exists(__DIR__ . '/../vendor/limesurvey/limesurvey');

if ($hasVendorLimeSurvey) {
    // CI environment or standalone testing - use plugin's vendor LimeSurvey installation
    echo "Using vendor LimeSurvey installation\n";
    
    // Set up LimeSurvey path constants
    define('LIMESURVEY_PATH', __DIR__ . '/../vendor/limesurvey/limesurvey');
    define('APPPATH', LIMESURVEY_PATH . '/application/');
    define('BASEPATH', LIMESURVEY_PATH . '/');
    
    // Include Yii framework from LimeSurvey's vendor
    require_once LIMESURVEY_PATH . '/vendor/yiisoft/yii/framework/yii.php';
    
    // Disable Yii's autoloader to prevent conflicts with PHPUnit
    Yii::$enableIncludePath = false;
    
    // Set up Yii path aliases for LimeSurvey
    Yii::setPathOfAlias('application', APPPATH);
    Yii::setPathOfAlias('webroot', LIMESURVEY_PATH);
    
    // Import essential LimeSurvey classes (matching LimeSurvey's internal.php)
    Yii::import('application.core.*');
    Yii::import('application.models.*');
    
} elseif ($isInsideLimeSurvey) {
    // Development environment - use parent LimeSurvey installation
    echo "Development environment: Using parent LimeSurvey installation\n";
    
    // Set up LimeSurvey path constants pointing to parent installation
    define('LIMESURVEY_PATH', realpath(__DIR__ . '/../../../../'));
    define('APPPATH', LIMESURVEY_PATH . '/application/');
    define('BASEPATH', LIMESURVEY_PATH . '/');
    
    // Include parent LimeSurvey's vendor autoloader (includes Yii and LimeSurvey models)
    if (file_exists(LIMESURVEY_PATH . '/vendor/autoload.php')) {
        require_once LIMESURVEY_PATH . '/vendor/autoload.php';
    }
    
    // Include Yii framework from parent LimeSurvey's vendor
    if (file_exists(LIMESURVEY_PATH . '/vendor/yiisoft/yii/framework/yii.php')) {
        require_once LIMESURVEY_PATH . '/vendor/yiisoft/yii/framework/yii.php';
    }
    
    // Disable Yii's autoloader to prevent conflicts with PHPUnit
    Yii::$enableIncludePath = false;
    
    // Set up Yii path aliases for LimeSurvey
    Yii::setPathOfAlias('application', APPPATH);
    Yii::setPathOfAlias('webroot', LIMESURVEY_PATH);
    
    // Import essential LimeSurvey classes (matching LimeSurvey's internal.php)
    Yii::import('application.core.*');
    Yii::import('application.models.*');
    
    
} else {
    throw new Exception('Cannot detect LimeSurvey installation. Please either:
1. Run tests from within a LimeSurvey installation, or 
2. Install with LimeSurvey dependency: COMPOSER=composer-ci.json composer install');
}

// Set up basic constants that LimeSurvey expects
if (!defined('YII_DEBUG')) {
    define('YII_DEBUG', true);
}

if (!defined('YII_TRACE_LEVEL')) {
    define('YII_TRACE_LEVEL', 0);
}

// Set up test environment
ini_set('memory_limit', '512M');
date_default_timezone_set('UTC');

// LimeSurvey classes should now be available - no mocks needed

