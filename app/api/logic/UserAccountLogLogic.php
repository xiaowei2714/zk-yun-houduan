<?php

namespace app\api\logic;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use Exception;

/**
 * 用户金额记录逻辑层
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class UserAccountLogLogic extends BaseLogic
{
    /**
     * 列表
     *
     * @return array|false
     */
    public static function transferlist($userId)
    {
        try {

            $alias = 'ual';
            $aliasD = $alias . '.';

            return UserAccountLog::field([
                'u.sn as u_sn',
                $aliasD . 'change_amount',
                $aliasD . 'create_time'
            ])->alias($alias)
                ->leftJoin('user u', $aliasD . 'source_user_id = u.id')
                ->where($aliasD . 'user_id', $userId)
                ->where($aliasD . 'change_type', AccountLogEnum::UM_DEC_TRANSFER)
                ->whereNotNull($aliasD . 'source_user_id')
                ->order($aliasD . 'id desc')
                ->limit(100)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserAccountLogLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 新增转账数据
     *
     * @param $params
     * @return bool
     */
    public static function addTransferData($params): bool
    {
        try {
            Db::startTrans();

            // 扣除用户余额
            $res = User::where('id', $params['user_id'])
                ->dec('user_money', $params['money'])
                ->update([
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

            $sn = generate_sn(UserAccountLog::class, 'sn');
            $outDesc = '向账户ID：' . $params['change_user_sn'] . '转账';
            $changedMoney = $userInfo['user_money'];
            $userSn = $userInfo['sn'];

            // 流水
            $billData = [
                'user_id' => $params['user_id'],
                'type' => 11,
                'desc' => $outDesc,
                'change_type' => 2,
                'change_money' => $params['money'],
                'changed_money' => $changedMoney,
                'source_sn' => $sn
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('记录扣除流水失败');
                Db::rollback();
                return false;
            }

            // 增加用户余额
            $res = User::where('id', $params['change_user_id'])
                ->inc('user_money', $params['money'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('增加账户余额失败');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $params['change_user_id'])->find();

            // 流水
            $billData = [
                'user_id' => $params['change_user_id'],
                'type' => 12,
                'desc' => '账户ID：' . $userSn . '转入',
                'change_type' => 1,
                'change_money' => $params['money'],
                'changed_money' => $userInfo['user_money'],
                'source_sn' => $sn
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('记录增加流水失败');
                Db::rollback();
                return false;
            }

            // 转账记录
            $changeType = AccountLogEnum::UM_DEC_TRANSFER;
            $changeObject = AccountLogEnum::getChangeObject($changeType);
            if (!$changeObject) {
                self::setError('未配置的改变类型');
                Db::rollback();
                return false;
            }

            $accountParams = [
                'sn' => $sn,
                'user_id' => $params['user_id'],
                'source_user_id' => $params['change_user_id'],
                'change_object' => $changeObject,
                'change_type' => $changeType,
                'action' => AccountLogEnum::DEC,
                'change_amount' => $params['money'],
                'left_amount' => $changedMoney,
                'remark' => $outDesc
            ];

            $res = UserAccountLog::create($accountParams);
            if (empty($res['id'])) {
                self::setError('转账失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserMoneyLogLogic-List Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('转账异常');
            Db::rollback();
            return false;
        }
    }
}
