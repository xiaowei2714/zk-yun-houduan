<?php

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\model\Notice;
use think\facade\Log;
use Exception;

/**
 * 消息逻辑层
 *
 * Class NoticeRecordLogic
 * @package app\shopapi\logic
 */
class NoticeLogic extends BaseLogic
{
    /**
     * 列表
     *
     * @return array|false
     */
    public static function listByType($type)
    {
        try {
            return Notice::field('id,title,content')
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
}
