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
     * 列表
     *
     * @param $userId
     * @param $type
     * @param $status
     * @param $search
     * @return array|false
     */
    public static function list($userId, $type, $status, $search)
    {
        try {
            $obj = ConsumeRecharge::field([
                'id',
                'sn',
                'user_id',
                'account',
                'account_type',
                'name_area',
                'recharge_price',
                'recharge_up_price',
                'recharge_down_price',
                'balances_price',
                'pay_price',
                'status',
                'type',
                'pay_time',
                'create_time'
            ])
                ->where('user_id', '=', $userId)
                ->where('type', '=', $type);

            if ($status !== null && $status !== '') {
                $obj = $obj->where('status', '=', $status);
            }

            if ($search !== null && $search !== '') {
                $obj = $obj->where('account|sn', 'like', '%' . $search . '%');
            }

            return $obj->order('id desc')
                ->limit(15)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 数量
     *
     * @param $userId
     * @return array|false
     */
    public static function groupCount($userId)
    {
        try {
            return ConsumeRecharge::field([
                'type',
                'status',
                'count(*) as cou'
            ])
            ->where('user_id', '=', $userId)
                ->whereIn('status', [2, 3, 4])
                ->group(['type', 'status'])
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: SqlList Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 详情
     *
     * @param $id
     * @return array|false
     */
    public static function info($id)
    {
        try {
            return ConsumeRecharge::where('id', '=', $id)->find();
        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 充值
     *
     * @param array $params
     * @return false|int[]
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
                self::setError('扣除账户余额失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $params['user_id'])->find();
            if ($userInfo['user_money'] < 0) {
                self::setError('账户余额不足');
                Db::rollback();
                return false;
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
                self::setError('记录流水失败');
                Db::rollback();
                return false;
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
                throw new Exception('充值失败');
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-recharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('充值失败');
            Db::rollback();
            return false;
        }
    }

    /**
     * 更新余额
     *
     * @param $id
     * @param $price
     * @param $queryPrice
     * @return bool
     */
    public static function setBalance($id, $price, $queryPrice): bool
    {
        try {
            Db::startTrans();

            $consumeRechargeInfo = ConsumeRecharge::where('id', $id)->find();
            if (empty($consumeRechargeInfo)) {
                self::setError('取消失败，获取不到该订单');
                Db::rollback();
                return false;
            }

            // 扣除用户余额
            $res = User::where('id', $consumeRechargeInfo['user_id'])->dec('user_money', $queryPrice)->update([
                'update_time' => time()
            ]);
            if (!$res) {
                self::setError('扣除账户余额失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $consumeRechargeInfo['user_id'])->find();
            if ($userInfo['user_money'] < 0) {
                self::setError('账户余额不足');
                Db::rollback();
                return false;
            }

            // 流水
            $userAccountData = [
                'sn' => generate_sn(ConsumeRecharge::class, 'sn'),
                'user_id' => $consumeRechargeInfo['user_id'],
                'change_object' => 1,
                'change_type' => 0,
                'action' => 2,
                'change_amount' => $queryPrice,
                'left_amount' => $userInfo['user_money'],
                'source_sn' => $consumeRechargeInfo['sn'],
                'remark' => $consumeRechargeInfo['type'] == 1 ? '查询话费充值扣款' : '查询电费充值扣款',
                'extra' => 'consume_recharge_query'
            ];

            $res = UserAccountLog::create($userAccountData);
            if (empty($res['id'])) {
                self::setError('记录流水失败');
                Db::rollback();
                return false;
            }

            // 消费充值表
            $userAccountData = [
                'recharge_down_price' => $price,
                'update_time' => time()
            ];

            if ($consumeRechargeInfo['recharge_first_down_price'] === null) {
                $userAccountData['recharge_first_down_price'] = $price;
            }

            $res = ConsumeRecharge::where('id', $id)->update($userAccountData);
            if (empty($res)) {
                self::setError('更改余额失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-setBalance Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('记录失败');
            Db::rollback();
            return false;
        }
    }

    /**
     * 取消
     *
     * @param $id
     * @param $userId
     * @return bool
     */
    public static function cancel($id, $userId)
    {
        try {
            Db::startTrans();

            $info = ConsumeRecharge::where('id', '=', $id)->find();
            if (empty($info['id'])) {
                self::setError('取消失败，获取不到该订单');
                Db::rollback();
                return false;
            }
            if ($info['user_id'] != $userId) {
                self::setError('取消失败，获取不到该订单');
                Db::rollback();
                return false;
            }
            if ($info['status'] == 2) {
                self::setError('取消失败，该订单正在充值中');
                Db::rollback();
                return false;
            }
            if ($info['status'] == 3) {
                self::setError('取消失败，该订单已充值成功');
                Db::rollback();
                return false;
            }
            if ($info['status'] == 4) {
                self::setError('取消失败，该订单已充值失败');
                Db::rollback();
                return false;
            }

            $res = ConsumeRecharge::where('id', '=', $id)->update([
                'status' => 4,
                'user_cancel_time' => time(),
                'update_time' => time()
            ]);
            if (!$res) {
                self::setError('取消失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-cancel Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            Db::rollback();
            return false;
        }
    }
}
