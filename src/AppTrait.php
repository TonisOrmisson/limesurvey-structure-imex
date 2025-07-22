<?php
namespace tonisormisson\ls\structureimex;


trait AppTrait
{

    public function app() : \LSYii_Application
    {
        /** @var \LSYii_Application $app */
        $app = \Yii::app();
        return $app;
    }
}
