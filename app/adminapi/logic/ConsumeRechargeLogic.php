<?php

namespace app\adminapi\logic;

use app\common\model\ConsumeRecharge;
use app\common\model\Recharge;
use app\common\logic\BaseLogic;
use think\facade\Db;
use think\Model;

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
            return ConsumeRecharge::field(['id', 'sn', 'status'])
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
            $data = [
                'status' => 4,
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
     * 设置为批量失败
     *
     * @param $ids
     * @return bool
     */
    public static function setBatchFail($ids): bool
    {
        try {
            $data = [
                'status' => 4,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::whereIn('id', $ids)->update($data);

            return !empty($res);

        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
