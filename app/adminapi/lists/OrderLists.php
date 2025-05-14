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
use app\common\model\Order;
use app\common\lists\ListsSearchInterface;


/**
 * Order列表
 * Class OrderLists
 * @package app\adminapi\lists
 */
class OrderLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author Jarshs
     * @date 2025/04/05 14:01
     */
    public function setSearch(): array
    {
        return [
            '=' => ['account_type', 'status', 'start_time', 'end_time'],
            '%like%' => ['sn', 'account'],
        ];
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Jarshs
     * @date 2025/04/05 14:01
     */
    public function lists(): array
    {
        return Order::where($this->searchWhere)
            ->field(['id', 'sn', 'user_id', 'account', 'account_type', 'price', 'recharge_up_price', 'recharge_down_price', 'balances_price', 'status', 'start_time', 'end_time', 'pay_time'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();
    }


    /**
     * @notes 获取数量
     * @return int
     * @author Jarshs
     * @date 2025/04/05 14:01
     */
    public function count(): int
    {
        return Order::where($this->searchWhere)->count();
    }

}