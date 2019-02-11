<?php
/**
 * @author Tõnis Ormisson <tonis@andmemasin.eu>
 */
class RelevanceImEx extends PluginBase {

    /** @var LSYii_Application */
    protected $app;

    protected $storage = 'DbStorage';
    static protected $description = 'Import-Export survey logic as file';
    static protected $name = 'Relevance IMEX';

    /** @var array  */
    private $data = [];

    /** @var Survey $survey */
    private $survey;

    const ACTION_QUESTIONS = "questions";
    const ACTION_RELEVANCES = "relevances";

    /* Register plugin on events*/
    public function init() {
        require_once __DIR__.DIRECTORY_SEPARATOR.'ImportRelevance.php';
        $this->subscribe('beforeToolsMenuRender');
        $this->app = Yii::app();
    }

    public function beforeToolsMenuRender() {
        $event = $this->getEvent();

        /** @var array $menuItems */
        $menuItems = $event->get('menuItems');
        $this->survey = Survey::model()->findByPk($event->get('surveyId'));

        $menuItem = new \LimeSurvey\Menu\MenuItem([
            'label' => $this->getName(),
            'href' => $this->createUrl('actionIndex'),
            'iconClass' => 'fa fa-code-fork  text-info',

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
    private function createUrl($action, $params = []) {
        $url = $this->api->createUrl(
            'admin/pluginhelper',
            array_merge([
                'sa'     => 'sidebody',
                'plugin' => 'RelevanceImEx',
                'method' => $action,
                'sid' => $this->survey->primaryKey,
            ], $params)
        );
        return $url;
    }


    public function actionIndex($sid)
    {
        $this->beforeAction($sid);

        $import = null;

        if (Yii::app()->request->isPostRequest){
            $import = new ImportRelevance($this->survey);
            $oFile = CUploadedFile::getInstanceByName("the_file");
            if(!$import->loadFile($oFile)){
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
        $this->beforeAction($sid);
        $import = null;
        $this->data['exportPlugin'] = $this;
        return $this->renderPartial('questions', $this->data, true);
    }

    public function actionExport($sid)
    {
        $this->survey = Survey::model()->findByPk($sid);
        $model = new ExportRelevances($this->survey);
        // headers
        header('Content-Type: application/excel');
        header('Content-Disposition: attachment; filename="'.$model->fileName.'"');
        header('Content-Length: ' . filesize($model->fileName));
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        readfile($model->fileName);
        unlink($model->fileName);
        App()->end();
    }

    private function beforeAction($sid) {
        $this->survey = Survey::model()->findByPk($sid);
        $exportUrl = $this->createUrl('actionExport');

        $this->data = [
            'survey' => $this->survey,
            'exportUrl' => $exportUrl,
            'importUrl' => $this->createUrl('actionImport'),
            'navUrls' => $this->navigationUrls(),
        ];

    }

    private function navigationUrls() {
        return [
            self::ACTION_QUESTIONS => $this->createUrl('actionQuestions'),
            self::ACTION_RELEVANCES => $this->createUrl('actionIndex'),
        ];
    }



}
