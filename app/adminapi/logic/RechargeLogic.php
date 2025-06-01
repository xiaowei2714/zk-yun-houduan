<?php

namespace app\adminapi\logic;

use app\common\model\notice\NoticeRecord;
use app\common\model\Recharge;
use app\common\logic\BaseLogic;
use app\common\model\RechargeApi;
use app\common\model\user\User;
use app\common\model\UserMoneyLog;
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
                'sum(pay_money) as sum'
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

    /**
     * 设置为成功
     *
     * @param $rechargeId
     * @param $adminId
     * @return bool
     */
    public static function setSuccess($rechargeId, $adminId): bool
    {
        try {
            Db::startTrans();

            // 获取充值详情
            $rechargeInfo = Recharge::where('id', $rechargeId)->find();
            if (empty($rechargeInfo['id'])) {
                self::setError('设置失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ($rechargeInfo['status'] == 2) {
                self::setError('订单：' . $rechargeInfo['order_no'] . ' 设置失败，订单已充值成功');
                Db::rollback();
                return false;
            }

            // 更新充值表
            $rechargeParams = [
                'set_success_admin_id' => $adminId,
                'status' => 2,
                'pay_time' => time(),
                'update_time' => time()
            ];

            $res = Recharge::where('id', $rechargeId)->where('status', '=', 1)->update($rechargeParams);
            if (empty($res)) {
                self::setError('订单：' . $rechargeInfo['order_no'] . ' 设置失败，更新充值状态失败');
                Db::rollback();
                return false;
            }

            $addMoney = substr($rechargeInfo['pay_money'], 0, strpos($rechargeInfo['pay_money'], '.') + 4);

            // 增加用户余额
            $res = User::where('id', $rechargeInfo['user_id'])
                ->inc('user_money', (float)$addMoney)
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('订单：' . $rechargeInfo['order_no'] . ' 设置失败，增加用户余额失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $rechargeInfo['user_id'])->find();

            // 流水
            $billData = [
                'user_id' => $rechargeInfo['user_id'],
                'type' => 5,
                'desc' => '充值成功',
                'change_type' => 1,
                'change_money' => $addMoney,
                'changed_money' => $userInfo['user_money'],
                'source_sn' => $rechargeInfo['order_no']
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('订单：' . $rechargeInfo['order_no'] . ' 设置失败，记录流水失败');
                Db::rollback();
                return false;
            }

            // 消息通知
            $noticeData = [
                'user_id' => $rechargeInfo['user_id'],
                'title' => 'Y币充值成功提醒',
                'content' => '您于 ' . date('Y-m-d H:i:s') . ' 成功充值 ' . $rechargeInfo['pay_money'] . ' Y币',
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 3
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('订单：' . $rechargeInfo['order_no'] . ' 设置失败，充值消息失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: sql-RechargeLogic-setSuccess msg: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('系统异常');
            Db::rollback();
            return false;
        }
    }
}
