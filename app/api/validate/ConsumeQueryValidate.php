<?php


namespace app\api\validate;

use app\common\enum\PayEnum;
use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 话费、电费、油费查询验证器
 * Class UserValidate
 * @package app\shopapi\validate
 */
class ConsumeQueryValidate extends BaseValidate
{
    protected $rule = [
        'phone' => ['require', 'regex' => '/^1[3456789]\d{9}$/'],
        'number' => ['require', 'regex' => '/^[a-zA-Z0-9]+$/'],
        'area' => ['require', 'regex' => '/^[0-9]+$/'],
        'type' => ['require', 'number'],
        'batch_data' => ['require'],
    ];

    protected $message = [
        'phone.require' => '手机号不能为空',
        'phone.regex' => '请正确输入手机号',
        'number.require' => '电费号不能为空',
        'number.regex' => '请正确输入电费号',
        'area.require' => '请正确选择省份',
        'area.regex' => '请正确选择省份',
        'type' => '参数错误',
        'batch_data' => '请正确输入号码',
    ];

    /**
     * 手机号验证
     *
     * @return ConsumeQueryValidate
     */
    public function scenePhone(): ConsumeQueryValidate
    {
        return $this->only(['phone']);
    }

    /**
     * 电费号验证
     *
     * @return ConsumeQueryValidate
     */
    public function sceneElectricity(): ConsumeQueryValidate
    {
        return $this->only(['number', 'area']);
    }

    /**
     * 验证
     *
     * @return ConsumeQueryValidate
     */
    public function sceneAccountHistory(): ConsumeQueryValidate
    {
        return $this->only(['number', 'type']);
    }

    /**
     * 类型
     *
     * @return ConsumeQueryValidate
     */
    public function sceneType(): ConsumeQueryValidate
    {
        return $this->only(['type']);
    }

    /**
     * 批量
     *
     * @return ConsumeQueryValidate
     */
    public function sceneBatch(): ConsumeQueryValidate
    {
        return $this->only(['batch_data']);
    }
}
