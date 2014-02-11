<?php
namespace ats;
use base\components\App;
use base\components\Form;
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2013-12-26
 */

class UserLoginForm extends Form {
    public $username;
    public $password;
    public $rememberMe;
    public $csrfToken;

    protected function rules() {
        return [
            'username' => [
                'type' => 'string',
                'lengthMax' => 32,
                'lengthMin' => 1,
                'required' => true,
            ],
            'password' => [
                'type' => 'string',
                'required' => true,
            ],
            'rememberMe' => [
                'type' => 'bool',
            ],
        ];
    }
}
