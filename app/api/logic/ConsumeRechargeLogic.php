<?php

namespace app\api\logic;

use app\common\enum\PayEnum;
use app\common\logic\BaseLogic;
use app\common\model\ConsumeRecharge;
use app\common\model\recharge\RechargeOrder;
use app\common\model\user\User;
use app\common\service\ConfigService;

/**
 * 话费、电费、油费充值逻辑层
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class ConsumeRechargeLogic extends BaseLogic
{
    /**
     * @notes 充值
     * @param array $params
     * @return array|false
     * @author 段誉
     * @date 2023/2/24 10:43
     */
    public static function recharge(array $params)
    {
        try {
            $data = [
                'sn' => generate_sn(ConsumeRecharge::class, 'sn'),
                'user_id' => $params['user_id'],
                'account' => $params['account'],
                'name_area' => $params['name_area'],
                'recharge_price' => $params['money'],
                'status' => 1,
                'meal_id' => $params['meal_id'],
                'meal_discount' => $params['meal_discount'],
                'pay_price' => $params['meal_discounted_price'],
                'type' => $params['type'],
            ];

            $order = ConsumeRecharge::create($data);

            return [
                'id' => (int)$order['id'],
            ];

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
