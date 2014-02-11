<?php
namespace ats;
use base\components\Form;
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2013-12-26
 */

class UserRetrieveForm extends Form {
    public $email;
    public $csrfToken;

    protected function rules() {
        return [
            'email' => [
                'required' => true,
                'type' => 'string',
                'email' => true
            ]
        ];
    }

}
