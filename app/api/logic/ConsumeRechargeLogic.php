<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\ConsumeRecharge;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use think\facade\Db;
use think\facade\Log;
use Exception;

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
            Db::startTrans();

            // 扣除用户余额
            $res = User::where('id', $params['user_id'])->dec('user_money', $params['meal_discounted_price'])->update([
                'update_time' => time()
            ]);
            if (!$res) {
                throw new Exception('扣除用户余额失败');
            }

            // 获取用户余额
            $userInfo = User::where('id', $params['user_id'])->find();
            if ($userInfo['user_money'] < 0) {
                throw new Exception('用户余额不足');
            }

            $sn = generate_sn(ConsumeRecharge::class, 'sn');

            // 流水
            $userAccountData = [
                'sn' => generate_sn(ConsumeRecharge::class, 'sn'),
                'user_id' => $params['user_id'],
                'change_object' => 1,
                'change_type' => 0,
                'action' => 2,
                'change_amount' => $params['meal_discounted_price'],
                'left_amount' => $userInfo['user_money'],
                'source_sn' => $sn,
                'remark' => $params['type'] == 1 ? '话费充值扣款' : '电费充值扣款',
                'extra' => 'consume_recharge'
            ];

            $res = UserAccountLog::create($userAccountData);
            if (empty($res['id'])) {
                throw new Exception('流水表失败');
            }

            // 消费充值表
            $consumeRechargeData = [
                'sn' => $sn,
                'user_id' => $params['user_id'],
                'account' => $params['account'],
                'name_area' => $params['name_area'],
                'recharge_price' => $params['money'],
                'status' => 1,
                'meal_id' => $params['meal_id'],
                'meal_discount' => $params['meal_discount'],
                'pay_price' => $params['meal_discounted_price'],
                'type' => $params['type'],
                'recharge_up_price' => $params['recharge_up_price'] ?? 0,
                'account_type' => $params['account_type'] ?? ''
            ];

            $order = ConsumeRecharge::create($consumeRechargeData);
            if (empty($order['id'])) {
                throw new Exception('充值表失败');
            }

            Db::commit();

            return [
                'id' => (int)$order['id'],
            ];

        } catch (Exception $e) {
            Log::record('Exception: SqlRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            Db::rollback();
            return false;
        }
    }
}
