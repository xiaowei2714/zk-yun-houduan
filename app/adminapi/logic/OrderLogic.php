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


use app\common\model\Order;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * Order逻辑
 * Class OrderLogic
 * @package app\adminapi\logic
 */
class OrderLogic extends BaseLogic
{


    /**
     * @notes 添加
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/04/05 14:01
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            Order::create([
                'sn' => $params['sn'],
                'user_id' => $params['user_id'],
                'account' => $params['account'],
                'account_type' => $params['account_type'],
                'price' => $params['price'],
                'recharge_up_price' => $params['recharge_up_price'],
                'recharge_down_price' => $params['recharge_down_price'],
                'balances_price' => $params['balances_price'],
                'status' => $params['status'],
                'start_time' => strtotime($params['start_time']),
                'end_time' => strtotime($params['end_time']),
                'pay_time' => $params['pay_time']
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
     * @date 2025/04/05 14:01
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            Order::where('id', $params['id'])->update([
                'sn' => $params['sn'],
                'user_id' => $params['user_id'],
                'account' => $params['account'],
                'account_type' => $params['account_type'],
                'price' => $params['price'],
                'recharge_up_price' => $params['recharge_up_price'],
                'recharge_down_price' => $params['recharge_down_price'],
                'balances_price' => $params['balances_price'],
                'status' => $params['status'],
                'start_time' => strtotime($params['start_time']),
                'end_time' => strtotime($params['end_time']),
                'pay_time' => $params['pay_time']
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
     * @date 2025/04/05 14:01
     */
    public static function delete(array $params): bool
    {
        return Order::destroy($params['id']);
    }


    /**
     * @notes 获取详情
     * @param $params
     * @return array
     * @author Jarshs
     * @date 2025/04/05 14:01
     */
    public static function detail($params): array
    {
        return Order::findOrEmpty($params['id'])->toArray();
    }
}