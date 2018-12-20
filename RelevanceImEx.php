<?php
/**
 * @author Tõnis Ormisson <tonis@andmemasin.eu>
 */
class RelevanceImEx extends PluginBase {


    protected $storage = 'DbStorage';
    static protected $description = 'Import-Export survey logic as file';
    static protected $name = 'Relevance IMEX';

    /** @var Survey $survey */
    private $survey;

    /* Register plugin on events*/
    public function init() {
        require_once __DIR__.DIRECTORY_SEPARATOR.'ImportRelevance.php';
        $this->subscribe('beforeToolsMenuRender');
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
        $this->survey = Survey::model()->findByPk($sid);
        $exportUrl = $this->createUrl('actionExport');

        $aData = [
            'survey' => $this->survey,
            'exportUrl' => $exportUrl,
            'importUrl' => $this->createUrl('actionImport'),
        ];
        $aData['title_bar']['title'] = $this->survey->currentLanguageSettings->surveyls_title . " (" . gT("ID") . ":" . $this->survey->primaryKey . ")";
        // Get default character set from global settings
        $characterSet = App()->getConfig('characterset');
        // If no encoding was set yet, use the old "auto" default
        if($characterSet == "") {
            $characterSet = "auto";
        }
        $aEncodings =aEncodingsArray();
        // If there are error with file : show the form
        $aData['aEncodings'] = $aEncodings;
        asort($aData['aEncodings']);
        $aData['thischaracterset'] = $characterSet;
        $import = null;

        if (Yii::app()->request->isPostRequest){
            $import = new ImportRelevance($this->survey);
            $oFile = CUploadedFile::getInstanceByName("the_file");
            if(!$import->loadFile($oFile)){
                Yii::app()->setFlashMessage($import->getError('file'), 'error');
            } else {
                $import->process();
            }
        }
        $aData['import'] = $import;

        return  $this->renderPartial('index', $aData, true);
    }


    public function actionExport($sid)
    {
        $this->survey = Survey::model()->findByPk($sid);
        $oSurvey = $this->survey;
        $fn = "survey_relevances_{$oSurvey->primaryKey}.csv";

        // headers
        header('Content-Type: application/excel');
        header('Content-Disposition: attachment; filename="'.$fn.'"');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");

        //FIXME delimiter also ;

        echo "group,code,parent,relevance\n";
        foreach ($oSurvey->groups as $group) {
            // only base language
            if ($group->language != $oSurvey->language) {
                continue;
            }
            /** @var $group QuestionGroup */
            echo "\"{$group->group_name}\",\"\",\"\",{$group->grelevance}\n";
            foreach ($group->questions as $question) {
                // only base language
                if ($question->language != $oSurvey->language) {
                    continue;
                }
                echo "\"\",\"{$question->title}\",\"\",{$question->relevance}\n";
                if (!empty($question->subquestions)) {
                    foreach ($question->subquestions as $subquestion) {
                        echo "\"\",\"{$subquestion->title}\",\"{$question->title}\",{$subquestion->relevance}\n";
                    }
                }
            }
        }

        // don't render page
        App()->end();
    }


}
