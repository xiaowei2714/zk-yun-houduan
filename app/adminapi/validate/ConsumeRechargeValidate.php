<?php

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;

/**
 * 话费充值、电费充值
 * Class RechargeValidate
 * @package app\adminapi\validate
 */
class ConsumeRechargeValidate extends BaseValidate
{
    /**
     * 设置校验规则
     * @var string[]
     */
    protected $rule = [
        'id' => 'require',
        'ids' => 'require|array',
    ];

    /**
     * 详情场景
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneNeedId()
    {
        return $this->only(['id']);
    }

    /**
     * 详情场景
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneNeedIds()
    {
        return $this->only(['ids']);
    }
}
