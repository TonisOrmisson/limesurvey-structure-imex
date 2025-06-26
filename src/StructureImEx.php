<?php

namespace tonisormisson\ls\structureimex;

use CUploadedFile;
use PluginBase;
use Survey;
use tonisormisson\ls\structureimex\exceptions\InvalidInputException;
use tonisormisson\ls\structureimex\import\ImportRelevance;
use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\export\ExportRelevances;
use tonisormisson\ls\structureimex\export\ExportQuestions;
use tonisormisson\ls\structureimex\export\ExportQuotas;
use tonisormisson\ls\structureimex\import\ImportQuotas;

/**
 * @author Tõnis Ormisson <tonis@andmemasin.eu>
 */
class StructureImEx extends PluginBase
{

    use AppTrait;


    protected $storage = 'DbStorage';
    static protected $description = 'Import-Export survey structure & logic as file';
    static protected $name = 'Structure IMEX';

    /** @var array */
    private $data = [];

    /** @var Survey $survey */
    private $survey;

    /** @var PersistentWarningManager|null */
    private $warningManager = null;

    const ACTION_QUESTIONS = "questions";
    const ACTION_RELEVANCES = "relevances";
    const ACTION_QUOTAS = "quotas";

    /** @var string */
    public $type;    /* Register plugin on events*/
    public function init()
    {
        \Yii::log("init", 'info', 'plugin.tonisormisson.imex');
        $this->subscribe('beforeToolsMenuRender');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
    }

    /**
     * Survey Settings
     */
    public function beforeSurveySettings()
    {
        $event = $this->event;
        $surveyId = $event->get("survey");

        $newSettings = [
            'importUnknownAttributes' => [
                'type' => 'boolean',
                'label' => 'Import unknown attributes',
                'help' => 'Allow importing unknown question attributes (i.e. plugin attributes).',
                'current' => $this->get('importUnknownAttributes', 'Survey', $surveyId, false),
            ],
        ];

        // Set all settings
        $event->set("surveysettings.{$this->id}", [
            'name' =>  get_class($this),
            'settings' => $newSettings,
        ]);
    }

    /**
     * Handle Survey Settings Saving
     */
    public function newSurveySettings()
    {
        $event = $this->event;
        foreach ($event->get('settings') as $name => $value) {
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }


    public function beforeToolsMenuRender()
    {
        $event = $this->getEvent();

        /** @var array $menuItems */
        $menuItems = $event->get('menuItems');
        $this->survey = Survey::model()->findByPk($event->get('surveyId'));

        $menuItem = new \LimeSurvey\Menu\MenuItem([
            'label' => $this->getName(),
            'href' => $this->createUrl('actionIndex'),
            'iconClass' => 'fa fa-tasks  text-info',

        ]);
        $menuItems[] = $menuItem;
        $event->set('menuItems', $menuItems);
        return $menuItems;

    }

    /**
     * @param string $action
     * @param array $params
     * @return string
     */
    private function createUrl($action, $params = [])
    {
        $url = $this->api->createUrl(
            'admin/pluginhelper',
            array_merge([
                'sa' => 'sidebody',
                'plugin' => 'StructureImEx',
                'method' => $action,
                'sid' => $this->survey->primaryKey,
            ], $params)
        );
        return $url;
    }


    public function actionRelevances($sid)
    {
        $this->type = self::ACTION_RELEVANCES;
        $this->beforeAction($sid);

        $import = null;


        if ($this->app()->request->getIsPostRequest()) {
            $import = new ImportRelevance($this->survey, $this->warningManager);
            $oFile = CUploadedFile::getInstanceByName("the_file");
            if (!$import->loadFile($oFile)) {
                $this->app()->setFlashMessage($import->getError('file'), 'error');
            } else {
                $result = $import->process();
                
                // Check for any errors from the import process
                $allErrors = $import->getErrors();
                $hasErrors = false;
                
                if (!empty($allErrors)) {
                    foreach ($allErrors as $field => $errors) {
                        if (!empty($errors)) {
                            $hasErrors = true;
                            foreach ((array)$errors as $error) {
                                $this->app()->setFlashMessage($error, 'error');
                            }
                        }
                    }
                }
                
                if (!$hasErrors) {
                    // Show success message if no errors occurred
                    $this->app()->setFlashMessage('Relevance rules imported successfully!', 'success');
                }
            }
        }

        $this->data['import'] = $import;
        $this->data['exportPlugin'] = $this;

        return $this->renderPartial('relevances', $this->data, true);
    }

    public function actionQuotas($sid)
    {
        \Yii::log("actionQuotas: Starting with sid=$sid", 'info', 'plugin.tonisormisson.imex');
        
        $this->type = self::ACTION_QUOTAS;
        $this->beforeAction($sid);

        $import = null;

        if ($this->app()->request->getIsPostRequest()) {
            \Yii::log("actionQuotas: Processing file upload", 'info', 'plugin.tonisormisson.imex');
            
            $import = new ImportQuotas($this->survey, $this->warningManager);
            $oFile = CUploadedFile::getInstanceByName("the_file");
            
            if (!$import->loadFile($oFile)) {
                \Yii::log("actionQuotas: File load failed: " . $import->getError('file'), 'error', 'plugin.tonisormisson.imex');
                $this->app()->setFlashMessage($import->getError('file'), 'error');
            } else {
                \Yii::log("actionQuotas: File loaded successfully, processing import", 'info', 'plugin.tonisormisson.imex');
                
                $result = $import->process();

                $allErrors = $import->getErrors();
                $hasErrors = false;
                
                \Yii::log("actionQuotas: Import completed, checking errors. Error count: " . count($allErrors), 'info', 'plugin.tonisormisson.imex');
                
                if (!empty($allErrors)) {
                    foreach ($allErrors as $field => $errors) {
                        if (!empty($errors)) {
                            $hasErrors = true;
                            foreach ((array)$errors as $error) {
                                \Yii::log("actionQuotas: Import error [$field]: $error", 'error', 'plugin.tonisormisson.imex');
                                $this->app()->setFlashMessage($error, 'error');
                            }
                        }
                    }
                }
                
                if (!$hasErrors) {
                    \Yii::log("actionQuotas: Import successful. Success count: {$import->successfulModelsCount}, Failed count: {$import->failedModelsCount}", 'info', 'plugin.tonisormisson.imex');
                    $this->app()->setFlashMessage('Survey quotas imported successfully!', 'success');
                }
            }
        } else {
            \Yii::log("actionQuotas: No file upload detected (GET request or no file)", 'info', 'plugin.tonisormisson.imex');
        }

        $this->data['import'] = $import;
        $this->data['exportPlugin'] = $this;

        \Yii::log("actionQuotas: Rendering quotas view", 'info', 'plugin.tonisormisson.imex');
        return $this->renderPartial('quotas', $this->data, true);
    }

    public function actionQuestions($sid)
    {
        $this->type = self::ACTION_QUESTIONS;
        $this->beforeAction($sid);
        $this->data['exportPlugin'] = $this;
        $import = null;

        if ($this->app()->request->getIsPostRequest()) {
            if ($this->survey->getIsActive()) {
                $this->app()->setFlashMessage("You cannot import survey structure on an activated survey!", 'error');
            } else {
                $import = new ImportStructure($this->survey, $this->warningManager);
                $oFile = CUploadedFile::getInstanceByName("the_file");
                if (!$import->loadFile($oFile)) {
                    $this->app()->setFlashMessage($import->getError('file'), 'error');
                } else {
                    $result = $import->process();

                    // Check for any errors from the import process
                    $allErrors = $import->getErrors();
                    $hasErrors = false;
                    
                    if (!empty($allErrors)) {
                        foreach ($allErrors as $field => $errors) {
                            if (!empty($errors)) {
                                $hasErrors = true;
                                foreach ((array)$errors as $error) {
                                    $this->app()->setFlashMessage($error, 'error');
                                }
                            }
                        }
                    }
                    
                    if (!$hasErrors) {
                        // Show success message if no errors occurred
                        $this->app()->setFlashMessage('Survey structure imported successfully!', 'success');
                    }

                }

            }

        }
        $this->data['import'] = $import;
        $this->data['exportPlugin'] = $this;
        return $this->renderPartial('questions', $this->data, true);
    }


    public function actionIndex($sid)
    {
        $this->beforeAction($sid);
        $this->data['exportPlugin'] = $this;
        return $this->renderPartial('index', $this->data, true);
    }



    public function actionExport($sid)
    {
        \Yii::log("actionExport: Starting with sid=$sid", 'info', 'plugin.tonisormisson.imex');

        $this->survey = Survey::model()->findByPk($sid);
        $type = $this->app()->request->getParam('type');
        
        \Yii::log("actionExport: Export type: $type", 'info', 'plugin.tonisormisson.imex');

        switch ($type) {
            case self::ACTION_RELEVANCES:
                \Yii::log("actionExport: Creating ExportRelevances", 'info', 'plugin.tonisormisson.imex');
                $model = new ExportRelevances($this);
                break;
            case self::ACTION_QUESTIONS:
                \Yii::log("actionExport: Creating ExportQuestions", 'info', 'plugin.tonisormisson.imex');
                $model = new ExportQuestions($this);
                break;
            case self::ACTION_QUOTAS:
                \Yii::log("actionExport: Creating ExportQuotas", 'info', 'plugin.tonisormisson.imex');
                $model = new ExportQuotas($this);
                break;
            default:
                \Yii::log("actionExport: Unknown export type: $type", 'error', 'plugin.tonisormisson.imex');
                throw new InvalidInputException('Unknown type: ' . $type);
        }

        \Yii::log("actionExport: Export model created successfully. File: " . $model->getFullFileName(), 'info', 'plugin.tonisormisson.imex');

        // headers
        header('Content-Type: application/excel');
        header('Content-Disposition: attachment; filename="' . $model->fileName . '"');
        header('Content-Length: ' . filesize($model->getFullFileName()));
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        readfile($model->getFullFileName());
        unlink($model->getFullFileName());
        
        \Yii::log("actionExport: Export completed and file sent", 'info', 'plugin.tonisormisson.imex');
        App()->end();
    }

    /**
     * @return bool
     */
    public function getImportUnknownAttributes()
    {
        return $this->get('importUnknownAttributes', 'Survey', $this->survey->sid, false);
    }

    /**
     * @return Survey
     */
    public function getSurvey()
    {
        return $this->survey;
    }

    public function dir()
    {
        return $this->getDir();
    }

    private function beforeAction($sid)
    {
        $this->survey = Survey::model()->findByPk($sid);
        $exportUrl = $this->createUrl('actionExport', ['type' => $this->type]);

        $this->data = [
            'survey' => $this->survey,
            'exportUrl' => $exportUrl,
            'importUrl' => $this->createUrl('actionImport'),
            'navUrls' => $this->navigationUrls(),
        ];

    }

    private function navigationUrls()
    {
        return [
            self::ACTION_QUESTIONS => $this->createUrl('actionQuestions'),
            self::ACTION_RELEVANCES => $this->createUrl('actionRelevances'),
            self::ACTION_QUOTAS => $this->createUrl('actionQuotas'),
        ];
    }

    protected function getDir()
    {
        $parent = parent::getDir();
        return $parent . DIRECTORY_SEPARATOR . "src";
    }

    /**
     * Get or create the warning manager instance
     */
    public function getWarningManager(): PersistentWarningManager
    {
        if (!$this->warningManager) {
            $this->warningManager = new PersistentWarningManager($this->getSession());
        }
        return $this->warningManager;
    }

    /**
     * Create URL for external use (public method)
     */
    public function createPublicUrl($action, $params = [])
    {
        return $this->createUrl($action, $params);
    }

    /**
     * Public setter for plugin settings (for testing purposes)
     */
    public function setSetting($key, $value, $type = 'Survey', $surveyId = null)
    {
        return $this->set($key, $value, $type, $surveyId);
    }

    /** @var ?\CHttpSession Custom session object for testing */
    private $customSession = null;

    /**
     * Get the session object
     */
    public function getSession() : \CHttpSession
    {
        if($this->customSession instanceof \CHttpSession) {
            return $this->customSession;
        }
        $app = $this->app();
        return $app->getSession();
    }

    /**
     * Set a custom session object (for testing purposes)
     * 
     * @param object $session Session object
     * @return void
     */
    public function setSession($session): void
    {
        $this->customSession = $session;
    }


}
