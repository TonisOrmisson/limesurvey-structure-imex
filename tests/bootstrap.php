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
$isVendorEnvironment = getenv('LIMESURVEY_VENDOR_PATH') !== false;
$isUnitTestOnly = getenv('UNIT_TEST_ONLY') === 'true';

// For unit tests in CI that don't need LimeSurvey
if ($isUnitTestOnly && getenv('CI') === 'true') {
    echo "Unit test mode: Skipping LimeSurvey requirement\n";
    // Define minimal constants for unit tests
    if (!defined('LIMESURVEY_PATH')) {
        define('LIMESURVEY_PATH', __DIR__ . '/..');
        define('APPPATH', LIMESURVEY_PATH . '/application/');
        define('BASEPATH', LIMESURVEY_PATH . '/');
    }
} elseif ($hasVendorLimeSurvey && ($isVendorEnvironment || getenv('CI') === 'true')) {
    // CI environment or standalone testing - use plugin's vendor LimeSurvey installation
    echo "Using vendor LimeSurvey installation\n";
    
    $vendorLimeSurveyPath = __DIR__ . '/../vendor/limesurvey/limesurvey';
    
    // Verify vendor LimeSurvey installation is complete
    if (!file_exists($vendorLimeSurveyPath . '/application/config/version.php')) {
        throw new Exception("Vendor LimeSurvey installation incomplete. Missing version.php at: {$vendorLimeSurveyPath}/application/config/version.php");
    }
    
    if (!file_exists($vendorLimeSurveyPath . '/application/config/config.php')) {
        throw new Exception("Vendor LimeSurvey installation incomplete. Missing config.php at: {$vendorLimeSurveyPath}/application/config/config.php");
    }
    
    // Set up LimeSurvey path constants
    define('LIMESURVEY_PATH', $vendorLimeSurveyPath);
    define('APPPATH', LIMESURVEY_PATH . '/application/');
    define('BASEPATH', LIMESURVEY_PATH . '/');
    
    // Include Yii framework from LimeSurvey's vendor
    $yiiPath = LIMESURVEY_PATH . '/vendor/yiisoft/yii/framework/yii.php';
    if (!file_exists($yiiPath)) {
        throw new Exception("Yii framework not found at: {$yiiPath}");
    }
    require_once $yiiPath;
    
    // Disable Yii's autoloader to prevent conflicts with PHPUnit
    Yii::$enableIncludePath = false;
    
    // Set up Yii path aliases for LimeSurvey
    Yii::setPathOfAlias('application', APPPATH);
    Yii::setPathOfAlias('webroot', LIMESURVEY_PATH);
    
    // Import essential LimeSurvey classes (matching LimeSurvey's internal.php)
    Yii::import('application.core.*');
    Yii::import('application.models.*');
    
    // Load our plugin's autoloader from vendor LimeSurvey installation
    $vendorPluginAutoloader = LIMESURVEY_PATH . '/upload/plugins/StructureImEx/vendor/autoload.php';
    if (file_exists($vendorPluginAutoloader)) {
        require_once $vendorPluginAutoloader;
        echo "Loaded plugin autoloader from vendor LimeSurvey\n";
    } else {
        echo "Warning: Plugin autoloader not found at {$vendorPluginAutoloader}\n";
    }
    
} elseif ($isInsideLimeSurvey && !$isVendorEnvironment) {
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
    $vendorPath = __DIR__ . '/../vendor/limesurvey/limesurvey';
    $error = 'Cannot detect LimeSurvey installation. Current environment:';
    $error .= "\n- Inside LimeSurvey: " . ($isInsideLimeSurvey ? 'YES' : 'NO');
    $error .= "\n- Has vendor LimeSurvey: " . ($hasVendorLimeSurvey ? 'YES' : 'NO');
    $error .= "\n- Vendor environment variable: " . ($isVendorEnvironment ? 'YES' : 'NO');
    $error .= "\n- CI environment: " . (getenv('CI') === 'true' ? 'YES' : 'NO');
    $error .= "\n- Vendor path exists: " . (file_exists($vendorPath) ? 'YES' : 'NO');
    if (file_exists($vendorPath)) {
        $error .= "\n- Vendor contents: " . implode(', ', scandir($vendorPath));
        $error .= "\n- Has application: " . (file_exists($vendorPath . '/application') ? 'YES' : 'NO');
        $error .= "\n- Has config: " . (file_exists($vendorPath . '/application/config/config.php') ? 'YES' : 'NO');
    }
    $error .= "\n\nPlease either:";
    $error .= "\n1. Run tests from within a LimeSurvey installation, or";
    $error .= "\n2. Install with LimeSurvey dependency: COMPOSER=composer-ci.json composer install";
    $error .= "\n3. For CI: set LIMESURVEY_VENDOR_PATH environment variable";
    throw new Exception($error);
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

