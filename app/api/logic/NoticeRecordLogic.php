<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\notice\NoticeRecord;
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
    public static function listByUser(array $params)
    {
        try {
            $obj = NoticeRecord::field('id,title,content,create_time')
                ->where('user_id', $params['user_id'])
                ->where('type', $params['type']);

            if (!empty($params['last_id'])) {
                $obj = $obj->where('id', '<', $params['last_id']);
            }

            return $obj->order('id desc')
                ->limit($params['limit'])
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
