<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use PHPUnit\Framework\TestCase;
use LSYii_Application;
use CDbConnection;
use Survey;
use Question;
use QuestionGroup;

/**
 * Base class for database-driven functional tests
 * 
 * This class sets up a real LimeSurvey database connection for testing
 * actual import/export functionality with database persistence.
 */
abstract class DatabaseTestCase extends TestCase
{
    protected static $app;
    protected static $db;
    protected static $isDbSetup = false;
    
    protected $testSurveyId;
    protected $createdSurveyIds = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Load environment variables from tests/.env file
        self::loadEnvironmentFile();
        
        if (!self::$isDbSetup) {
            self::setupDatabase();
            self::$isDbSetup = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any previous test data
        $this->cleanupTestSurveys();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->cleanupTestSurveys();
        
        parent::tearDown();
    }

    /**
     * Load environment variables from tests/.env file
     */
    private static function loadEnvironmentFile(): void
    {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) {
                    continue; // Skip comments
                }
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (!getenv($key)) { // Don't override existing env vars
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * Set up database connection for testing
     */
    private static function setupDatabase(): void
    {
        // Get database configuration from environment or use defaults for testing
        $dbConfig = self::getDatabaseConfig();
        
        // Initialize LimeSurvey application with minimal configuration
        self::initializeLimeSurveyApp($dbConfig);
        
        // Verify database connection
        self::verifyDatabaseConnection();
    }

    /**
     * Get database configuration for testing
     */
    private static function getDatabaseConfig(): array
    {
        // Check for CI environment variables first
        if (getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true') {
            // In CI, use separate database for vendor LimeSurvey installation
            return [
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => getenv('DB_PORT') ?: '3306',
                'database' => getenv('DB_NAME') ?: 'limesurvey_vendor_test',
                'username' => getenv('DB_USER') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
            ];
        }
        
        // Local development configuration - use parent LimeSurvey database
        return [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'limesurvey_test',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: 'root',
        ];
    }

    /**
     * Initialize LimeSurvey application for testing using the same approach as LimeSurvey tests
     */
    private static function initializeLimeSurveyApp(array $dbConfig): void
    {
        // Set up $_SERVER variables to prevent "CHttpRequest is unable to determine the request URI" error
        // This is the same fix used in LimeSurvey's own tests (see tests/bootstrap.php:225-229)
        $_SERVER['SCRIPT_FILENAME'] = 'index-test.php';
        $_SERVER['SCRIPT_NAME'] = '/index-test.php';
        $_SERVER['REQUEST_URI'] = 'index-test.php';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        
        // Check if application already exists (avoid recreating)
        if (self::$app !== null) {
            return;
        }
        
        // Check if we're in CI environment using vendor LimeSurvey or development using parent LimeSurvey
        $isVendorEnvironment = getenv('LIMESURVEY_VENDOR_PATH') !== false;
        
        // Use the existing LimeSurvey application if available and we're in development mode
        if (\Yii::app() !== null && !$isVendorEnvironment) {
            self::$app = \Yii::app();
            self::$db = self::$app->db;
            
            // Update database config for testing
            self::$db->connectionString = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database']
            );
            self::$db->username = $dbConfig['username'];
            self::$db->password = $dbConfig['password'];
            
            return;
        }

        // Load LimeSurvey's internal config as base
        $configFile = LIMESURVEY_PATH . '/application/config/internal.php';
        if (!file_exists($configFile)) {
            throw new \Exception("LimeSurvey internal config not found at: {$configFile}");
        }
        $config = require_once($configFile);
        
        // Override database configuration
        $config['components']['db']['connectionString'] = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database']
        );
        $config['components']['db']['username'] = $dbConfig['username'];
        $config['components']['db']['password'] = $dbConfig['password'];
        
        // Enable debug mode and live error display for tests
        $config['config']['debug'] = 2;
        $config['config']['debugsql'] = true;
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        // Configure error handler for tests - disable HTML error pages
        $config['components']['errorHandler'] = [
            'class' => 'CErrorHandler',
            'errorAction' => null, // Disable custom error pages
            'discardOutput' => true, // Don't buffer output for error pages
        ];

        $config['components']['log']['routes']['custom'] = [
            'class' => 'CFileLogRoute',
            'levels' => 'error',
            'logFile' => 'error.log',
        ];

        $config['components']['log']['routes']['andmemasin'] = [
            'class' => 'CFileLogRoute',
            'levels' => 'trace, info, error, warning, debug',
            'categories' => 'plugin.andmemasin.*',
            'logFile' => 'andmemasin.log',
        ];


        // Create test runtime and assets paths in plugin directory
        $testBasePath = __DIR__ . '/../runtime';
        $runtimePath = $testBasePath;
        $assetsPath = $testBasePath . '/assets';
        
        foreach ([$testBasePath, $runtimePath, $assetsPath] as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }


        $config['runtimePath'] = $runtimePath;
        
        // Override asset manager configuration
        $config['components']['assetManager']['basePath'] = $assetsPath;
        $config['components']['assetManager']['baseUrl'] = '/test-assets';
        
        // Temporarily change working directory to LimeSurvey root to fix getcwd() in config
        $originalCwd = getcwd();
        chdir(LIMESURVEY_PATH);
        
        // In vendor environment, ensure config file exists and plugin is available
        if ($isVendorEnvironment) {
            $vendorConfigFile = LIMESURVEY_PATH . '/application/config/config.php';
            if (!file_exists($vendorConfigFile)) {
                throw new \Exception("Vendor LimeSurvey config not found. Installation may have failed.");
            }
            
            // Ensure our plugin is available in vendor LimeSurvey
            $vendorPluginPath = LIMESURVEY_PATH . '/upload/plugins/StructureImEx';
            if (!file_exists($vendorPluginPath . '/StructureImEx.php')) {
                throw new \Exception("StructureImEx plugin not found in vendor LimeSurvey at: {$vendorPluginPath}");
            }
            
            // Add vendor plugin path to include path for autoloading
            if (file_exists($vendorPluginPath . '/vendor/autoload.php')) {
                require_once $vendorPluginPath . '/vendor/autoload.php';
            }
        }
        
        // Create the LimeSurvey application exactly like LimeSurvey's own tests do
        // This should automatically load all dependencies when they're needed
        self::$app = \Yii::createApplication('LSYii_Application', $config);
        
        // Configure error handler for clean test output
        if (self::$app->errorHandler instanceof \CErrorHandler) {
            // For tests, we want exceptions to be thrown instead of HTML pages
            self::$app->errorHandler->errorAction = null;
            self::$app->errorHandler->discardOutput = true;
        }
        
        // Load essential helpers through the application (this is the LimeSurvey way)
        self::$app->loadHelper('database');
        self::$app->loadHelper('surveytranslator');
        self::$app->loadHelper('replacements');

        // Now load the expression manager (which depends on the above helpers)
        if (class_exists('LimeExpressionManager') === false) {
            $emFile = LIMESURVEY_PATH . '/application/helpers/expressions/em_manager_helper.php';
            if (file_exists($emFile)) {
                require_once $emFile;
            }
        }
        
        // Finally load import helper (which depends on expression manager)
        if (function_exists('importSurveyFile') === false) {
            $importFile = LIMESURVEY_PATH . '/application/helpers/admin/import_helper.php';
            if (file_exists($importFile)) {
                require_once $importFile;
            }
        }
        
        // Restore original working directory
        chdir($originalCwd);
        
        self::$db = self::$app->db;

    }

    /**
     * Verify database connection and required tables exist
     */
    private static function verifyDatabaseConnection(): void
    {
        self::$db->createCommand('SELECT 1')->queryScalar();
        
        // Check if core LimeSurvey tables exist
        $requiredTables = ['surveys', 'groups', 'questions', 'question_attributes'];
        foreach ($requiredTables as $table) {
            $tableExists = self::$db->createCommand()
                ->select('COUNT(*)')
                ->from('information_schema.tables')
                ->where('table_schema = :schema AND table_name = :table', [
                    ':schema' => self::$db->createCommand('SELECT DATABASE()')->queryScalar(),
                    ':table' => self::$db->tablePrefix . $table
                ])
                ->queryScalar();
                
            if (!$tableExists) {
                throw new \Exception("Required table '{$table}' not found in test database. Please run LimeSurvey database migration first.");
            }
        }
    }

    /**
     * Import a survey from LSS file using the same approach as LimeSurvey tests
     */
    protected function importSurveyFromFile(string $lssFilePath): int
    {
        if (!file_exists($lssFilePath)) {
            throw new \Exception("Survey file not found: {$lssFilePath}");
        }

        // Set session loginID like LimeSurvey tests do
        \Yii::app()->session['loginID'] = 1;
        
        // Reset the cache to prevent import from failing if there is a cached survey and it's active
        \Survey::model()->resetCache();
        
        // Use LimeSurvey's importSurveyFile function (same as their tests)
        $translateLinksFields = false;
        $newSurveyName = null;
        $result = \importSurveyFile(
            $lssFilePath,
            $translateLinksFields,
            $newSurveyName,
            null
        );
        
        if (!$result) {
            throw new \Exception('Survey import failed: No result returned');
        }
        
        if (!empty($result['error'])) {
            throw new \Exception('Survey import failed: ' . $result['error']);
        }
        
        if (!isset($result['newsid'])) {
            throw new \Exception('Survey import failed: No survey ID returned');
        }

        $surveyId = $result['newsid'];
        $this->createdSurveyIds[] = $surveyId;
        
        // Reset the cache so findByPk doesn't return a previously cached survey
        \Survey::model()->resetCache();
        
        return $surveyId;
    }

    /**
     * Clean up test surveys from database
     */
    protected function cleanupTestSurveys(): void
    {
        foreach ($this->createdSurveyIds as $surveyId) {
            // Delete survey and all related data
            $survey = Survey::model()->findByPk($surveyId);
            if ($survey) {
                $survey->delete();
            }
            
            // Clean up any remaining data in correct order
            // First get question IDs
            $questionIds = self::$db->createCommand("SELECT qid FROM lime_questions WHERE sid = :sid")->queryColumn([':sid' => $surveyId]);
            if (!empty($questionIds)) {
                $questionIdList = implode(',', $questionIds);
                self::$db->createCommand("DELETE FROM lime_question_attributes WHERE qid IN ($questionIdList)")->execute();
            }
            self::$db->createCommand("DELETE FROM lime_questions WHERE sid = :sid")->execute([':sid' => $surveyId]);
            self::$db->createCommand("DELETE FROM lime_groups WHERE sid = :sid")->execute([':sid' => $surveyId]);
            self::$db->createCommand("DELETE FROM lime_surveys WHERE sid = :sid")->execute([':sid' => $surveyId]);
        }
        $this->createdSurveyIds = [];
    }

    /**
     * Create a test survey with questions and attributes for testing
     */
    protected function createTestSurveyWithQuestions(): array
    {
        // Import the blank survey first
        $blankSurveyPath = __DIR__ . '/../support/data/surveys/blank-survey.lss';
        $surveyId = $this->importSurveyFromFile($blankSurveyPath);
        
        // Add test question groups and questions
        $groupId1 = $this->createTestGroup($surveyId, 'Test Group 1', 1);
        $groupId2 = $this->createTestGroup($surveyId, 'Test Group 2', 2);
        
        $questionId1 = $this->createTestQuestion($surveyId, $groupId1, 'Q001', Question::QT_T_LONG_FREE_TEXT, 'What is your name?');
        $questionId2 = $this->createTestQuestion($surveyId, $groupId1, 'Q002', Question::QT_M_MULTIPLE_CHOICE, 'Select your preferences?', 'Y');
        $questionId3 = $this->createTestQuestion($surveyId, $groupId2, 'Q003', Question::QT_N_NUMERICAL, 'How old are you?');
        
        // Add some test attributes
        $this->createTestAttribute($questionId2, 'random_order', '1');
        $this->createTestAttribute($questionId2, 'other_position', 'end');
        $this->createTestAttribute($questionId3, 'min_answers', '0');
        $this->createTestAttribute($questionId3, 'max_answers', '120');
        
        return [
            'surveyId' => $surveyId,
            'groups' => [$groupId1, $groupId2],
            'questions' => [$questionId1, $questionId2, $questionId3]
        ];
    }

    /**
     * Create a test question group
     */
    protected function createTestGroup(int $surveyId, string $groupName, int $groupOrder): int
    {
        $group = new QuestionGroup();
        $group->sid = $surveyId;
        $group->group_name = $groupName;
        $group->description = "Test description for {$groupName}";
        $group->group_order = $groupOrder;
        $group->language = 'en';
        $group->grelevance = '1';
        
        if (!$group->save()) {
            throw new \Exception('Failed to create test group: ' . print_r($group->getErrors(), true));
        }
        
        return $group->gid;
    }

    /**
     * Create a test question
     */
    protected function createTestQuestion(int $surveyId, int $groupId, string $title, string $type, string $question, string $mandatory = 'N'): int
    {

        // Create the main Question record (no language-specific fields)
        $q = new Question();
        $q->sid = $surveyId;
        $q->gid = $groupId;
        $q->type = $type;
        $q->title = $title;
        $q->mandatory = $mandatory;
        $q->other = 'N';
        $q->question_order = 1;
        $q->scale_id = 0;
        $q->parent_qid = 0;
        $q->relevance = '1';
        $q->modulename = '';

        if (!$q->save()) {
            throw new \tonisormisson\ls\structureimex\exceptions\ImexException('Failed to create test question: ' . print_r($q->getErrors(), true));
        }
        
        // Create the localized content in QuestionL10n table
        if (class_exists('QuestionL10n')) {
            $survey = Survey::model()->findByPk($surveyId);
            $language = $survey->language ?? 'en';
            
            $questionL10n = new \QuestionL10n();
            $questionL10n->qid = $q->qid;
            $questionL10n->language = $language;
            $questionL10n->question = $question;
            $questionL10n->help = "Help text for {$title}";
            
            if (!$questionL10n->save()) {
                throw new \tonisormisson\ls\structureimex\exceptions\ImexException('Failed to create question L10n: ' . print_r($questionL10n->getErrors(), true));
            }
        }
        
        return $q->qid;
    }

    /**
     * Create a test question attribute
     */
    protected function createTestAttribute(int $questionId, string $attribute, string $value): void
    {
        self::$db->createCommand()->insert('question_attributes', [
            'qid' => $questionId,
            'attribute' => $attribute,
            'value' => $value,
            'language' => 'en'
        ]);
    }

    /**
     * Get the database connection for manual queries
     */
    protected function getDb(): CDbConnection
    {
        return self::$db;
    }

    /**
     * Get the LimeSurvey application instance
     */
    protected function getApp(): LSYii_Application
    {
        return self::$app;
    }

    /**
     * Create a real StructureImEx plugin instance configured with a survey
     */
    protected function createRealPlugin(int $surveyId): \tonisormisson\ls\structureimex\StructureImEx
    {
        // Create a real StructureImEx plugin instance
        $plugin = new \tonisormisson\ls\structureimex\StructureImEx(\Yii::app()->getPluginManager(), 999);
        
        // Set up the survey context
        $survey = Survey::model()->findByPk($surveyId);
        if (!$survey) {
            throw new \Exception("Survey {$surveyId} not found for plugin setup");
        }
        
        // Use reflection to set the private survey property
        $reflection = new \ReflectionClass($plugin);
        $surveyProperty = $reflection->getProperty('survey');
        $surveyProperty->setAccessible(true);
        $surveyProperty->setValue($plugin, $survey);
        
        return $plugin;
    }
    
    /**
     * Get the absolute path to a test support file
     * 
     * This method handles the relative path navigation from any test subdirectory
     * back to the tests/support directory structure.
     * 
     * @param string $relativePath Path relative to tests/support (e.g., 'data/surveys/blank-survey.lss')
     * @return string Absolute path to the support file
     */
    protected function getTestSupportFile($relativePath)
    {
        // Get the absolute path to the tests directory by going up from DatabaseTestCase location
        $testsDir = dirname(__DIR__);
        return $testsDir . '/support/' . $relativePath;
    }
    
    /**
     * Get common test survey files
     */
    protected function getBlankSurveyPath()
    {
        return $this->getTestSupportFile('data/surveys/blank-survey.lss');
    }
    
    protected function getMultiLanguageSurveyPath()
    {
        return $this->getTestSupportFile('data/surveys/survey-one-question-two-languages.lss');
    }
}
