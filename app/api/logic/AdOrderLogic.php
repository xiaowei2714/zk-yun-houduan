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
     * @param $adIds
     * @param $status
     * @return array|false
     */
    public static function getCountData($adIds, $status = '')
    {
        try {
            $obj = AdOrder::field([
                'ad_id',
                'count(*) as cou',
            ])->whereIn('ad_id', $adIds);

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
     * 总数
     *
     * @param $adId
     * @return false|int
     */
    public static function geCompleteCount($adId)
    {
        try {
            $startTime = strtotime(date('Y-m-d 00:00:00', time() - 29 * 24 * 3600));

            return AdOrder::where('ad_id', '=', $adId)
                ->where('create_time', '>=', $startTime)
                ->count();

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
     * @return AdOrder|array|false|Model|null
     */
    public static function geCompleteSumData($adId)
    {
        try {
            $startTime = strtotime(date('Y-m-d 00:00:00', time() - 29 * 24 * 3600));

            return AdOrder::field([
                'count(*) as cou',
                'sum(`create_time`) as time',
                'sum(`pay_time`) as pay_time',
                'sum(`complete_time`) as complete_time'
            ])->where('ad_id', '=', $adId)
                ->where('create_time', '>=', $startTime)
                ->where('status', '=', 4)
                ->find();

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
                $obj = $obj->where($aliasD . 'order_no', 'like', '%' . $params['search'] . '%');
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

            // 获取广告余额
            $adInfo = UserAd::where('id', $adInfo['id'])->find();
            if ($adInfo['left_num'] < 0) {
                self::setError('广告剩余额度不足');
                Db::rollback();
                return false;
            }

            $orderNo = self::generateOrderNo($params['user_id']);

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
                self::setError('购买失败，新增通知失败');
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
     * @param $id
     * @param $operUserId
     * @return bool
     */
    public static function paySuccessOrder($id, $operUserId)
    {
        try {
            Db::startTrans();

            // 广告订单详情
            $adOrderInfo = self::info($id);
            if (empty($adOrderInfo['id'])) {
                self::setError('设置失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['user_id'] != $operUserId) {
                self::setError('设置失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] < 0 || $adOrderInfo['status'] > 5) {
                self::setError('设置失败，订单状态异常');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 2) {
                self::setError('设置失败，当前订单已支付成功');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 4) {
                self::setError('设置失败，当前订单已完成');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 5) {
                self::setError('设置失败，当前订单已取消');
                Db::rollback();
                return false;
            }

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
     * @param $id
     * @param $operUserId
     * @return bool
     */
    public static function completeOrder($id, $operUserId)
    {
        try {
            Db::startTrans();

            // 广告订单详情
            $adOrderInfo = self::info($id);
            if (empty($adOrderInfo['id'])) {
                self::setError('确认收款失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['to_user_id'] != $operUserId) {
                self::setError('确认收款失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] < 0 || $adOrderInfo['status'] > 5) {
                self::setError('确认收款失败，订单状态异常');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 4) {
                self::setError('确认收款失败，当前订单已完成');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 5) {
                self::setError('确认收款失败，当前订单已取消');
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

            // 获取买方信息
            $buyUserInfo = User::where('id', $adOrderInfo['user_id'])->find();

            // 卖方流水
            $billData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'type' => 13,
                'desc' => $buyUserInfo['sn'] . ' 购买Y币扣除',
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
                self::setError('确认收款失败，增加用户余额失败');
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
                self::setError('确认收款失败，记录流水失败');
                Db::rollback();
                return false;
            }

            // 买方消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeData = [
                'user_id' => $adOrderInfo['user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已确认收款',
                'content' => '您的订单已由卖家确认收款，订单尾号 ' . $orderNoSub,
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

            // 卖方消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已确认收款',
                'content' => '购买订单您已确认收款，订单尾号 ' . $orderNoSub,
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

            // 订单状态更改
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
     * @param $id
     * @param null $operUserId
     * @param bool $isSeller
     * @return bool
     */
    public static function cancelOrder($id, $operUserId = null, $isSeller = false)
    {
        try {
            Db::startTrans();

            // 广告订单详情
            $adOrderInfo = self::info($id);
            if (empty($adOrderInfo['id'])) {
                self::setError('取消失败，订单不存在');
                Db::rollback();
                return false;
            }
            if ($operUserId != null) {
                if (!$isSeller && $adOrderInfo['user_id'] != $operUserId) {
                    self::setError('取消失败，订单不存在');
                    Db::rollback();
                    return false;
                }

                if ($isSeller && $adOrderInfo['to_user_id'] != $operUserId) {
                    self::setError('取消失败，订单不存在');
                    Db::rollback();
                    return false;
                }
            }
            if ($adOrderInfo['status'] < 0 || $adOrderInfo['status'] > 5) {
                self::setError('取消失败，订单状态异常');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 4) {
                self::setError('取消失败，当前订单状态为已完成');
                Db::rollback();
                return false;
            }
            if ($adOrderInfo['status'] == 5) {
                self::setError('取消失败，当前订单状态为已取消');
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
                self::setError('取消失败，返还广告额度失败');
                Db::rollback();
                return false;
            }

            // 消息通知
            $orderNoSub = substr($adOrderInfo['order_no'], -4);
            $noticeContent = '';
            if (empty($operUserId)) {
                $noticeContent = '广告订单已到时自动取消，订单尾号 ' . $orderNoSub;
            } else {
                if ($isSeller) {
                    $noticeContent = '广告订单已由卖方取消，订单尾号 ' . $orderNoSub;
                } else {
                    $noticeContent = '广告订单已由买方取消，订单尾号 ' . $orderNoSub;
                }
            }

            $noticeData = [
                'user_id' => $adOrderInfo['to_user_id'],
                'title' => '订单 ' . $orderNoSub . ' 已取消',
                'content' => $noticeContent,
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 2
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('取消失败，发放通知失败');
                Db::rollback();
                return false;
            }

            if ($isSeller) {
                $noticeData = [
                    'user_id' => $adOrderInfo['user_id'],
                    'title' => '订单 ' . $orderNoSub . ' 已取消',
                    'content' => '广告订单已由卖方取消，订单尾号 ' . $orderNoSub,
                    'scene_id' => 0,
                    'read' => 0,
                    'recipient' => 1,
                    'send_type' => 1,
                    'notice_type' => 1,
                    'type' => 2
                ];

                $res = NoticeRecord::create($noticeData);
                if (empty($res)) {
                    self::setError('取消失败，发放通知失败');
                    Db::rollback();
                    return false;
                }
            }

            $cancelType = 1;
            if (!empty($operUserId)) {
                $cancelType = 2;
            }
            if ($isSeller) {
                $cancelType = 3;
            }

            $params = [
                'status' => 5,
                'cancel_type' => $cancelType,
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
