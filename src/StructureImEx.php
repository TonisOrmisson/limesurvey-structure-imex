<?php

namespace tonisormisson\ls\structureimex;

use CUploadedFile;
use Permission;
use PluginBase;
use Question;
use QuestionGroup;
use Survey;
use tonisormisson\ls\structureimex\exceptions\InvalidInputException;
use tonisormisson\ls\structureimex\import\ImportRelevance;
use tonisormisson\ls\structureimex\import\ImportStructure;
use tonisormisson\ls\structureimex\export\ExportRelevances;
use tonisormisson\ls\structureimex\export\ExportQuestions;
use tonisormisson\ls\structureimex\export\ImexQuestionsRowBuilder;
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
    const API_ACTION_LIST_GROUP_ITEMS = 'list_group_items';
    const API_ACTION_LIST_GROUP_QUESTION_ITEMS = 'list_group_question_items';
    const API_ACTION_LIST_QUESTIONS_BY_GROUP = 'list_questions_by_group';
    const API_ACTION_GET_QUESTION_STRUCTURE = 'get_question_structure';

    /** @var string */
    public $type;    /* Register plugin on events*/
    public function init()
    {
        \Yii::log("init", 'info', 'plugin.tonisormisson.imex');
        $this->subscribe('beforeToolsMenuRender');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
        $this->subscribe('listPluginApiActions');
        $this->subscribe('callPluginApiAction');
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
            $import = new ImportRelevance($this->survey, $this->getWarningManager());
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
            
            $import = new ImportQuotas($this->survey, $this->getWarningManager());
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
                $clearSurveyContents = (bool) $this->app()->request->getPost('clear_survey_contents', false);
                $import = new ImportStructure($this->survey, $this->getWarningManager());
                $import->setClearSurveyContents($clearSurveyContents);
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
                $model = new ExportRelevances($this->survey);
                break;
            case self::ACTION_QUESTIONS:
                \Yii::log("actionExport: Creating ExportQuestions", 'info', 'plugin.tonisormisson.imex');
                $model = new ExportQuestions($this->survey);
                break;
            case self::ACTION_QUOTAS:
                \Yii::log("actionExport: Creating ExportQuotas", 'info', 'plugin.tonisormisson.imex');
                $model = new ExportQuotas($this->survey);
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
     * Expose plugin API metadata for RemoteControl discovery.
     */
    public function listPluginApiActions(): void
    {
        $event = $this->event;
        $requestedPlugin = (string) $event->get('requestedPlugin', '');
        if ($requestedPlugin !== '' && $requestedPlugin !== get_class($this)) {
            return;
        }

        $pluginApi = $event->get('pluginApi', []);
        if (!is_array($pluginApi)) {
            $pluginApi = [];
        }

        $pluginApi[get_class($this)] = [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'actions' => [
                self::API_ACTION_LIST_GROUP_ITEMS => [
                    'title' => 'List Group Items',
                    'description' => 'Returns group-level IMEX items only.',
                    'remoteControlPermission' => [
                        'scope' => 'survey',
                        'permission' => 'surveycontent',
                        'crud' => 'read',
                        'sid' => ['payload.sid', 'payload.surveyId', 'context.sid', 'context.surveyId']
                    ],
                    'input' => [
                        'type' => 'object',
                        'required' => ['sid'],
                        'properties' => [
                            'sid' => [
                                'type' => 'integer',
                                'minimum' => 1
                            ],
                            'language' => [
                                'type' => 'string',
                                'description' => 'Optional survey language code for localized labels. Defaults to survey base language.'
                            ]
                        ]
                    ],
                    'output' => [
                        'type' => 'object',
                        'properties' => [
                            'surveyId' => ['type' => 'integer'],
                            'surveyLanguage' => ['type' => 'string'],
                            'labelLanguage' => ['type' => 'string'],
                            'imexHeader' => ['type' => 'array'],
                            'groups' => ['type' => 'array']
                        ]
                    ],
                    'example' => [
                        'method' => 'call_plugin_api',
                        'params' => [
                            'plugin' => get_class($this),
                            'action' => self::API_ACTION_LIST_GROUP_ITEMS,
                            'payload' => [
                                'sid' => 123456
                            ]
                        ]
                    ]
                ],
                self::API_ACTION_LIST_GROUP_QUESTION_ITEMS => [
                    'title' => 'List Group Question Items',
                    'description' => 'Returns top-level question IMEX items for one group.',
                    'remoteControlPermission' => [
                        'scope' => 'survey',
                        'permission' => 'surveycontent',
                        'crud' => 'read',
                        'sid' => ['payload.sid', 'payload.surveyId', 'context.sid', 'context.surveyId']
                    ],
                    'input' => [
                        'type' => 'object',
                        'required' => ['sid', 'gid'],
                        'properties' => [
                            'sid' => [
                                'type' => 'integer',
                                'minimum' => 1
                            ],
                            'gid' => [
                                'type' => 'integer',
                                'minimum' => 1
                            ],
                            'language' => [
                                'type' => 'string',
                                'description' => 'Optional survey language code for localized labels. Defaults to survey base language.'
                            ]
                        ]
                    ],
                    'output' => [
                        'type' => 'object',
                        'properties' => [
                            'surveyId' => ['type' => 'integer'],
                            'groupId' => ['type' => 'integer'],
                            'surveyLanguage' => ['type' => 'string'],
                            'labelLanguage' => ['type' => 'string'],
                            'imexHeader' => ['type' => 'array'],
                            'group' => ['type' => 'object'],
                            'questions' => ['type' => 'array']
                        ]
                    ],
                    'example' => [
                        'method' => 'call_plugin_api',
                        'params' => [
                            'plugin' => get_class($this),
                            'action' => self::API_ACTION_LIST_GROUP_QUESTION_ITEMS,
                            'payload' => [
                                'sid' => 123456,
                                'gid' => 1
                            ]
                        ]
                    ]
                ],
                self::API_ACTION_LIST_QUESTIONS_BY_GROUP => [
                    'title' => 'List Questions By Group',
                    'description' => 'Returns a compact survey structure: groups with top-level questions (subquestions excluded).',
                    'remoteControlPermission' => [
                        'scope' => 'survey',
                        'permission' => 'surveycontent',
                        'crud' => 'read',
                        'sid' => ['payload.sid', 'payload.surveyId', 'context.sid', 'context.surveyId']
                    ],
                    'input' => [
                        'type' => 'object',
                        'required' => ['sid'],
                        'properties' => [
                            'sid' => [
                                'type' => 'integer',
                                'minimum' => 1
                            ],
                            'language' => [
                                'type' => 'string',
                                'description' => 'Optional survey language code for localized labels. Defaults to survey base language.'
                            ]
                        ]
                    ],
                    'output' => [
                        'type' => 'object',
                        'properties' => [
                            'surveyId' => ['type' => 'integer'],
                            'surveyLanguage' => ['type' => 'string'],
                            'labelLanguage' => ['type' => 'string'],
                            'imexHeader' => ['type' => 'array'],
                            'groups' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'gid' => ['type' => 'integer'],
                                        'order' => ['type' => 'integer'],
                                        'title' => ['type' => 'string'],
                                        'imexRow' => ['type' => 'object'],
                                        'questions' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'qid' => ['type' => 'integer'],
                                                    'code' => ['type' => 'string'],
                                                    'label' => ['type' => 'string'],
                                                    'type' => ['type' => 'string'],
                                                    'order' => ['type' => 'integer'],
                                                    'imexRow' => ['type' => 'object']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'example' => [
                        'method' => 'call_plugin_api',
                        'params' => [
                            'plugin' => get_class($this),
                            'action' => self::API_ACTION_LIST_QUESTIONS_BY_GROUP,
                            'payload' => [
                                'sid' => 123456
                            ]
                        ]
                    ]
                ],
                self::API_ACTION_GET_QUESTION_STRUCTURE => [
                    'title' => 'Get Question Structure',
                    'description' => 'Returns one question with subquestions and answers in IMEX-like row format.',
                    'remoteControlPermission' => [
                        'scope' => 'survey',
                        'permission' => 'surveycontent',
                        'crud' => 'read',
                        'sid' => ['payload.sid', 'payload.surveyId', 'context.sid', 'context.surveyId']
                    ],
                    'input' => [
                        'type' => 'object',
                        'required' => ['qid', 'sid'],
                        'properties' => [
                            'qid' => [
                                'type' => 'integer',
                                'minimum' => 1
                            ],
                            'sid' => [
                                'type' => 'integer',
                                'minimum' => 1,
                                'description' => 'Survey ID, required for core RemoteControl permission checks.'
                            ],
                            'language' => [
                                'type' => 'string',
                                'description' => 'Optional survey language code for localized labels. Defaults to survey base language.'
                            ]
                        ]
                    ],
                    'output' => [
                        'type' => 'object',
                        'properties' => [
                            'surveyId' => ['type' => 'integer'],
                            'surveyLanguage' => ['type' => 'string'],
                            'labelLanguage' => ['type' => 'string'],
                            'imexHeader' => ['type' => 'array'],
                            'question' => ['type' => 'object'],
                            'subquestions' => ['type' => 'array'],
                            'answers' => ['type' => 'array'],
                            'exportAnswers' => ['type' => 'array'],
                            'imexRows' => ['type' => 'array']
                        ]
                    ],
                    'example' => [
                        'method' => 'call_plugin_api',
                        'params' => [
                            'plugin' => get_class($this),
                            'action' => self::API_ACTION_GET_QUESTION_STRUCTURE,
                            'payload' => [
                                'qid' => 4380,
                                'sid' => 225627,
                                'language' => 'et'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $event->set('pluginApi', $pluginApi);
    }

    /**
     * Execute plugin API action requested through RemoteControl.
     */
    public function callPluginApiAction(): void
    {
        $event = $this->event;
        $plugin = (string) $event->get('plugin', '');
        if ($plugin !== get_class($this)) {
            return;
        }

        $action = (string) $event->get('action', '');
        switch ($action) {
            case self::API_ACTION_LIST_GROUP_ITEMS:
                $this->handleApiListGroupItems($event);
                return;
            case self::API_ACTION_LIST_GROUP_QUESTION_ITEMS:
                $this->handleApiListGroupQuestionItems($event);
                return;
            case self::API_ACTION_LIST_QUESTIONS_BY_GROUP:
                $this->handleApiListQuestionsByGroup($event);
                return;
            case self::API_ACTION_GET_QUESTION_STRUCTURE:
                $this->handleApiGetQuestionStructure($event);
                return;
            default:
                return;
        }
    }

    private function handleApiListGroupItems($event): void
    {
        $payload = $event->get('payload', []);
        $context = $event->get('context', []);
        $sid = (int) ($payload['sid'] ?? $payload['surveyId'] ?? $context['sid'] ?? $context['surveyId'] ?? 0);
        $requestedLanguage = (string) ($payload['language'] ?? $context['language'] ?? '');

        if ($sid <= 0) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Faulty parameters: payload.sid is required']);
            return;
        }

        if (!Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'read')) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'No permission']);
            return;
        }

        $survey = Survey::model()->findByPk($sid);
        if (!$survey) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Error: Invalid survey ID']);
            return;
        }

        $labelLanguage = $this->resolveApiLanguage($survey, $requestedLanguage);
        if ($labelLanguage === null) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Language code not found for this survey.']);
            return;
        }

        $rowBuilder = new ImexQuestionsRowBuilder($survey, $survey->getAllLanguages());
        $groups = QuestionGroup::model()->findAllByAttributes(
            ['sid' => $sid],
            ['order' => 'group_order ASC, gid ASC']
        );

        $resultGroups = [];
        foreach ($groups as $group) {
            $groupImexRow = $rowBuilder->buildAssocRow($rowBuilder->buildGroupRow($group));
            $resultGroups[] = [
                'gid' => (int) $group->gid,
                'sid' => (int) $group->sid,
                'order' => (int) $group->group_order,
                'title' => $this->getImexLocalizedColumn(
                    $groupImexRow,
                    ImportStructure::COLUMN_VALUE,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'description' => $this->getImexLocalizedColumn(
                    $groupImexRow,
                    ImportStructure::COLUMN_HELP,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'imexRow' => $groupImexRow,
            ];
        }

        $event->set('handled', true);
        $event->set('result', [
            'surveyId' => $sid,
            'surveyLanguage' => (string) $survey->language,
            'labelLanguage' => (string) $labelLanguage,
            'imexHeader' => $rowBuilder->buildHeader(),
            'groups' => $resultGroups,
        ]);
    }

    private function handleApiListGroupQuestionItems($event): void
    {
        $payload = $event->get('payload', []);
        $context = $event->get('context', []);
        $sid = (int) ($payload['sid'] ?? $payload['surveyId'] ?? $context['sid'] ?? $context['surveyId'] ?? 0);
        $gid = (int) ($payload['gid'] ?? $context['gid'] ?? 0);
        $requestedLanguage = (string) ($payload['language'] ?? $context['language'] ?? '');

        if ($sid <= 0 || $gid <= 0) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Faulty parameters: payload.sid and payload.gid are required']);
            return;
        }

        if (!Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'read')) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'No permission']);
            return;
        }

        $survey = Survey::model()->findByPk($sid);
        if (!$survey) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Error: Invalid survey ID']);
            return;
        }

        $labelLanguage = $this->resolveApiLanguage($survey, $requestedLanguage);
        if ($labelLanguage === null) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Language code not found for this survey.']);
            return;
        }

        $group = QuestionGroup::model()->findByAttributes([
            'gid' => $gid,
            'sid' => $sid,
        ]);
        if (!$group) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Error: Invalid group ID']);
            return;
        }

        $rowBuilder = new ImexQuestionsRowBuilder($survey, $survey->getAllLanguages());
        $groupImexRow = $rowBuilder->buildAssocRow($rowBuilder->buildGroupRow($group));
        $groupQuestions = $group->questions;
        if (!is_array($groupQuestions)) {
            $groupQuestions = [];
        }

        $questions = [];
        foreach ($groupQuestions as $question) {
            $questionImexRow = $rowBuilder->buildAssocRow(
                $rowBuilder->buildQuestionRow($question, ExportQuestions::TYPE_QUESTION)
            );
            $questions[] = [
                'qid' => (int) $question->qid,
                'sid' => (int) $question->sid,
                'gid' => (int) $question->gid,
                'order' => (int) $question->question_order,
                'code' => (string) $question->title,
                'type' => (string) $question->type,
                'label' => $this->getImexLocalizedColumn(
                    $questionImexRow,
                    ImportStructure::COLUMN_VALUE,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'imexRow' => $questionImexRow,
            ];
        }

        $event->set('handled', true);
        $event->set('result', [
            'surveyId' => $sid,
            'groupId' => $gid,
            'surveyLanguage' => (string) $survey->language,
            'labelLanguage' => (string) $labelLanguage,
            'imexHeader' => $rowBuilder->buildHeader(),
            'group' => [
                'gid' => (int) $group->gid,
                'sid' => (int) $group->sid,
                'order' => (int) $group->group_order,
                'title' => $this->getImexLocalizedColumn(
                    $groupImexRow,
                    ImportStructure::COLUMN_VALUE,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'description' => $this->getImexLocalizedColumn(
                    $groupImexRow,
                    ImportStructure::COLUMN_HELP,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'imexRow' => $groupImexRow,
            ],
            'questions' => $questions,
        ]);
    }

    private function handleApiListQuestionsByGroup($event): void
    {
        $payload = $event->get('payload', []);
        $context = $event->get('context', []);
        $sid = $payload['sid'] ?? $payload['surveyId'] ?? $context['sid'] ?? $context['surveyId'] ?? null;
        $sid = (int) $sid;
        $requestedLanguage = (string) ($payload['language'] ?? $context['language'] ?? '');

        if ($sid <= 0) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Faulty parameters: payload.sid is required']);
            return;
        }

        if (!Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'read')) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'No permission']);
            return;
        }

        $survey = Survey::model()->findByPk($sid);
        if (!$survey) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Error: Invalid survey ID']);
            return;
        }

        $labelLanguage = $this->resolveApiLanguage($survey, $requestedLanguage);
        if ($labelLanguage === null) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Language code not found for this survey.']);
            return;
        }

        $rowBuilder = new ImexQuestionsRowBuilder($survey, $survey->getAllLanguages());
        $groups = QuestionGroup::model()->findAllByAttributes(
            ['sid' => $sid],
            ['order' => 'group_order ASC, gid ASC']
        );

        $resultGroups = [];
        foreach ($groups as $group) {
            $groupQuestions = $group->questions;
            if (!is_array($groupQuestions)) {
                $groupQuestions = [];
            }

            $groupImexRow = $rowBuilder->buildAssocRow($rowBuilder->buildGroupRow($group));
            $questions = [];
            foreach ($groupQuestions as $question) {
                $questionImexRow = $rowBuilder->buildAssocRow(
                    $rowBuilder->buildQuestionRow($question, ExportQuestions::TYPE_QUESTION)
                );
                $questions[] = [
                    'qid' => (int) $question->qid,
                    'code' => (string) $question->title,
                    'label' => $this->getImexLocalizedColumn(
                        $questionImexRow,
                        ImportStructure::COLUMN_VALUE,
                        $labelLanguage,
                        (string) $survey->language
                    ),
                    'type' => (string) $question->type,
                    'order' => (int) $question->question_order,
                    'imexRow' => $questionImexRow,
                ];
            }

            $resultGroups[] = [
                'gid' => (int) $group->gid,
                'order' => (int) $group->group_order,
                'title' => $this->getImexLocalizedColumn(
                    $groupImexRow,
                    ImportStructure::COLUMN_VALUE,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'imexRow' => $groupImexRow,
                'questions' => $questions,
            ];
        }

        $event->set('handled', true);
        $event->set('result', [
            'surveyId' => $sid,
            'surveyLanguage' => (string) $survey->language,
            'labelLanguage' => (string) $labelLanguage,
            'imexHeader' => $rowBuilder->buildHeader(),
            'groups' => $resultGroups,
        ]);
    }

    private function handleApiGetQuestionStructure($event): void
    {
        $payload = $event->get('payload', []);
        $context = $event->get('context', []);
        $qid = (int) ($payload['qid'] ?? $context['qid'] ?? 0);
        $requestedSid = (int) ($payload['sid'] ?? $payload['surveyId'] ?? $context['sid'] ?? $context['surveyId'] ?? 0);
        $requestedLanguage = (string) ($payload['language'] ?? $context['language'] ?? '');

        if ($qid <= 0) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Faulty parameters: payload.qid is required']);
            return;
        }

        $question = Question::model()->with('questionl10ns', 'subquestions', 'answers')->findByPk($qid);
        if (!$question) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Error: Invalid question ID']);
            return;
        }

        $sid = (int) $question->sid;
        if ($requestedSid > 0 && $requestedSid !== $sid) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Error: Question does not belong to provided survey ID']);
            return;
        }

        if (!Permission::model()->hasSurveyPermission($sid, 'surveycontent', 'read')) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'No permission']);
            return;
        }

        $survey = Survey::model()->findByPk($sid);
        if (!$survey) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Error: Invalid survey ID']);
            return;
        }

        $labelLanguage = $this->resolveApiLanguage($survey, $requestedLanguage);
        if ($labelLanguage === null) {
            $event->set('handled', true);
            $event->set('error', ['status' => 'Language code not found for this survey.']);
            return;
        }

        $rowBuilder = new ImexQuestionsRowBuilder($survey, $survey->getAllLanguages());
        $questionRow = $rowBuilder->buildAssocRow(
            $rowBuilder->buildQuestionRow($question, ExportQuestions::TYPE_QUESTION)
        );
        $questionOptions = $this->decodeImexOptions($questionRow, $labelLanguage);

        $allSubQuestions = is_array($question->subquestions) ? $question->subquestions : [];
        [$answerLikeSubQuestions, $regularSubQuestions] = $rowBuilder->partitionSubQuestions($question, $allSubQuestions);

        $subquestionRows = [];
        $subquestions = [];
        foreach ($allSubQuestions as $subQuestion) {
            $row = $rowBuilder->buildAssocRow(
                $rowBuilder->buildQuestionRow($subQuestion, ExportQuestions::TYPE_SUB_QUESTION)
            );
            $subquestionRows[(int) $subQuestion->qid] = $row;
            $subquestions[] = [
                'qid' => (int) $subQuestion->qid,
                'parentQid' => (int) $subQuestion->parent_qid,
                'scaleId' => (int) $subQuestion->scale_id,
                'order' => (int) $subQuestion->question_order,
                'code' => (string) $subQuestion->title,
                'label' => $this->getImexLocalizedColumn(
                    $row,
                    ImportStructure::COLUMN_VALUE,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'help' => $this->getImexLocalizedColumn(
                    $row,
                    ImportStructure::COLUMN_HELP,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'script' => $this->getImexLocalizedColumn(
                    $row,
                    ImportStructure::COLUMN_SCRIPT,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'relevance' => (string) $subQuestion->relevance,
                'imexRow' => $row,
            ];
        }

        $answers = [];
        $directAnswerRows = [];
        $directAnswers = is_array($question->answers) ? $question->answers : [];
        foreach ($directAnswers as $answer) {
            $row = $rowBuilder->buildAssocRow($rowBuilder->buildAnswerRow($answer));
            $answerText = $this->getImexLocalizedColumn(
                $row,
                ImportStructure::COLUMN_VALUE,
                $labelLanguage,
                (string) $survey->language
            );
            $directAnswerRows[] = $row;
            $answers[] = [
                'source' => 'answer',
                'aid' => (int) $answer->aid,
                'qid' => (int) $answer->qid,
                'code' => (string) $answer->code,
                'scaleId' => (int) $answer->scale_id,
                'sortOrder' => (int) $answer->sortorder,
                'assessmentValue' => (int) $answer->assessment_value,
                'label' => $answerText,
                'imexRow' => $row,
            ];
        }

        $derivedAnswerRows = [];
        foreach ($answerLikeSubQuestions as $subQuestion) {
            $row = $rowBuilder->buildAssocRow($rowBuilder->buildSubQuestionAsAnswerRow($subQuestion));
            $derivedAnswerRows[] = $row;
        }

        $exportAnswerRows = [];
        if ((string) $question->type !== Question::QT_M_MULTIPLE_CHOICE) {
            $exportAnswerRows = array_merge($exportAnswerRows, $directAnswerRows);
        }
        $exportAnswerRows = array_merge($exportAnswerRows, $derivedAnswerRows);

        $imexRows = [$questionRow];
        foreach ($exportAnswerRows as $answerRow) {
            $imexRows[] = $answerRow;
        }
        foreach ($regularSubQuestions as $regularSubQuestion) {
            $qidKey = (int) $regularSubQuestion->qid;
            if (isset($subquestionRows[$qidKey])) {
                $imexRows[] = $subquestionRows[$qidKey];
            }
        }

        $groupTitle = '';
        if ((int) $question->gid > 0) {
            $group = QuestionGroup::model()->findByPk((int) $question->gid);
            if ($group) {
                $groupTitle = (string) $group->getPrimaryTitle();
            }
        }

        $event->set('handled', true);
        $event->set('result', [
            'surveyId' => $sid,
            'surveyLanguage' => (string) $survey->language,
            'labelLanguage' => $labelLanguage,
            'imexHeader' => $rowBuilder->buildHeader(),
            'question' => [
                'qid' => (int) $question->qid,
                'sid' => (int) $question->sid,
                'gid' => (int) $question->gid,
                'groupTitle' => $groupTitle,
                'parentQid' => (int) $question->parent_qid,
                'order' => (int) $question->question_order,
                'code' => (string) $question->title,
                'type' => (string) $question->type,
                'label' => $this->getImexLocalizedColumn(
                    $questionRow,
                    ImportStructure::COLUMN_VALUE,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'help' => $this->getImexLocalizedColumn(
                    $questionRow,
                    ImportStructure::COLUMN_HELP,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'script' => $this->getImexLocalizedColumn(
                    $questionRow,
                    ImportStructure::COLUMN_SCRIPT,
                    $labelLanguage,
                    (string) $survey->language
                ),
                'relevance' => (string) $question->relevance,
                'mandatory' => (string) $question->mandatory,
                'same_script' => (int) $question->same_script,
                'theme' => ((string) $question->question_theme_name !== 'core') ? (string) $question->question_theme_name : '',
                'options' => $questionOptions['global'],
                'options_language' => $questionOptions['language'],
                'imexRow' => $questionRow,
            ],
            'subquestions' => $subquestions,
            'answers' => $answers,
            'exportAnswers' => $exportAnswerRows,
            'imexRows' => $imexRows,
        ]);
    }

    private function resolveApiLanguage(Survey $survey, string $requestedLanguage): ?string
    {
        if ($requestedLanguage === '') {
            return (string) $survey->language;
        }
        $availableLanguages = $survey->getAllLanguages();
        if (!in_array($requestedLanguage, $availableLanguages, true)) {
            return null;
        }
        return $requestedLanguage;
    }

    /**
     * Read localized value from IMEX row data (`{column}-{lang}`), fallback to base language.
     *
     * @param array<string,mixed> $imexRow
     */
    private function getImexLocalizedColumn(array $imexRow, string $column, string $language, string $baseLanguage): string
    {
        $primaryKey = $column . '-' . $language;
        if (array_key_exists($primaryKey, $imexRow) && $imexRow[$primaryKey] !== '') {
            return (string) $imexRow[$primaryKey];
        }

        $fallbackKey = $column . '-' . $baseLanguage;
        if (array_key_exists($fallbackKey, $imexRow) && $imexRow[$fallbackKey] !== '') {
            return (string) $imexRow[$fallbackKey];
        }

        return '';
    }

    /**
     * Decode options from IMEX row (`options` and `options-{lang}`) into arrays.
     *
     * @param array<string,mixed> $imexRow
     * @return array{global: array, language: array}
     */
    private function decodeImexOptions(array $imexRow, string $language): array
    {
        $globalRaw = (string) ($imexRow[ImportStructure::COLUMN_OPTIONS] ?? '');
        $languageRaw = (string) ($imexRow[ImportStructure::COLUMN_OPTIONS . '-' . $language] ?? '');

        return [
            'global' => $this->decodeImexJsonObject($globalRaw),
            'language' => $this->decodeImexJsonObject($languageRaw),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeImexJsonObject(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
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
