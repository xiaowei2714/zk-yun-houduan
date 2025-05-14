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

namespace app\adminapi\lists;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\SetMeal;
use app\common\lists\ListsSearchInterface;


/**
 * SetMeal列表
 * Class SetMealLists
 * @package app\adminapi\lists
 */
class SetMealLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function setSearch(): array
    {
        return [
            
        ];
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function lists(): array
    {
        $list = SetMeal::where($this->searchWhere)
            ->field(['id', 'name', 'type', 'show_type', 'price', 'discount', 'operator', 'forbid_buy_city', 'sort', 'day_astrict_num', 'meanwhile_order_num',
                'user_required_info', 'merchant_required_info', 'allow_buy_nums', 'forbid_buy_nums', 'status', 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();

        foreach ($list as &$v) {
            $v['operator'] = json_decode($v['operator'], true);
            $v['operator_txt'] = implode(',', $v['operator']);
            $v['user_required_info'] = json_decode($v['user_required_info'], true);
            $v['merchant_required_info'] = json_decode($v['merchant_required_info'], true);
        }

        return $list;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function count(): int
    {
        return SetMeal::where($this->searchWhere)->count();
    }

}