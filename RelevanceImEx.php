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

        /* Show page */
        $this->subscribe('newDirectRequest');

    }

    public function beforeToolsMenuRender() {
        $event = $this->getEvent();

        /** @var array $menuItems */
        $menuItems = $event->get('menuItems');
        $this->survey = Survey::model()->findByPk($event->get('surveyId'));
        $url = $this->api->createUrl(
            'admin/pluginhelper',
            [
                'sa'     => 'sidebody',
                'plugin' => 'RelevanceImEx',
                'method' => 'actionIndex',
                'sid' => $this->survey->primaryKey,
            ]
        );

        $menuItem = new \LimeSurvey\Menu\MenuItem([
            'label' => $this->getName(),
            'href' => $url,
        ]);
        $menuItems[] = $menuItem;
        $event->set('menuItems', $menuItems);
        return $menuItems;

    }




    public function actionIndex()
    {
        $aData = [
            'pluginds' => $this->getName(),
            'aData' => [],
        ];
        return  $this->renderPartial('index', $aData, true);
    }


}
