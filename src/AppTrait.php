<?php
namespace tonisormisson\ls\structureimex;


trait AppTrait
{
    public function isV4plusVersion(): bool
    {
        return intval($this->app()->getConfig("versionnumber")) > 3;
    }

    public function app() : \LSYii_Application
    {
        /** @var \LSYii_Application $app */
        $app = \Yii::app();
        return $app;
    }
}
