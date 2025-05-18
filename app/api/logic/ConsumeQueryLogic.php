<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\ConsumeQuery;
use app\common\model\ConsumeRecharge;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use think\facade\Db;
use think\facade\Log;
use Exception;
use think\Model;

/**
 * 话费、电费、油费查询逻辑层
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class ConsumeQueryLogic extends BaseLogic
{
    /**
     * 列表
     *
     * @param $userId
     * @param $account
     * @param $type
     * @return array|false
     */
    public static function list($userId, $account, $type): bool|array
    {
        try {
            return ConsumeQuery::field([
                'user_id',
                'account',
                'account_type',
                'area',
                'balance',
                'pay_price',
                'create_time'
            ])
                ->where('user_id', '=', $userId)
                ->where('account', '=', $account)
                ->where('type', '=', $type)
                ->order('id desc')
                ->limit(15)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeQueryLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 详情
     *
     * @param $id
     * @return ConsumeQuery|array|false|Model|null
     */
    public static function info($id)
    {
        try {
            return ConsumeQuery::field([
                'user_id',
                'account',
                'account_type',
                'area',
                'balance',
                'pay_price',
                'create_time'
            ])
                ->where('id', '=', $id)
                ->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeQueryLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 列表
     *
     * @param $userId
     * @param $type
     * @return array|false
     */
    public static function listByUser($userId, $type): bool|array
    {
        try {
            return ConsumeQuery::field([
                'account',
                'account_type',
                'area',
                'balance'
            ])
                ->where('user_id', '=', $userId)
                ->where('type', '=', $type)
                ->order('id desc')
                ->limit(100)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeQueryLogic-listByUser Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 列表
     *
     * @param $userId
     * @param $type
     * @param $account
     * @return array|false
     */
    public static function listByAccount($userId, $type, $account): bool|array
    {
        try {
            return ConsumeQuery::field([
                'id',
                'balance',
                'create_time'
            ])
                ->where('user_id', '=', $userId)
                ->where('account', '=', $account)
                ->where('type', '=', $type)
                ->order('id desc')
                ->limit(100)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeQueryLogic-listByAccount Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 新增数据
     *
     * @param array $params
     * @return bool
     */
    public static function addData(array $params): bool
    {
        try {
            Db::startTrans();

            // 扣除用户余额
            $res = User::where('id', $params['user_id'])->dec('user_money', $params['pay_price'])->update([
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

//            // 流水
//            $userAccountData = [
//                'sn' => generate_sn(ConsumeRecharge::class, 'sn'),
//                'user_id' => $params['user_id'],
//                'change_object' => 1,
//                'change_type' => 0,
//                'action' => 2,
//                'change_amount' => $params['meal_discounted_price'],
//                'left_amount' => $userInfo['user_money'],
//                'source_sn' => $sn,
//                'remark' => $params['type'] == 1 ? '话费充值扣款' : '电费充值扣款',
//                'extra' => 'consume_recharge'
//            ];
//
//            $res = UserAccountLog::create($userAccountData);
//            if (empty($res['id'])) {
//                self::setError('记录流水失败');
//                Db::rollback();
//                return false;
//            }

            // 消费查询表
            $consumeParams = [
                'user_id' => $params['user_id'],
                'account' => $params['account'],
                'balance' => $params['balance'],
                'pay_price' => $params['pay_price'],
                'type' => $params['type']
            ];

            if (isset($params['account_type'])) {
                $consumeParams['account_type'] = $params['account_type'];
            }
            if (isset($params['area'])) {
                $consumeParams['area'] = $params['area'];
            }

            $order = ConsumeQuery::create($consumeParams);
            if (empty($order['id'])) {
                throw new Exception('新增`消费查询数据`失败');
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-ConsumeQueryLogic-addData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('查询失败');
            Db::rollback();
            return false;
        }
    }
}
