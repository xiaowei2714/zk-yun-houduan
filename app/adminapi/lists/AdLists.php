<?php

namespace app\adminapi\lists;

use app\common\lists\ListsSearchInterface;
use app\common\model\UserAd;


/**
 * Ad列表
 * Class AdOrderLists
 * @package app\adminapi\lists
 */
class AdLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
        ];
    }

    /**
     * @return array
     */
    public function lists(): array
    {
        $alias = 'ad';
        $aliasD = $alias . '.';
        return UserAd::where($this->searchWhere)
            ->field([
                $aliasD . 'id',
                $aliasD . 'user_id',
                $aliasD . 'num',
                $aliasD . 'left_num',
                $aliasD . 'price',
                $aliasD . 'min_price',
                $aliasD . 'max_price',
                $aliasD . 'pay_time',
                $aliasD . 'pay_type',
                $aliasD . 'tips',
                $aliasD . 'status',
                $aliasD . 'create_time',
                'u.nickname as username',
                'u.sn as user_sn',
            ])->alias($alias)
            ->leftJoin('user u', $aliasD . 'user_id = u.id')
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取数量
     * @return int
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function count(): int
    {
        return UserAd::where($this->searchWhere)->count();
    }
}
