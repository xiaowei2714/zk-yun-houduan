<?php

namespace app\adminapi\lists;

use app\common\model\Recharge;
use app\common\lists\ListsSearchInterface;

/**
 * Recharge列表
 * Class RechargeLists
 * @package app\adminapi\lists
 */
class RechargeOrderLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['order_no'],
        ];
    }

    /**
     * 获取列表
     *
     * @return array
     */
    public function lists(): array
    {
        return Recharge::where($this->searchWhere)
            ->field(['id', 'user_id', 'money', 'desc', 'order_no', 'create_time', 'pay_time', 'hash', 'status'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();
    }

    /**
     * 获取数量
     *
     * @return int
     */
    public function count(): int
    {
        return Recharge::where($this->searchWhere)->count();
    }
}
