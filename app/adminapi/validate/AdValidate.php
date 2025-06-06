<?php

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;


/**
 * AdValidate
 */
class AdValidate extends BaseValidate
{
    /**
     * 设置校验规则
     * @var string[]
     */
    protected $rule = [
        'id' => 'require',
    ];


    /**
     * 参数描述
     * @var string[]
     */
    protected $field = [
        'id' => 'id',
    ];

    /**
     * @return AdValidate
     */
    public function sceneDetail(): AdValidate
    {
        return $this->only(['id']);
    }

}
