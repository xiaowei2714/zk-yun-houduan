<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\model\UserAd;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use Exception;
use think\Model;

/**
 * 交易大厅
 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class AdLogic extends BaseLogic
{
    /**
     * 列表
     *
     * @param array $params
     * @return bool|array
     */
    public static function list(array $params): bool|array
    {
        try {
            $alias = 'ua';
            $aliasD = $alias . '.';
            $obj = UserAd::field([
                $aliasD . 'id',
                $aliasD . 'user_id',
                $aliasD . 'num',
                $aliasD . 'left_num',
                $aliasD . 'price',
                $aliasD . 'min_price',
                $aliasD . 'max_price',
                $aliasD . 'pay_time',
                $aliasD . 'pay_type',
                $aliasD . 'type',
                'u.nickname',
            ])->alias($alias)
                ->leftJoin('user u', $aliasD . 'user_id = u.id')
                ->where($aliasD . 'type', '=', $params['type'])
                ->where($aliasD . 'status', '=', 2);

            if (!empty($params['last_id'])) {
                $obj = $obj->where($aliasD . 'id', '<', $params['last_id']);
            }

            return $obj->order($aliasD . 'id desc')
                ->limit($params['limit'])
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取列表异常');
            return false;
        }
    }

    /**
     * @param array $params
     * @return mixed
     */
    public static function listByUser(array $params)
    {
        try {
            $tablePre = env('database.prefix');
            $adOrderTable = $tablePre . 'ad_order';
            $adTable = $tablePre . 'user_ad';

            $alias = 'ua';
            $aliasD = $alias . '.';

            $userIdParams = $params['user_id'];
            $limitParams = $params['limit'];

            $where = '';
            if (!empty($params['last_id'])) {
                $where = "AND $aliasD`id` <= " . $params['last_id'];
            }

            $sql = <<< EOT
SELECT
	$aliasD`id`,
	$aliasD`num`,
	$aliasD`left_num`,
	$aliasD`price`,
	$aliasD`min_price`,
	$aliasD`max_price`,
	$aliasD`pay_time`,
	$aliasD`pay_type`,
	$aliasD`status`,
	$aliasD`type`,
	(
	    SELECT count(*) FROM $adOrderTable WHERE ad_id = $aliasD`id` and status = 4
	) AS s_cou,
    (
	    SELECT count(*) FROM $adOrderTable WHERE ad_id = $aliasD`id`
	) AS cou 
    FROM
	    `$adTable` AS $alias
    WHERE
        $aliasD`user_id` = $userIdParams
        AND $aliasD`delete_time` is null
        $where
    ORDER BY
        $aliasD`id` DESC 
        LIMIT $limitParams
EOT;

            return Db::query($sql);

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-listByUser Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取列表异常');
            return false;
        }
    }

    /**
     * 详情
     *
     * @param $id
     * @return UserAd|array|false|Model|null
     */
    public static function infoHaveUser($id): UserAd|bool|array|Model|null
    {
        try {
            $alias = 'ua';
            $aliasD = $alias . '.';
            return UserAd::field([
                $aliasD . 'id',
                $aliasD . 'num',
                $aliasD . 'left_num',
                $aliasD . 'price',
                $aliasD . 'min_price',
                $aliasD . 'max_price',
                $aliasD . 'pay_time',
                $aliasD . 'pay_type',
                $aliasD . 'type',
                'u.nickname',
            ])
                ->alias($alias)
                ->leftJoin('user u', $aliasD . 'user_id = u.id')
                ->where($aliasD . 'id', '=', $id)
                ->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取列表异常');
            return false;
        }
    }

    /**
     * 详情
     *
     * @param $id
     * @return UserAd|array|false|Model|null
     */
    public static function info($id): UserAd|bool|array|Model|null
    {
        try {
            return UserAd::field([
                'id',
                'user_id',
                'num',
                'left_num',
                'price',
                'min_price',
                'max_price',
                'pay_time',
                'pay_type',
                'status',
                'type',
            ])
                ->where('id', '=', $id)
                ->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-list Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取详情异常');
            return false;
        }
    }

    /**
     * 增加数据
     *
     * @param array $params
     * @return bool
     */
    public static function addData(array $params): bool
    {
        try {
            Db::startTrans();

            // 扣除用户余额
            $res = User::where('id', $params['user_id'])
                ->dec('user_money', $params['num'])
                ->inc('freeze_money', $params['num'])
                ->update([
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('出售数量大于可出售数量');
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

            // 流水
            $billData = [
                'user_id' => $params['user_id'],
                'type' => 6,
                'desc' => '发布出售Y币交易冻结',
                'change_type' => 2,
                'change_money' => $params['num'],
                'changed_money' => $userInfo['user_money']
            ];

            $res = UserMoneyLog::create($billData);
            if (empty($res['id'])) {
                self::setError('记录流水失败');
                Db::rollback();
                return false;
            }

            // 广告发布
            $userAdParams = [
                'user_id' => $params['user_id'],
                'num' => $params['num'],
                'left_num' => $params['num'],
                'price' => $params['price'],
                'min_price' => $params['min_price'],
                'max_price' => $params['max_price'],
                'pay_time' => $params['pay_time'],
                'pay_type' => $params['pay_type'],
                'tips' => $params['tips'],
                'status' => $params['status'],
                'type' => $params['type']
            ];

            $order = UserAd::create($userAdParams);
            if (empty($order['id'])) {
                self::setError('发布失败');
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-addData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('发布异常');
            Db::rollback();
            return false;
        }
    }

    /**
     * 改变状态
     *
     * @param $info
     * @return bool
     */
    public static function changeStatus($info): bool
    {
        try {
            $res = UserAd::where('id', $info['id'])
                ->update([
                    'status' => $info['status'] == 1 ? 2 : 1,
                    'update_time' => time()
                ]);

            if (!$res) {
                self::setError('更改失败');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Log::record('Exception: Sql-AdLogic-addData Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('更改异常');
            return false;
        }
    }

    /**
     * 删除数据
     *
     * @param $info
     * @return bool
     */
    public static function deleteData($info): bool
    {
        try {
            Db::startTrans();

            // 返回冻结金额
            $res = User::where('id', $info['user_id'])
                ->dec('freeze_money', $info['num'])
                ->inc('user_money', $info['num'])
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
                'change_money' => $info['num'],
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
