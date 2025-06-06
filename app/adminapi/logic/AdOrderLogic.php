<?php

namespace app\adminapi\logic;


use app\common\model\AdOrder;
use app\common\logic\BaseLogic;
use app\common\model\notice\NoticeRecord;
use app\common\model\user\User;
use app\common\model\UserAd;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use think\Model;
use Exception;

/**
 * AdOrder逻辑
 * Class AdOrderLogic
 * @package app\adminapi\logic
 */
class AdOrderLogic extends BaseLogic
{
    /**
     * @notes 添加
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            AdOrder::create([
                'user_id' => $params['user_id'],
                'to_user_id' => $params['to_user_id'],
                'ad_id' => $params['ad_id'],
                'order_no' => $params['order_no'],
                'order_type' => $params['order_type'],
                'num' => $params['num'],
                'price' => $params['price'],
                'dan_price' => $params['dan_price'],
                'pay_type' => $params['pay_type'],
                'status' => $params['status'],
                'cancel_type' => $params['cancel_type']
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
     * @date 2025/05/06 17:29
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            AdOrder::where('id', $params['id'])->update([
                'user_id' => $params['user_id'],
                'to_user_id' => $params['to_user_id'],
                'ad_id' => $params['ad_id'],
                'order_no' => $params['order_no'],
                'order_type' => $params['order_type'],
                'num' => $params['num'],
                'price' => $params['price'],
                'dan_price' => $params['dan_price'],
                'pay_type' => $params['pay_type'],
                'status' => $params['status'],
                'cancel_type' => $params['cancel_type']
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
     * @date 2025/05/06 17:29
     */
    public static function delete(array $params): bool
    {
        return AdOrder::destroy($params['id']);
    }


    /**
     * @notes 获取详情
     * @param $params
     * @return array
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public static function detail($params): array
    {
        return AdOrder::findOrEmpty($params['id'])->toArray();
    }

    /**
     * 完成订单
     *
     * @param $id
     * @param $adminId
     * @return bool
     */
    public static function completeOrder($id, $adminId)
    {
        try {
            Db::startTrans();

            // 广告订单详情
            $adOrderInfo = self::detail(['id' => $id]);
            if (empty($adOrderInfo['id'])) {
                self::setError('确认收款失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ((int)$adOrderInfo['status'] < 0 || (int)$adOrderInfo['status'] > 5) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败，订单状态异常');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 4) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败，当前订单已完成');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 5) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败，当前订单已取消');
                Db::rollback();
                return false;
            }

            // 扣除卖方冻结余额
            $res = User::where('id', $adOrderInfo['to_user_id'])
                ->dec('freeze_money', $adOrderInfo['num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('扣除卖方冻结资产失败');
                Db::rollback();
                return false;
            }

            // 获取卖方冻结余额
            $userInfo = User::where('id', $adOrderInfo['to_user_id'])->find();
            if ($userInfo['freeze_money'] < 0) {
                self::setError('卖方账户冻结资产不足');
                Db::rollback();
                return false;
            }

            // 卖方流水
            $billData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'type' => 13,
                'desc' => $userInfo['sn'] . ' 购买Y币扣除',
                'change_type' => 2,
                'change_money' => $adOrderInfo['num'],
                'changed_money' => $userInfo['freeze_money'],
                'source_sn' => $adOrderInfo['order_no']
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('记录卖方流水失败');
                Db::rollback();
                return false;
            }

            // 增加买方余额
            $res = User::where('id', $adOrderInfo['user_id'])
                ->inc('user_money', $adOrderInfo['num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败，增加用户余额失败');
                Db::rollback();
                return false;
            }

            // 获取买方余额
            $userInfo = User::where('id', $adOrderInfo['user_id'])->find();

            // 买方流水
            $billData = [
                'user_id' => $adOrderInfo['user_id'],
                'type' => 13,
                'desc' => '购买广告成功',
                'change_type' => 1,
                'change_money' => $adOrderInfo['num'],
                'changed_money' => $userInfo['user_money'],
                'source_sn' => $userInfo['order_no']
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败，记录流水失败');
                Db::rollback();
                return false;
            }

            // 买方消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeData = [
                'user_id' => $adOrderInfo['user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已确认收款',
                'content' => '您的订单已被卖家确认收款，订单尾号 ' . $orderNoSub,
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败，新增通知失败');
                Db::rollback();
                return false;
            }

            // 卖方消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已确认收款',
                'content' => '购买订单已确认收款，订单尾号 ' . $orderNoSub,
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败，新增通知失败');
                Db::rollback();
                return false;
            }

            // 订单状态更改
            $completeTime = time();
            $params = [
                'status' => 4,
                'is_admin_complete' => 1,
                'complete_time' => $completeTime,
                'admin_id' => $adminId,
                'update_time' => $completeTime
            ];

            if ($completeTime - strtotime($adOrderInfo['create_time']) <= 300) {
                $params['is_range_complete'] = 1;
            }

            $res = AdOrder::where('id', $adOrderInfo['id'])->update($params);
            if (empty($res)) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 确认收款失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-completeOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('确认收款异常');
            Db::rollback();
            return false;
        }
    }

    /**
     * 取消订单
     *
     * @param $id
     * @param $adminId
     * @return bool
     */
    public static function cancelOrder($id, $adminId)
    {
        try {
            Db::startTrans();

            // 广告订单详情
            $adOrderInfo = self::detail(['id' => $id]);
            if (empty($adOrderInfo['id'])) {
                self::setError('订单设置失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ((int)$adOrderInfo['status'] < 0 || (int)$adOrderInfo['status'] > 5) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 设置失败，订单状态异常');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 4) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 设置失败，当前订单已完成');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 5) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 设置失败，当前订单已取消');
                Db::rollback();
                return false;
            }

            // 返还广告剩余额
            $res = UserAd::where('id', $adOrderInfo['ad_id'])
                ->inc('left_num', $adOrderInfo['num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 设置失败，返还广告剩余额度失败');
                Db::rollback();
                return false;
            }

            // 消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已取消',
                'content' => '广告订单已取消，订单尾号 ' . $orderNoSub,
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 设置失败，新增通知失败');
                Db::rollback();
                return false;
            }

            $params = [
                'status' => 5,
                'cancel_type' => 4,
                'cancel_time' => time(),
                'admin_id' => $adminId,
                'update_time' => time()
            ];

            $res = AdOrder::where('id', $adOrderInfo['id'])->update($params);
            if (empty($res)) {
                self::setError('订单：' . $adOrderInfo['order_no'] . ' 取消失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('取消异常');
            Db::rollback();
            return false;
        }
    }
}
