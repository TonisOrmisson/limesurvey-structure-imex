<?php
/**
 * @author Tõnis Ormisson <tonis@andmemasin.eu>
 * @since 3.0.
 */
class RelevanceImEx extends PluginBase {


    protected $storage = 'DbStorage';
    static protected $description = 'Import-Export survey relevances';
    static protected $name = 'Relevance IMEX';


    /* Register plugin on events*/
    public function init() {
        $this->subscribe('beforeToolsMenuRender');

    }

    public function beforeToolsMenuRender() {
        $event = $this->getEvent();

        /** @var array $menuItems */
        $menuItems = $event->get('menuItems');
        $menuItem = new \LimeSurvey\Menu\MenuItem([
            'label' => self::$name,
            'icon' =>'',
            'href' => ''
        ]);
        $menuItems[] = $menuItem;
        $event->set('menuItems', $menuItems);
        return $menuItems;

    }




}
