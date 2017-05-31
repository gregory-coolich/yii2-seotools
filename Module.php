<?php

namespace ryhor123\seotools;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'ryhor123\seotools\controllers';

    /**
     * @var array the list of rights that are allowed to access this module.
     * If you modify, you also need to enable authManager.
     * http://www.yiiframework.com/doc-2.0/guide-security-authorization.html
     */
    public $roles = [];

    public function init()
    {
        parent::init();
    }
}
