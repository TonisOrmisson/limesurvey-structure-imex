<?php

// Bootstrap file for PHPUnit tests
// This sets up the LimeSurvey environment for testing using the real LimeSurvey dependency

// Include our plugin's composer autoloader first (for OpenSpout, etc.)
require_once __DIR__ . '/../vendor/autoload.php';

// Set up LimeSurvey path constants
define('LIMESURVEY_PATH', __DIR__ . '/../vendor/limesurvey/limesurvey');
define('APPPATH', LIMESURVEY_PATH . '/application/');
define('BASEPATH', LIMESURVEY_PATH . '/');

// Include LimeSurvey's vendor autoloader (includes Yii and LimeSurvey models)
require_once LIMESURVEY_PATH . '/vendor/autoload.php';

// Include Yii framework from LimeSurvey's vendor
require_once LIMESURVEY_PATH . '/vendor/yiisoft/yii/framework/yii.php';

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

// Add simple mock for PluginBase since we don't need the full plugin system for our tests
if (!class_exists('PluginBase')) {
    class PluginBase {
        protected $settings = [];
        
        public function __construct($plugin = null, $id = null) {}
        
        public function get($key, $subkey = null, $default = null, $surveyId = null, $userId = null) {
            return $default;
        }
        
        public function set($key, $value = null, $subkey = null, $surveyId = null, $userId = null) {
            return true;
        }
        
        public function subscribe($event, $method = null) {}
        
        public function getPluginSettings($getValues = true) {
            return [];
        }
        
        public function getEvent() {
            return new MockEvent();
        }
    }
}

