<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\user\User;
use app\common\model\UserAd;
use app\common\model\UserMoneyLog;
use app\common\model\UserPayType;
use think\facade\Db;
use think\facade\Log;
use Exception;
use think\Model;

/**
 * 支付类型
 * 配置 *
 * Class RechargeLogic
 * @package app\shopapi\logic
 */
class UserPayTypeLogic extends BaseLogic
{
    /**
     * 详情
     *
     * @param $userId
     * @param $type
     * @return UserPayType|array|false|Model|null
     */
    public static function infoByType($userId, $type): bool|array|UserPayType|Model|null
    {
        try {
            return UserPayType::where('user_id', '=', $userId)
                ->where('type', '=', $type)
                ->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserPayTypeLogic-infoByType Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            self::setError('获取详情异常');
            return false;
        }
    }
}
