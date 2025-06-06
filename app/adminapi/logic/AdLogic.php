<?php

namespace app\adminapi\logic;


use app\api\logic\UserLogic;
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
 * Order
 */
class AdLogic extends BaseLogic
{
    /**
     * 上架
     *
     * @param $id
     * @return bool
     */
    public static function onAd($id)
    {
        try {
            $res = UserAd::where('id', $id)
                ->update([
                    'status' => 2,
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('上架失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-onAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('上架异常');
            return false;
        }
    }

    /**
     * 批量上架
     *
     * @param $ids
     * @return bool
     */
    public static function batchOnAd($ids)
    {
        try {
            $res = UserAd::whereIn('id', $ids)
                ->update([
                    'status' => 2,
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('上架失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-onAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('上架异常');
            return false;
        }
    }

    /**
     * 下架
     *
     * @param $id
     * @return bool
     */
    public static function offAd($id)
    {
        try {
            $res = UserAd::where('id', $id)
                ->update([
                    'status' => 1,
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('下架失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-offAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('下架异常');
            return false;
        }
    }

    /**
     * 批量下架
     *
     * @param $ids
     * @return bool
     */
    public static function batchOffAd($ids)
    {
        try {
            $res = UserAd::whereIn('id', $ids)
                ->update([
                    'status' => 1,
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('下架失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-batchOffAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('下架异常');
            return false;
        }
    }

    /**
     * 删除数据
     *
     * @param $id
     * @return bool
     */
    public static function deleteData($id): bool
    {
        try {
            Db::startTrans();

            $info = UserAd::where('id', $id)->find();
            if (empty($info['id'])) {
                self::setError('广告不存在');
                Db::rollback();
                return false;
            }

            $userInfo = UserLogic::info($info['user_id']);
            if (empty($userInfo['id'])) {
                self::setError('用户不存在');
                Db::rollback();
                return false;
            }
            if (bccomp($userInfo['freeze_money'], $info['left_num'], 3) < 0) {
                self::setError('返还的冻结金额不足');
                Db::rollback();
                return false;
            }

            // 返回冻结金额
            $res = User::where('id', $info['user_id'])
                ->dec('freeze_money', $info['left_num'])
                ->inc('user_money', $info['left_num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('回撤数量大于可出售数量');
                Db::rollback();
                return false;
            }

            // 获取用户余额
            $userInfo = User::where('id', $info['user_id'])->find();
            if ($userInfo['freeze_money'] < 0) {
                self::setError('账户冻结金额不足');
                Db::rollback();
                return false;
            }

            // 流水
            $billData = [
                'user_id' => $info['user_id'],
                'type' => 6,
                'desc' => '交易广告删除退回余额',
                'change_type' => 1,
                'change_money' => $info['left_num'],
                'changed_money' => $userInfo['user_money']
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('记录流水失败');
                Db::rollback();
                return false;
            }

            $res = UserAd::destroy($info['id']);
            if (!$res) {
                self::setError('删除失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-deleteData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('删除异常');
            Db::rollback();
            return false;
        }
    }
}
