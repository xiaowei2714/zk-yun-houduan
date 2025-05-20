<?php

namespace app\adminapi\logic;

use app\common\model\ConsumeRecharge;
use app\common\logic\BaseLogic;
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

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-setBatchRecharging Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 设置为成功
     *
     * @param $id
     * @param $userData
     * @param $ratioData
     * @return bool
     */
    public static function setSuccess($info, $userData, $ratioData): bool
    {
        try {
            Db::startTrans();

            // 流水描述
            $billDesc = '';
            switch ($info['type']) {
                case 1:
                    $billDesc = '话费充值';
                    break;

                case 2:
                    $billDesc = '电费充值';
                    break;

                case 3:
                    $billDesc = '话费快充充值';
                    break;

                case 4:
                    $billDesc = '礼品卡充值';
                    break;
            }

            // 返佣第一人
            if (!empty($userData['first_user_id']) && !empty($ratioData['first_ratio'])) {

                $tmpUserId = $userData['first_user_id'];

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
                    $res = User::where('id', $tmpUserId)->inc('user_money', $givePrice)->update([
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
            if (!empty($userData['second_user_id']) && !empty($ratioData['second_ratio'])) {

                $tmpUserId = $userData['second_user_id'];

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
                    $res = User::where('id', $tmpUserId)->inc('user_money', $givePrice)->update([
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
            if (!empty($userData['three_user_id']) && !empty($ratioData['three_ratio'])) {

                $tmpUserId = $userData['three_user_id'];

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
                    $res = User::where('id', $tmpUserId)->inc('user_money', $givePrice)->update([
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
                'balances_price' => $info['recharge_price'],
                'pay_time' => time(),
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $info['id'])->update($data);
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
    public
    static function setBatchSuccess($ids): bool
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

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeRechargeLogic-setBatchSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
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
    public
    static function setFail($id): bool
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

            // 获取流水
            $billInfo = UserMoneyLog::where('source_sn', $consumeRechargeInfo['sn'])->find();
            if (empty($billInfo['id'])) {
                self::setError('获取流水失败');
                Db::rollback();
                return false;
            }

            // 删除流水
            $res = UserMoneyLog::destroy($billInfo['id']);
            if (!$res) {
                self::setError('回撤流水失败');
                Db::rollback();
                return false;
            }

            // 返回用户余额
            $res = User::where('id', $consumeRechargeInfo['user_id'])->inc('user_money', $consumeRechargeInfo['pay_price'])->update([
                'update_time' => time()
            ]);
            if (!$res) {
                self::setError('返还扣除的用户余额失败');
                Db::rollback();
                return false;
            }

            // 消费充值表
            $userAccountData = [
                'status' => 4,
                'update_time' => time()
            ];

            $res = ConsumeRecharge::where('id', $id)->update($userAccountData);
            if (empty($res)) {
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
