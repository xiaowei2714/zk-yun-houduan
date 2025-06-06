<?php

namespace app\adminapi\lists\user;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\UserMoneyLog;

class UserMoneyLists extends BaseAdminDataLists implements ListsSearchInterface
{

    /**
     * 搜索条件
     *
     * @return \string[][]
     */
    public function setSearch(): array
    {
        return [
            '=' => ['user_id', 'type', 'change_type']
        ];
    }

    /**
     * 获取用户账单
     *
     * @return array
     */
    public function lists(): array
    {
        $data = UserMoneyLog::where($this->searchWhere)
            ->field([
                'id',
                'type',
                'desc',
                'change_type',
                'change_money',
                'changed_money',
                'create_time',
            ])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();

        $conf = $this->typeConf();
        foreach ($data as &$value) {
            $value['type'] = $conf[$value['type']] ?? '';
        }

        return $data;
    }

    /**
     * 获取数量
     *
     * @return int
     */
    public function count(): int
    {
        return UserMoneyLog::where($this->searchWhere)->count();
    }

    private function typeConf()
    {
        return [
            1 => '话费',
            2 => '电费',
            3 => '返佣',
            4 => '查询',
            5 => '充值',
            6 => '广告发布',
            7 => '开通分站',
            8 => '提现',
            9 => '话费快充',
            10 => '礼品卡',
            11 => '转出',
            12 => '转入',
            13 => '广告交易',
        ];
    }
}
