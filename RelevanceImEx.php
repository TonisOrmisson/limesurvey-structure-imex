<?php
/**
 * @author Tõnis Ormisson <tonis@andmemasin.eu>
 * @since 3.0.
 */
class RelevanceImEx extends PluginBase {


    protected $storage = 'DbStorage';
    static protected $description = 'Import-Export survey relevances';
    static protected $name = 'Relevance IMEX';

    /** @var Survey $survey */
    private $survey;

    /* Register plugin on events*/
    public function init() {

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
        $aData = [
            'survey' => $this->survey,
            'exportUrl' => $this->createUrl('actionExport'),
            'importUrl' => $this->createUrl('actionImport'),
        ];
        return  $this->renderPartial('index', $aData, true);
    }


}
