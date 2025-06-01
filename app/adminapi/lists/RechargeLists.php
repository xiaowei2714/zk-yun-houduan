<?php

namespace app\adminapi\lists;

use app\common\model\Recharge;
use app\common\lists\ListsSearchInterface;

/**
 * Recharge列表
 * Class RechargeLists
 * @package app\adminapi\lists
 */
class RechargeLists extends BaseAdminDataLists implements ListsSearchInterface
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
        $alias = 'r';
        $aliasD = $alias . '.';
        $list = Recharge::field([
            $aliasD . 'id',
            $aliasD . 'user_id',
            $aliasD . 'money',
            $aliasD . 'pay_money',
            $aliasD . 'desc',
            $aliasD . 'order_no',
            $aliasD . 'pay_time',
            $aliasD . 'hash',
            $aliasD . 'status',
            $aliasD . 'create_time',
            'a.name as admin_name',
            'u.nickname'
        ])
            ->alias($alias)
            ->leftJoin('user u', $aliasD . 'user_id = u.id')
            ->leftJoin('admin a', $aliasD . 'set_success_admin_id = a.id')
            ->where($this->handleWhereData($this->params, $aliasD))
            ->limit($this->limitOffset, $this->limitLength)
            ->order([$aliasD . 'id' => 'desc'])
            ->select()
            ->toArray();

        $newData = [];
        foreach ($list as $value) {
            $newData[] = [
                'id' => $value['id'],
                'user_id' => $value['user_id'],
                'nickname' =>$value['nickname'],
                'money' => $value['pay_money'],
                'desc' => $value['desc'],
                'order_no' => $value['order_no'],
                'pay_time' => !empty($value['pay_time']) ? date('Y-m-d H:i:s', $value['pay_time']) : '',
                'set_success_admin' => $value['admin_name'],
                'hash' => $value['hash'],
                'status' => $value['status'],
                'create_time' => $value['create_time'],
            ];
        }

        return $newData;
    }

    /**
     * @notes 获取数量
     * @return int
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public function count(): int
    {
        return Recharge::where($this->handleWhereData($this->params))->count();
    }

    /**
     * @param $params
     * @param $pre
     * @return array
     */
    private function handleWhereData($params, $pre = '')
    {
        $newData = [];
        if (isset($params['order_no']) && $params['order_no'] !== '' && $params['order_no'] !== null) {
            $newData[] = [
                $pre . 'order_no', 'like', '%' . $params['order_no'] . '%'
            ];
        }

        return $newData;
    }

}
