<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-18
 */
namespace leprechaun;
use base\components\App;
use base\components\Controller;

/**
 * Class Site main site controller
 * @package ats
 */
class SiteController extends Controller {

    public $layout = 'layouts/main';

    protected function accessRules() {
        return [
            static::RULES_COMMON => [
                'authorized' => true,
                'ajax' => true,
            ],
            'Index' => [
                'authorized' => false,
                'ajax' => false,
            ],
        ];
    }

    /**
     * Index page
     */
    public function actionIndex() {
        $this->render('site/index');
    }
}
