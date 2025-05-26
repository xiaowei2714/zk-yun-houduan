<?php

namespace app\adminapi\logic;

use app\common\model\Recharge;
use app\common\logic\BaseLogic;
use think\facade\Db;
use think\facade\Log;
use Exception;
use think\Model;

/**
 * Recharge逻辑
 * Class RechargeLogic
 * @package app\adminapi\logic
 */
class RechargeLogic extends BaseLogic
{
    /**
     * 汇总数据
     *
     * @param $statusArr
     * @param $startTime
     * @return Recharge|array|false|Model|null
     */
    public static function getSum($statusArr, $startTime = null)
    {
        try {
            $obj = Recharge::field([
                'count(*) as cou',
                'sum(money) as sum'
            ])->whereIn('status', $statusArr);

            if (!empty($startTime)) {
                $obj = $obj->where('create_time', '>=', $startTime);
            }

            return $obj->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-RechargeLogic-getSum Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 汇总数据
     *
     * @param $statusArr
     * @param $startTime
     * @return array|false
     */
    public static function getGroupSumByDay($statusArr, $startTime = null)
    {
        try {
            $obj = Recharge::field([
                "FROM_UNIXTIME(`create_time`, '%m/%d') date",
                'sum(money) as sum'
            ])->whereIn('status', $statusArr);

            if (!empty($startTime)) {
                $obj = $obj->where('create_time', '>=', $startTime);
            }

            return $obj->group('date')->select()->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-RechargeLogic-getSum Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除未支付订单
     *
     * @param $startTime
     * @return mixed
     */
    public static function deleteUnPayOrder($startTime)
    {
        try {
            $tablePre = env('database.prefix');
            $rechargeTable = $tablePre . 'recharge';

            $curTime = time();
            $sql = <<< EOT
UPDATE $rechargeTable SET `delete_time` = $curTime WHERE `status` = 1 AND `create_time` < $startTime
EOT;

            return Db::execute($sql);

        } catch (Exception $e) {
            Log::record('Exception: Sql-RechargeLogic-deleteUnPayOrder  Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('清理异常');
            return false;
        }
    }

    /**
     * @notes 添加
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            Recharge::create([
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'desc' => $params['desc'],
                'order_no' => $params['order_no'],
                'pay_time' => $params['pay_time'],
                'hash' => $params['hash'],
                'status' => $params['status']
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 编辑
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            Recharge::where('id', $params['id'])->update([
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'desc' => $params['desc'],
                'order_no' => $params['order_no'],
                'pay_time' => $params['pay_time'],
                'hash' => $params['hash'],
                'status' => $params['status']
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 删除
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public static function delete(array $params): bool
    {
        return Recharge::destroy($params['id']);
    }


    /**
     * @notes 获取详情
     * @param $params
     * @return array
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public static function detail($params): array
    {
        return Recharge::findOrEmpty($params['id'])->toArray();
    }
}
