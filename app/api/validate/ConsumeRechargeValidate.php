<?php


namespace app\api\validate;

use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 话费、电费、油费充值验证器
 * Class UserValidate
 * @package app\shopapi\validate
 */
class ConsumeRechargeValidate extends BaseValidate
{
    protected $rule = [
        'id' =>  ['require', 'number'],
        'meal_id' =>  ['require', 'number'],
        'meal_discount' => 'require',
        'meal_discounted_price' => 'require',
        'money' => 'require|gt:0',
        'phone' => ['require', 'regex' => '/^1[3456789]\d{9}$/'],
        'batch_data' => 'require',
        'number' => 'require',
        'name' => 'require',
        'area' => 'require',
        'buy_price' => 'require|gt:0',
        'pay_price' => 'require|gt:0',
        'type' => 'require'
    ];

    protected $message = [
        'id' => 'ID不能为空',
        'meal_id' => '充值选项不能为空',
        'meal_discount' => '充值选项不能为空',
        'meal_discounted_price' => '充值选项不能为空',
        'money.require' => '请填写充值金额',
        'money.gt' => '请填写大于0的充值金额',
        'phone.require' => '手机号不能为空',
        'phone.regex' => '请正确输入手机号',
        'batch_data' => '请正确输入手机号',
        'number.require' => '户号不能为空',
        'area' => '地区不能为空',
        'type' => '订单类型不能为空',
    ];

    /**
     * 列表
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneList()
    {
        return $this->only(['type']);
    }

    /**
     * 列表
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneId()
    {
        return $this->only(['id']);
    }

    /**
     * 话费验证
     *
     * @return ConsumeRechargeValidate
     */
    public function scenePhoneRecharge()
    {
        return $this->only(['meal_id', 'meal_discount', 'meal_discounted_price', 'money', 'phone']);
    }

    /**
     * 批量话费验证
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneBatchPhoneRecharge()
    {
        return $this->only(['meal_id', 'meal_discount', 'meal_discounted_price', 'money', 'batch_data']);
    }

    /**
     * 电费验证
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneElectricityRecharge()
    {
        return $this->only(['meal_id', 'meal_discount', 'meal_discounted_price', 'money', 'number', 'area']);
    }

    /**
     * 批量话费验证
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneBatchElectricityRecharge()
    {
        return $this->only(['meal_id', 'meal_discount', 'meal_discounted_price', 'money', 'batch_data']);
    }

    /**
     * 礼品卡充值验证
     *
     * @return ConsumeRechargeValidate
     */
    public function sceneCardRecharge()
    {
        return $this->only(['name', 'buy_price', 'pay_price']);
    }

    /**
     * 校验金额
     *
     * @param $money
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkMoney($money, $rule, $data)
    {
        $status = ConfigService::get('recharge', 'status', 0);
        if (!$status) {
            return '充值功能已关闭';
        }

        $minAmount = ConfigService::get('recharge', 'min_amount', 0);

        if ($money < $minAmount) {
            return '最低充值金额' . $minAmount . "元";
        }

        return true;
    }
}
