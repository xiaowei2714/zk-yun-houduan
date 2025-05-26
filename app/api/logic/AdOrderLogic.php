<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\AdOrder;
use app\common\model\notice\NoticeRecord;
use app\common\model\user\User;
use app\common\model\UserAd;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use Exception;
use think\Model;

/**
 * 广告订单
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class AdOrderLogic extends BaseLogic
{
    /**
     * 总数
     *
     * @param $toUserId
     * @param string $status
     * @param bool $isRangeComplete
     * @return false|int
     */
    public static function getCount($toUserId, $status = '', $isRangeComplete = false): bool|int
    {
        try {
            $obj = AdOrder::where('to_user_id', '=', $toUserId);

            if (!empty($status)) {
                $obj = $obj->where('status', '=', $status);
            }

            if ($isRangeComplete) {
                $obj = $obj->where('is_range_complete', '=', 1);
            }

            return $obj->count();

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取数据异常');
            return false;
        }
    }

    /**
     * 总数
     *
     * @param $adId
     * @param $status
     * @return array|false
     */
    public static function getCountData($adId, $status = '')
    {
        try {
            $obj = AdOrder::field([
                'ad_id',
                'count(*) as cou',
            ])->whereIn('ad_id', $adId);

            if (!empty($status)) {
                $obj = $obj->where('status', '=', $status);
            }

            return $obj->group('ad_id')->select()->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取数据异常');
            return false;
        }
    }

    /**
     * 列表
     *
     * @param $userId
     * @param $status
     * @param $search
     * @return array|false
     */
    public static function list(array $params)
    {
        try {
            $alias = 'ao';
            $aliasD = $alias . '.';
            $obj = AdOrder::field([
                $aliasD . 'id',
                $aliasD . 'user_id',
                $aliasD . 'order_no',
                $aliasD . 'order_type',
                $aliasD . 'num',
                $aliasD . 'price',
                $aliasD . 'dan_price',
                $aliasD . 'pay_type',
                $aliasD . 'status',
                $aliasD . 'expire_time',
                $aliasD . 'create_time',
                'u.nickname as sell_nickname',
                'uu.nickname as buy_nickname',
            ])->alias($alias)
                ->leftJoin('user u', $aliasD . 'to_user_id = u.id')
                ->leftJoin('user uu', $aliasD . 'user_id = uu.id')
                ->where('user_id|to_user_id', '=', $params['user_id']);

            if (!empty($params['status'])) {
                if ($params['status'] != 3) {
                    $obj = $obj->where($aliasD . 'status', '=', $params['status']);
                } else {
                    $obj = $obj->where($aliasD . 'appeal', '=', 1);
                }
            }

            if (!empty($params['search'])) {
                $obj = $obj->where($aliasD . 'oder_no', 'like', '%' . $params['search'] . '%');
            }

            if (!empty($params['last_id'])) {
                $obj = $obj->where($aliasD . 'id', '<', $params['last_id']);
            }

            return $obj->order($aliasD . 'id desc')
                ->limit($params['limit'])
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取数据异常');
            return false;
        }
    }

    /**
     * 详情
     *
     * @param $id
     * @return AdOrder|array|false|Model|null
     */
    public static function infoHaveUser($id): bool|array|AdOrder|Model|null
    {
        try {
            $alias = 'ao';
            $aliasD = $alias . '.';
            return AdOrder::field([
                $aliasD . 'id',
                $aliasD . 'order_no',
                $aliasD . 'user_id',
                $aliasD . 'to_user_id',
                $aliasD . 'ad_id',
                $aliasD . 'status',
                $aliasD . 'order_type',
                $aliasD . 'price',
                $aliasD . 'dan_price',
                $aliasD . 'pay_type',
                $aliasD . 'num',
                $aliasD . 'expire_time',
                $aliasD . 'cancel_type',
                $aliasD . 'create_time',
                'u.nickname as sell_nickname',
                'uu.nickname as buy_nickname',
                'ua.tips',
            ])
                ->alias($alias)
                ->leftJoin('user u', $aliasD . 'to_user_id = u.id')
                ->leftJoin('user uu', $aliasD . 'user_id = uu.id')
                ->leftJoin('user_ad ua', $aliasD . 'ad_id = ua.id')
                ->where($aliasD . 'id', '=', $id)
                ->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-infoHaveUser Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取数据异常');
            return false;
        }
    }

    /**
     * 详情
     *
     * @param $id
     * @return AdOrder|array|false|Model|null
     */
    public static function info($id): bool|array|AdOrder|Model|null
    {
        try {
            return AdOrder::where('id', '=', $id)->find();
        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取数据异常');
            return false;
        }
    }

    /**
     * 购买订单
     *
     * @param $adInfo
     * @param array $params
     * @return AdOrder|false|Model
     */
    public static function addOrder($adInfo, array $params)
    {
        try {
            Db::startTrans();

            // 扣除用户余额
            $res = User::where('id', $adInfo['user_id'])
                ->dec('freeze_money', $params['buy_num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('扣除用户冻结资产失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $adInfo['user_id'])->find();
            if ($userInfo['freeze_money'] < 0) {
                self::setError('账户冻结资产不足');
                Db::rollback();
                return false;
            }

            $orderNo = self::generateOrderNo($params['user_id']);

            // 流水
            $billData = [
                'user_id' => $adInfo['user_id'],
                'type' => 13,
                'desc' => '购买广告扣除冻结资产',
                'change_type' => 2,
                'change_money' => $params['buy_num'],
                'changed_money' => $userInfo['freeze_money'],
                'source_sn' => $orderNo
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('记录流水失败');
                Db::rollback();
                return false;
            }

            // 扣除广告剩余额
            $res = UserAd::where('id', $adInfo['id'])
                ->dec('left_num', $params['buy_num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('扣除广告剩余额度失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $adInfo = UserAd::where('id', $adInfo['id'])->find();
            if ($adInfo['left_num'] < 0) {
                self::setError('广告剩余额度不足');
                Db::rollback();
                return false;
            }

            // 消息通知
            $orderNoSub = substr($orderNo, -4);
            $noticeData = [
                'user_id' => $adInfo['user_id'],
                'title' => '买家已下单，请等待付款 ' . $orderNoSub,
                'content' => '订单尾号 ' . $orderNoSub . ' ，订单数量 ' . $params['buy_num'] . ' Y币，付款金额：' . $params['price'],
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('设置失败，新增通知失败');
                Db::rollback();
                return false;
            }

            // 广告订单
            $userAdParams = [
                'user_id' => $params['user_id'],
                'to_user_id' => $adInfo['user_id'],
                'ad_id' => $adInfo['id'],
                'order_no' => $orderNo,
                'order_type' => $adInfo['type'] == 1 ? 2 : 1,
                'num' => $params['buy_num'],
                'price' => $params['price'],
                'dan_price' => $adInfo['price'],
                'pay_type' => $params['pay_type'],
                'status' => 1,
                'expire_time' => time() + $adInfo['pay_time'] * 60,
            ];

            $order = AdOrder::create($userAdParams);
            if (empty($order['id'])) {
                self::setError('购买失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return $order;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-addOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('购买异常');
            Db::rollback();
            return false;
        }
    }

    /**
     * 设置订单成功
     *
     * @param $adInfo
     * @return bool
     */
    public static function paySuccessOrder($adOrderInfo)
    {
        try {
            Db::startTrans();

            $orderNoSub = substr($adOrderInfo['order_no'], -4);

            // 消息通知
            $noticeData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'title' => '买家已付款，请放行 ' . $orderNoSub,
                'content' => '订单尾号 ' . $orderNoSub . ' ，订单数量 ' . $adOrderInfo['num'] . ' Y币，买家已标记为付款。请登录收款账户查看到账情况，若已到账请尽快放行。恶意延退放币将触发相应机制，影响账户正常使用',
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('设置失败，新增通知失败');
                Db::rollback();
                return false;
            }

            $params = [
                'status' => 2,
                'pay_time' => time(),
                'update_time' => time()
            ];

            $res = AdOrder::where('id', $adOrderInfo['id'])->update($params);
            if (empty($res)) {
                self::setError('设置失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-paySuccessOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('设置异常');
            Db::rollback();
            return false;
        }
    }

    /**
     * 申诉订单
     *
     * @param $adInfo
     * @return bool
     */
    public static function appealOrder($adInfo)
    {
        try {
            $params = [
                'appeal' => 1,
                'appeal_time' => time(),
                'update_time' => time()
            ];

            $res = AdOrder::where('id', $adInfo['id'])->update($params);
            if (empty($res)) {
                self::setError('申诉失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdOrderLogic-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('申诉异常');
            return false;
        }
    }

    /**
     * 完成订单
     *
     * @param $adOrderInfo
     * @return bool
     */
    public static function completeOrder($adOrderInfo)
    {
        try {
            Db::startTrans();

            // 增加用户余额
            $res = User::where('id', $adOrderInfo['user_id'])
                ->inc('user_money', $adOrderInfo['num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('确认收款失败，增加用户余额失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $adOrderInfo['user_id'])->find();

            // 流水
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
                self::setError('确认收款失败，记录流水失败');
                Db::rollback();
                return false;
            }

            // 消息通知
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
                self::setError('确认收款失败，新增通知失败');
                Db::rollback();
                return false;
            }

            // 消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已确认收款',
                'content' => '购买订单您已主动确认收款，订单尾号 ' . $orderNoSub,
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('确认收款失败，新增通知失败');
                Db::rollback();
                return false;
            }

            $completeTime = time();
            $params = [
                'status' => 4,
                'complete_time' => $completeTime,
                'update_time' => $completeTime
            ];

            if ($completeTime - strtotime($adOrderInfo['create_time']) <= 300) {
                $params['is_range_complete'] = 1;
            }

            $res = AdOrder::where('id', $adOrderInfo['id'])->update($params);
            if (empty($res)) {
                self::setError('确认收款失败');
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
     * @param $adOrderInfo
     * @param $isUserOper
     * @return bool
     */
    public static function cancelOrder($adOrderInfo, $isUserOper = true)
    {
        try {
            Db::startTrans();

            // 返还用户余额
            $res = User::where('id', $adOrderInfo['user_id'])
                ->inc('freeze_money', $adOrderInfo['num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('设置失败，返还用户冻结资产失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $adOrderInfo['user_id'])->find();

            // 流水
            $billData = [
                'user_id' => $adOrderInfo['user_id'],
                'type' => 13,
                'desc' => '取消购买广告返还冻结资产',
                'change_type' => 1,
                'change_money' => $adOrderInfo['num'],
                'changed_money' => $userInfo['freeze_money'],
                'source_sn' => $userInfo['order_no']
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('设置失败，记录流水失败');
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
                self::setError('设置失败，返还广告剩余额度失败');
                Db::rollback();
                return false;
            }

            // 消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已取消',
                'content' => '您的订单已被买家取消，订单尾号 ' . $orderNoSub,
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('设置失败，新增通知失败');
                Db::rollback();
                return false;
            }

            $params = [
                'status' => 5,
                'cancel_type' => $isUserOper ? 2 : 1,
                'cancel_time' => time(),
                'update_time' => time()
            ];

            $res = AdOrder::where('id', $adOrderInfo['id'])->update($params);
            if (empty($res)) {
                self::setError('取消失败');
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

    /**
     * 交易生成唯一订单号
     *
     * @param $userId
     * @return string
     */
    private static function generateOrderNo($userId): string
    {
        return 'J'
            . date('syHmid')
            . substr(str_pad($userId, 6, '0', STR_PAD_LEFT), -6)
            . mt_rand(100, 999);
    }
}
