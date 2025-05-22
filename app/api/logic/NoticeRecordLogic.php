<?php

namespace app\api\logic;

use app\common\enum\user\AccountLogEnum;
use app\common\logic\BaseLogic;
use app\common\model\notice\NoticeRecord;
use app\common\model\user\User;
use app\common\model\user\UserAccountLog;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Log;
use Exception;

/**
 * 通知记录逻辑层
 *
 * Class NoticeRecordLogic
 * @package app\shopapi\logic
 */
class NoticeRecordLogic extends BaseLogic
{
    /**
     * 列表
     *
     * @return array|false
     */
    public static function listByUser($userId, $type)
    {
        try {
            return NoticeRecord::field('id,title,content,create_time')
                ->where('user_id', $userId)
                ->where('type', $type)
                ->order('id desc')
                ->limit(100)
                ->select()
                ->toArray();

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserAccountLogLogic-listByUser Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 列表
     *
     * @return array|false
     */
    public static function info($id)
    {
        try {
            return NoticeRecord::field('id,user_id,title,content,create_time')
                ->where('id', $id)
                ->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserAccountLogLogic-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }

    /**
     * 列表
     *
     * @return array|false
     */
    public static function newestInfo($userId, $type)
    {
        try {
            return NoticeRecord::field('title,create_time')
                ->where('user_id', $userId)
                ->where('type', $type)
                ->order('id desc')
                ->find();

        } catch (Exception $e) {
            Log::record('Exception: Sql-UserAccountLogLogic-newestInfo Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return false;
        }
    }
}
