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


use app\common\model\SetMeal;
use app\common\logic\BaseLogic;
use think\facade\Db;


/**
 * SetMeal逻辑
 * Class SetMealLogic
 * @package app\adminapi\logic
 */
class SetMealLogic extends BaseLogic
{


    /**
     * @notes 添加
     * @param array $params
     * @return bool
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public static function add(array $params): bool
    {
        Db::startTrans();
        try {
            SetMeal::create([
                'name' => $params['name'],
                'type' => $params['type'],
                'show_type' => $params['show_type'],
                'price' => $params['price'],
                'discount' => $params['discount'],
                'operator' => json_encode($params['operator']),
                'forbid_buy_city' => $params['forbid_buy_city'],
                'sort' => $params['sort'],
                'day_astrict_num' => $params['day_astrict_num'],
                'meanwhile_order_num' => $params['meanwhile_order_num'],
                'user_required_info' => json_encode($params['user_required_info']),
                'merchant_required_info' => json_encode($params['merchant_required_info']),
                'allow_buy_nums' => $params['allow_buy_nums'],
                'forbid_buy_nums' => $params['forbid_buy_nums'],
                'status' => $params['status'],
                'desc' => $params['desc'],
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
     * @date 2025/04/02 14:35
     */
    public static function edit(array $params): bool
    {
        Db::startTrans();
        try {
            SetMeal::where('id', $params['id'])->update([
                'name' => $params['name'],
                'type' => $params['type'],
                'show_type' => $params['show_type'],
                'price' => $params['price'],
                'discount' => $params['discount'],
                'operator' => json_encode($params['operator']),
                'forbid_buy_city' => $params['forbid_buy_city'],
                'sort' => $params['sort'],
                'day_astrict_num' => $params['day_astrict_num'],
                'meanwhile_order_num' => $params['meanwhile_order_num'],
                'user_required_info' => json_encode($params['user_required_info']),
                'merchant_required_info' => json_encode($params['merchant_required_info']),
                'allow_buy_nums' => $params['allow_buy_nums'],
                'forbid_buy_nums' => $params['forbid_buy_nums'],
                'status' => $params['status'],
                'desc' => $params['desc'],
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
     * @date 2025/04/02 14:35
     */
    public static function delete(array $params): bool
    {
        return SetMeal::destroy($params['id']);
    }


    /**
     * @notes 获取详情
     * @param $params
     * @return array
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public static function detail($params): array
    {
        return SetMeal::findOrEmpty($params['id'])->toArray();
    }
}