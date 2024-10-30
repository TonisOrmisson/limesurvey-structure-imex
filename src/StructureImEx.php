<?php

namespace tonisormisson\ls\structureimex;

use CUploadedFile;
use kcfinder\dir;
use LSYii_Application;
use PluginBase;
use Survey;
use Yii;

/**
 * @author Tõnis Ormisson <tonis@andmemasin.eu>
 */
class StructureImEx extends PluginBase
{

    use AppTrait;

    /** @var LSYii_Application */
    protected $app;

    protected $storage = 'DbStorage';
    static protected $description = 'Import-Export survey structure & logic as file';
    static protected $name = 'Structure IMEX';

    /** @var array */
    private $data = [];

    /** @var Survey $survey */
    private $survey;

    const ACTION_QUESTIONS = "questions";
    const ACTION_RELEVANCES = "relevances";

    /** @var string */
    public $type;

    /* Register plugin on events*/
    public function init()
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'ImportRelevance.php';
        $this->subscribe('beforeToolsMenuRender');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
        $this->app = Yii::app();
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
            'name' => self::getName(),
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

        if (Yii::app()->request->isPostRequest) {
            $import = new ImportRelevance($this);
            $oFile = CUploadedFile::getInstanceByName("the_file");
            if (!$import->loadFile($oFile)) {
                $this->app->setFlashMessage($import->getError('file'), 'error');
            } else {
                $import->process();
            }
        }

        $this->data['import'] = $import;
        $this->data['exportPlugin'] = $this;

        return $this->renderPartial('index', $this->data, true);
    }

    public function actionQuestions($sid)
    {
        $this->type = self::ACTION_QUESTIONS;
        $this->beforeAction($sid);
        $this->data['exportPlugin'] = $this;
        $import = null;

        if (Yii::app()->request->isPostRequest) {
            if ($this->survey->getIsActive()) {
                Yii::app()->setFlashMessage("You cannot import survey structure on an activated survey!", 'error');
            } else {
                if ($this->isV4plusVersion()) {
                    $import = new ImportStructureV4Plus($this);
                } else {
                    $import = new ImportStructure($this);
                }
                $oFile = CUploadedFile::getInstanceByName("the_file");
                if (!$import->loadFile($oFile)) {
                    $this->app->setFlashMessage($import->getError('file'), 'error');
                } else {
                    $import->process();

                    $errors = $import->getErrors('file');

                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            Yii::app()->setFlashMessage($error, 'error');
                        }
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
        return $this->actionRelevances($sid);
    }


    public function actionExport($sid)
    {

        $this->survey = Survey::model()->findByPk($sid);
        $type = Yii::app()->request->getParam('type');

        switch ($type) {
            case self::ACTION_RELEVANCES:
                $model = new ExportRelevances($this->survey);
                break;
            case self::ACTION_QUESTIONS:
                $model = new ExportQuestions($this->survey);
                break;
            default:
                throw new \Exception('Unknown type: ' . $type);
        }

        // headers
        header('Content-Type: application/excel');
        header('Content-Disposition: attachment; filename="' . $model->fileName . '"');
        header('Content-Length: ' . filesize($model->getFullFileName()));
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        readfile($model->getFullFileName());
        unlink($model->getFullFileName());
        App()->end();
    }

    /**
     * @return bool
     */
    public function getImportUnknownAttributes()
    {
        return boolval($this->get('importUnknownAttributes', 'Survey', $this->survey->sid, false));
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
        ];
    }

    protected function getDir()
    {
        $parent = parent::getDir();
        return $parent . DIRECTORY_SEPARATOR . "src";
    }


}
