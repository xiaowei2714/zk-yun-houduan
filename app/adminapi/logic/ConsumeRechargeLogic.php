<?php

namespace app\adminapi\logic;

use app\common\model\ConsumeRecharge;
use app\common\logic\BaseLogic;
use app\common\model\notice\NoticeRecord;
use app\common\model\user\User;
use app\common\model\UserMoneyLog;
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
     * 汇总数据
     *
     * @param $createTime
     * @return ConsumeRecharge|array|false|Model|null
     */
    public static function countSum($createTime = null)
    {
        try {
            $obj = ConsumeRecharge::field([
                'sum(`pay_price`) sum',
                'count(*) as cou',
            ])->where('status', '=', 3);

            if (!empty($createTime)) {
                $obj = $obj->where('create_time', '>=', $createTime);
            }

            return $obj->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 汇总数据
     *
     * @param $startTime
     * @return array|false
     */
    public static function getGroupSumByDay($startTime = null)
    {
        try {
            $obj = ConsumeRecharge::field([
                "FROM_UNIXTIME(`create_time`, '%m/%d') date",
                'sum(pay_price) as sum'
            ])->where('status', '=', 3);

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
     * 获取详情
     *
     * @param $id
     * @return ConsumeRecharge|array|false|Model|null
     */
    public static function info($id)
    {
        try {
            return ConsumeRecharge::where('id', $id)->find();
        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 获取详情
     *
     * @param $id
     * @return ConsumeRecharge|array|false|Model|null
     */
    public static function infoHaveUser($id)
    {
        try {
            $alias = 'cr';
            $aliasD = $alias . '.';
            return ConsumeRecharge::field([
                $aliasD . '*',
                'u.p_first_user_id as first_user_id',
                'u.p_second_user_id as second_user_id',
                'u.p_three_user_id as three_user_id',
            ])
                ->alias($alias)
                ->leftJoin('user u', $aliasD . 'user_id = u.id')
                ->where($aliasD . 'id', $id)
                ->find();
        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-getData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
    public static function getDataHaveUser($ids)
    {
        try {

            $alias = 'cr';
            $aliasD = $alias . '.';
            return ConsumeRecharge::field([
                $aliasD . 'id as id',
                $aliasD . 'sn',
                $aliasD . 'status',
                $aliasD . 'type',
                $aliasD . 'pay_price',
                $aliasD . 'user_id',
                $aliasD . 'recharge_price',
                $aliasD . 'create_time',
                'u.p_first_user_id as first_user_id',
                'u.p_second_user_id as second_user_id',
                'u.p_three_user_id as three_user_id',
            ])
                ->alias($alias)
                ->leftJoin('user u', $aliasD . 'user_id = u.id')
                ->whereIn($aliasD . 'id', $ids)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-getDataHaveUser Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为充值中
     *
     * @param $id
     * @param $adminId
     * @return bool
     */
    public static function setRecharging($id, $adminId): bool
    {
        try {
            $data = [
                'status' => 2,
                'admin_id' => $adminId,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $id)->update($data);

            return !empty($res);

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-setRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为批量充值中
     *
     * @param $ids
     * @param $adminId
     * @return bool
     */
    public static function setBatchRecharging($ids, $adminId): bool
    {
        try {
            $data = [
                'status' => 2,
                'admin_id' => $adminId,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::whereIn('id', $ids)->update($data);

            return !empty($res);

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-setBatchRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为成功
     *
     * @param $info
     * @param $ratioData
     * @param $adminId
     * @return bool
     */
    public static function setSuccess($info, $ratioData, $adminId): bool
    {
        try {
            Db::startTrans();

            // 流水描述
            $desc = '';
            switch ($info['type']) {
                case 1:
                    $desc = '话费';
                    break;

                case 2:
                    $desc = '电费';
                    break;

                case 3:
                    $desc = '话费快充';
                    break;

                case 4:
                    $desc = '礼品卡';
                    break;
            }

            $billDesc = $desc . '充值';

            // 返佣第一人
            if (!empty($info['first_user_id']) && !empty($ratioData['first_ratio'])) {

                $tmpUserId = $info['first_user_id'];

                // 查看是否已返佣过
                $res = UserMoneyLog::where('user_id', $tmpUserId)->where('source_sn', $info['sn'])->find();
                if (empty($res['id'])) {

                    if ($ratioData['first_ratio'] > 100 || $ratioData['first_ratio'] < 0) {
                        self::setError('更新失败，第一返佣比例有误');
                        Db::rollback();
                        return false;
                    }

                    $ratio = bcdiv($ratioData['first_ratio'], 100, 4);
                    $givePrice = bcmul($info['pay_price'], $ratio, 4);
                    $givePrice = number_format($givePrice, 2);

                    // 更改返还用户余额
                    $res = User::where('id', $tmpUserId)
                        ->inc('user_money', $givePrice)
                        ->inc('total_award_price', $givePrice)
                        ->update([
                            'update_time' => time()
                        ]);

                    if (!$res) {
                        self::setError('增加返佣第一人账户余额失败，ID：' . $tmpUserId);
                        Db::rollback();
                        return false;
                    }

                    // 获取用户余额
                    $userInfo = User::where('id', $tmpUserId)->find();
                    if (empty($userInfo['id']) || $userInfo['user_money'] < 0) {
                        self::setError('返佣第一人账户异常');
                        Db::rollback();
                        return false;
                    }

                    $billData = [
                        'user_id' => $tmpUserId,
                        'n_user_id' => $info['user_id'],
                        'type' => 3,
                        'desc' => $billDesc . '返佣，返佣比例：' . $ratioData['first_ratio'] . '%',
                        'change_type' => 1,
                        'change_money' => $givePrice,
                        'changed_money' => $userInfo['user_money'] ?? 0,
                        'source_sn' => $info['sn']
                    ];

                    $res = UserMoneyLog::create($billData);
                    if (empty($res['id'])) {
                        self::setError('记录流水失败');
                        Db::rollback();
                        return false;
                    }
                }
            }

            // 返佣第二人
            if (!empty($info['second_user_id']) && !empty($ratioData['second_ratio'])) {

                $tmpUserId = $info['second_user_id'];

                // 查看是否已返佣过
                $res = UserMoneyLog::where('user_id', $tmpUserId)->where('source_sn', $info['sn'])->find();
                if (empty($res['id'])) {

                    if ($ratioData['second_ratio'] > 100 || $ratioData['second_ratio'] < 0) {
                        self::setError('更新失败，第二返佣比例有误');
                        Db::rollback();
                        return false;
                    }

                    $ratio = bcdiv($ratioData['second_ratio'], 100, 4);
                    $givePrice = bcmul($info['pay_price'], $ratio, 4);
                    $givePrice = number_format($givePrice, 2);

                    // 更改返还用户余额
                    $res = User::where('id', $tmpUserId)
                        ->inc('user_money', $givePrice)
                        ->inc('total_award_price', $givePrice)
                        ->update([
                            'update_time' => time()
                        ]);

                    if (!$res) {
                        self::setError('增加返佣第二人账户余额失败，ID：' . $tmpUserId);
                        Db::rollback();
                        return false;
                    }

                    // 获取用户余额
                    $userInfo = User::where('id', $tmpUserId)->find();
                    if (empty($userInfo['id']) || $userInfo['user_money'] < 0) {
                        self::setError('返佣第二人账户异常');
                        Db::rollback();
                        return false;
                    }

                    $billData = [
                        'user_id' => $tmpUserId,
                        'n_user_id' => $info['user_id'],
                        'type' => 3,
                        'desc' => $billDesc . '返佣，返佣比例：' . $ratioData['second_ratio'] . '%',
                        'change_type' => 1,
                        'change_money' => $givePrice,
                        'changed_money' => $userInfo['user_money'] ?? 0,
                        'source_sn' => $info['sn']
                    ];

                    $res = UserMoneyLog::create($billData);
                    if (empty($res['id'])) {
                        self::setError('记录流水失败');
                        Db::rollback();
                        return false;
                    }
                }
            }

            // 返佣第三人
            if (!empty($info['three_user_id']) && !empty($ratioData['three_ratio'])) {

                $tmpUserId = $info['three_user_id'];

                // 查看是否已返佣过
                $res = UserMoneyLog::where('user_id', $tmpUserId)->where('source_sn', $info['sn'])->find();
                if (empty($res['id'])) {

                    if ($ratioData['three_ratio'] > 100 || $ratioData['three_ratio'] < 0) {
                        self::setError('更新失败，第三返佣比例有误');
                        Db::rollback();
                        return false;
                    }

                    $ratio = bcdiv($ratioData['three_ratio'], 100, 4);
                    $givePrice = bcmul($info['pay_price'], $ratio, 4);
                    $givePrice = number_format($givePrice, 2);

                    // 更改返还用户余额
                    $res = User::where('id', $tmpUserId)
                        ->inc('user_money', $givePrice)
                        ->inc('total_award_price', $givePrice)
                        ->update([
                            'update_time' => time()
                        ]);

                    if (!$res) {
                        self::setError('增加返佣第三人账户余额失败，ID：' . $tmpUserId);
                        Db::rollback();
                        return false;
                    }

                    // 获取用户余额
                    $userInfo = User::where('id', $tmpUserId)->find();
                    if (empty($userInfo['id']) || $userInfo['user_money'] < 0) {
                        self::setError('返佣第三人账户异常');
                        Db::rollback();
                        return false;
                    }

                    $billData = [
                        'user_id' => $tmpUserId,
                        'n_user_id' => $info['user_id'],
                        'type' => 3,
                        'desc' => $billDesc . '返佣，返佣比例：' . $ratioData['three_ratio'] . '%',
                        'change_type' => 1,
                        'change_money' => $givePrice,
                        'changed_money' => $userInfo['user_money'] ?? 0,
                        'source_sn' => $info['sn']
                    ];

                    $res = UserMoneyLog::create($billData);
                    if (empty($res['id'])) {
                        self::setError('记录流水失败');
                        Db::rollback();
                        return false;
                    }
                }
            }

            $data = [
                'status' => 3,
                'admin_id' => $adminId,
                'balances_price' => $info['recharge_price'],
                'pay_time' => time(),
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $info['id'])
                ->whereIn('status', [1, 2])
                ->update($data);
            if (empty($res)) {
                self::setError('更新成功失败');
                Db::rollback();
                return false;
            }

            // 消息通知
            $noticeData = [
                'user_id' => $info['user_id'],
                'title' => '订单充值成功提醒',
                'content' => '您的' . $desc . '订单 ' . $info['sn'] . ' 已于 ' . date('Y-m-d H:i', strtotime($info['create_time'])) . ' 充值成功 ' . $info['recharge_price'],
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 1
            ];

            $res = NoticeRecord::create($noticeData);
            if (empty($res)) {
                self::setError('更新成功失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            self::setError('更新成功异常');
            Log::record('Exception: Sql-ConsumeRechargeLogic-setSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            Db::rollback();
            return false;
        }
    }

    /**
     * 设置为失败
     *
     * @param $id
     * @return bool
     */
    public static function setFail($id, $adminId): bool
    {
        try {
            Db::startTrans();

            $consumeRechargeInfo = ConsumeRecharge::where('id', $id)->find();
            if (empty($consumeRechargeInfo)) {
                self::setError('获取不到充值信息，ID：' . $id);
                Db::rollback();
                return false;
            }
            if ($consumeRechargeInfo['status'] == 4) {
                return true;
            }

            // 返回用户余额
            $res = User::where('id', $consumeRechargeInfo['user_id'])
                ->inc('user_money', $consumeRechargeInfo['pay_price'])
                ->update([
                    'update_time' => time()
                ]);
            if (!$res) {
                self::setError('取消失败，返还用户支付金额失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $consumeRechargeInfo['user_id'])->find();

            // 流水
            $billType = '';
            $billDesc = '';

            $rechargePriceStr = (string)$consumeRechargeInfo['recharge_price']; // 转换为字符串
            $rechargePriceStr = preg_replace('/\.?0*$/', '$1', $rechargePriceStr); // 使用正则表达式移除尾部的0和.

            switch ($consumeRechargeInfo['type']) {
                case 1:
                    $billType = 1;
                    $billDesc = $consumeRechargeInfo['account'] . '充值' . $rechargePriceStr . '话费失败';
                    break;

                case 2:
                    $billType = 2;
                    $billDesc = $consumeRechargeInfo['account'] . '充值' . $rechargePriceStr . '电费失败';
                    break;

                case 3:
                    $billType = 9;
                    $billDesc = $consumeRechargeInfo['account'] . '充值' . $rechargePriceStr . '话费快充失败';
                    break;

                case 4:
                    $billType = 10;
                    $billDesc = '礼品卡购买' . $rechargePriceStr . '失败';
                    break;
            }

            $billData = [
                'user_id' => $consumeRechargeInfo['user_id'],
                'type' => $billType,
                'desc' => $billDesc,
                'change_type' => 1,
                'change_money' => $consumeRechargeInfo['pay_price'],
                'changed_money' => $userInfo['user_money'],
                'source_sn' => $consumeRechargeInfo['sn'],
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('取消失败，记录流水失败');
                Db::rollback();
                return false;
            }

            // 消息通知描述
            $desc = '';
            switch ($consumeRechargeInfo['type']) {
                case 1:
                    $desc = '话费';
                    break;

                case 2:
                    $desc = '电费';
                    break;

                case 3:
                    $desc = '话费快充';
                    break;

                case 4:
                    $desc = '礼品卡';
                    break;
            }

            // 消息通知
            $noticeData = [
                'user_id' => $consumeRechargeInfo['user_id'],
                'title' => '订单充值失败提醒',
                'content' => '您的' . $desc . '订单 ' . $consumeRechargeInfo['sn'] . ' 已于 ' . date('Y-m-d H:i') . ' 充值失败 ' . $consumeRechargeInfo['recharge_price'],
                'scene_id' => 0,
                'read' => 0,
                'recipient' => 1,
                'send_type' => 1,
                'notice_type' => 1,
                'type' => 1
            ];

            $res = NoticeRecord::create($noticeData);
            if (!$res) {
                self::setError('更新成功失败');
                Db::rollback();
                return false;
            }

            // 消费充值表
            $userAccountData = [
                'status' => 4,
                'admin_id' => $adminId,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $id)->update($userAccountData);
            if (!$res) {
                self::setError('更改充值表失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-setFail Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('更改数据失败');
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
    public
    static function setBalance($id, $price): bool
    {
        try {
            Db::startTrans();

            $consumeRechargeInfo = ConsumeRecharge::where('id', $id)->find();
            if (empty($consumeRechargeInfo)) {
                throw new Exception('获取不到充值信息ID：' . $id);
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

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-setBalance Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            Db::rollback();
            return false;
        }
    }
}
