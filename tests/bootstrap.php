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

// Check if we're running unit tests - if so, skip LimeSurvey entirely
$isUnitTestRun = false;
if (isset($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $arg) {
        if (strpos($arg, '--testsuite=unit') !== false) {
            $isUnitTestRun = true;
            break;
        }
    }
}
if (getenv('UNIT_TEST_ONLY')) {
    $isUnitTestRun = true;
}

// For unit tests - load minimal LimeSurvey without plugin system
if ($isUnitTestRun || ($isUnitTestOnly && getenv('CI') === 'true')) {
    echo "Unit test mode: Loading minimal LimeSurvey\n";
    
    if ($hasVendorLimeSurvey) {
        $vendorLimeSurveyPath = __DIR__ . '/../vendor/limesurvey/limesurvey';
        
        // Set up LimeSurvey path constants
        define('LIMESURVEY_PATH', $vendorLimeSurveyPath);
        define('APPPATH', LIMESURVEY_PATH . '/application/');
        define('BASEPATH', LIMESURVEY_PATH . '/');
        
        // Include Yii framework from LimeSurvey's vendor
        $yiiPath = LIMESURVEY_PATH . '/vendor/yiisoft/yii/framework/yii.php';
        if (file_exists($yiiPath)) {
            require_once $yiiPath;
            
            // Disable Yii's autoloader to prevent conflicts with PHPUnit
            Yii::$enableIncludePath = false;
            
            // Set up Yii path aliases for LimeSurvey
            Yii::setPathOfAlias('application', APPPATH);
            Yii::setPathOfAlias('webroot', LIMESURVEY_PATH);
            
            // Import only essential LimeSurvey classes - NO plugin system
            Yii::import('application.core.*');
            Yii::import('application.models.*');
            
            echo "Loaded essential LimeSurvey classes for unit tests\n";
        }
    } else {
        // Fallback minimal setup
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
    
    // Note: config.php is created during setup, not part of the package
    // For unit tests, we don't need database access so config.php is not required
    
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
    // Skip this for unit tests to avoid plugin initialization
    $isUnitTestRun = false;
    
    // Check if we're running unit tests specifically
    if (isset($_SERVER['argv'])) {
        foreach ($_SERVER['argv'] as $arg) {
            if (strpos($arg, '--testsuite=unit') !== false) {
                $isUnitTestRun = true;
                break;
            }
        }
    }
    
    // Also check for UNIT_TEST_ONLY env var
    if (getenv('UNIT_TEST_ONLY')) {
        $isUnitTestRun = true;
    }
    
    if (!$isUnitTestRun) {
        $vendorPluginAutoloader = LIMESURVEY_PATH . '/upload/plugins/StructureImEx/vendor/autoload.php';
        if (file_exists($vendorPluginAutoloader)) {
            require_once $vendorPluginAutoloader;
            echo "Loaded plugin autoloader from vendor LimeSurvey\n";
        } else {
            echo "Warning: Plugin autoloader not found at {$vendorPluginAutoloader}\n";
        }
    } else {
        echo "Skipping plugin autoloader for unit tests\n";
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

