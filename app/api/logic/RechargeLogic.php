<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\Recharge;
use app\common\model\user\User;
use app\common\service\ConfigService;
use Exception;
use think\facade\Log;
use think\Model;

/**
 * 充值逻辑层
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class RechargeLogic extends BaseLogic
{
    /**
     * 列表
     *
     * @param $userId
     * @return array|false
     */
    public static function list($userId)
    {
        try {
            return Recharge::field([
                'id',
                'pay_money',
                'status',
                'create_time',
            ])->where('user_id', '=', $userId)
                ->order('id desc')
                ->limit(100)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-RechargeLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取列表异常');
            return false;
        }
    }

    /**
     * 详情
     *
     * @param $id
     * @return Recharge|array|false|Model|null
     */
    public static function info($id)
    {
        try {
            return Recharge::where('id', '=', $id)->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-RechargeLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取详情异常');
            return false;
        }
    }

    /**
     * 充值
     *
     * @param array $params
     * @return false|mixed
     */
    public static function recharge(array $params)
    {
        try {
            $orderNo = self::generateOrderNo($params['user_id']);

            $rechargeParams = [
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'pay_money' => $params['pay_money'],
                'order_no' => $orderNo,
                'status' => 1
            ];

            $order = Recharge::create($rechargeParams);
            if (empty($order['id'])) {
                self::setError('充值失败');
                return false;
            }

            return $order['id'];

        } catch (Exception $e) {
            Log::record('Exception: Sql-RechargeLogic-recharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('充值异常');
            return false;
        }
    }

    /**
     * @notes 充值配置
     * @param $userId
     * @return array
     * @author 段誉
     * @date 2023/2/24 16:56
     */
    public static function config($userId)
    {
        $userMoney = User::where(['id' => $userId])->value('user_money');
        $minAmount = ConfigService::get('recharge', 'min_amount', 0);
        $status = ConfigService::get('recharge', 'status', 0);

        return [
            'status' => $status,
            'min_amount' => $minAmount,
            'user_money' => $userMoney,
        ];
    }

    /**
     * 交易生成唯一订单号
     *
     * @param $userId
     * @return string
     */
    private static function generateOrderNo($userId): string
    {
        return 'J'
            . date('smHyid')
            . substr(str_pad($userId, 6, '0', STR_PAD_LEFT), -6)
            . mt_rand(100, 999);
    }
}
