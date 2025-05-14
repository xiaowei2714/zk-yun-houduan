<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\adminapi\logic;


use app\common\model\Withdraw;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * Withdraw逻辑
 * Class WithdrawLogic
 * @package app\adminapi\logic
 */
class WithdrawLogic extends BaseLogic
{


    /**
     * @notes 添加
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            Withdraw::create([
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'address' => $params['address'],
                'order_no' => $params['order_no'],
                'pay_time' => $params['pay_time'],
                'status' => $params['status'],
                'fail_msg' => $params['fail_msg']
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 编辑
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            Withdraw::where('id', $params['id'])->update([
                'user_id' => $params['user_id'],
                'money' => $params['money'],
                'address' => $params['address'],
                'order_no' => $params['order_no'],
                'pay_time' => $params['pay_time'],
                'status' => $params['status'],
                'fail_msg' => $params['fail_msg']
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 删除
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public static function delete(array $params): bool
    {
        return Withdraw::destroy($params['id']);
    }


    /**
     * @notes 获取详情
     * @param $params
     * @return array
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public static function detail($params): array
    {
        return Withdraw::findOrEmpty($params['id'])->toArray();
    }
}