<?php

namespace app\adminapi\logic;

use app\common\model\ConsumeRecharge;
use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use think\facade\Db;
use think\facade\Log;
use think\Model;
use Exception;

/**
 * Recharge逻辑
 * Class RechargeLogic
 * @package app\adminapi\logic
 */
class ConsumeRechargeLogic extends BaseLogic
{
    /**
     * 获取详情
     *
     * @param $id
     * @return ConsumeRecharge|array|false|Model|null
     */
    public static function info($id)
    {
        try {
            return ConsumeRecharge::where('id', $id)->find();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取数据
     *
     * @param $ids
     * @return array|false
     */
    public static function getData($ids)
    {
        try {
            return ConsumeRecharge::field(['id', 'sn', 'status', 'type', 'account', 'name_area'])
                ->whereIn('id', $ids)
                ->select()
                ->toArray();
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为充值中
     *
     * @param $id
     * @return bool
     */
    public static function setRecharging($id): bool
    {
        try {
            $data = [
                'status' => 2,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $id)->update($data);

            return !empty($res);

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为批量充值中
     *
     * @param $ids
     * @return bool
     */
    public static function setBatchRecharging($ids): bool
    {
        try {
            $data = [
                'status' => 2,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::whereIn('id', $ids)->update($data);

            return !empty($res);

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为成功
     *
     * @param $id
     * @return bool
     */
    public static function setSuccess($id): bool
    {
        try {
            $data = [
                'status' => 3,
                'balances_price' => Db::raw('recharge_price'),
                'pay_time' => time(),
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $id)->update($data);

            return !empty($res);

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为批量成功
     *
     * @param $ids
     * @return bool
     */
    public static function setBatchSuccess($ids): bool
    {
        try {
            $data = [
                'status' => 3,
                'balances_price' => Db::raw('recharge_price'),
                'pay_time' => time(),
                'update_time' => time()
            ];

            $res = ConsumeRecharge::whereIn('id', $ids)->update($data);

            return !empty($res);

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为失败
     *
     * @param $id
     * @return bool
     */
    public static function setFail($id): bool
    {
        try {
            Db::startTrans();

            $consumeRechargeInfo =  ConsumeRecharge::where('id', $id)->find();
            if (empty($consumeRechargeInfo)) {
                throw new Exception('获取不到充值信息ID：' . $id );
            }
            if ($consumeRechargeInfo['status'] == 4) {
                return true;
            }

            // 返回用户余额
            $res = User::where('id', $consumeRechargeInfo['user_id'])->inc('user_money', $consumeRechargeInfo['pay_price'])->update([
                'update_time' => time()
            ]);
            if (!$res) {
                throw new Exception('返还扣除的用户余额失败');
            }

            // 获取用户余额
            $userInfo = User::where('id', $consumeRechargeInfo['user_id'])->find();
            if ($userInfo['user_money'] < 0) {
                throw new Exception('用户余额不足');
            }

            // 流水
            $userAccountData = [
                'sn' => generate_sn(ConsumeRecharge::class, 'sn'),
                'user_id' => $consumeRechargeInfo['user_id'],
                'change_object' => 1,
                'change_type' => 0,
                'action' => 1,
                'change_amount' => $consumeRechargeInfo['pay_price'],
                'left_amount' => $userInfo['user_money'],
                'source_sn' => $consumeRechargeInfo['sn'],
                'remark' => $consumeRechargeInfo['type'] == 1 ? '话费充值失败返还' : '电费充值失败返还',
                'extra' => 'consume_recharge'
            ];

            $res = UserAccountLog::create($userAccountData);
            if (empty($res['id'])) {
                throw new Exception('流水表失败');
            }

            // 消费充值表
            $userAccountData = [
                'status' => 4,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $id)->update($userAccountData);
            if (empty($res)) {
                throw new Exception('更改充值表失败');
            }

            Db::commit();
            return true;

        } catch (\Exception $e) {
            Log::record('Exception: SqlRecharge Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            Db::rollback();
            return false;
        }
    }

    /**
     * 更新余额
     *
     * @param $id
     * @param $price
     * @return bool
     */
    public static function setBalance($id, $price): bool
    {
        try {
            Db::startTrans();

            $consumeRechargeInfo =  ConsumeRecharge::where('id', $id)->find();
            if (empty($consumeRechargeInfo)) {
                throw new Exception('获取不到充值信息ID：' . $id );
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
                throw new Exception('更改余额失败');
            }

            Db::commit();
            return true;

        } catch (\Exception $e) {
            Log::record('Exception: setBalance Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            Db::rollback();
            return false;
        }
    }
}
