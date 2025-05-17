<?php

namespace app\adminapi\lists;

use app\common\lists\ListsExcelInterface;
use app\common\model\ConsumeRecharge;

/**
 * 话费、电费充值列表
 * Class UserLists
 * @package app\adminapi\lists\user
 */
class ConsumeRechargeLists extends BaseAdminDataLists implements ListsExcelInterface
{
    /**
     * @notes 搜索条件
     * @return array
     * @author 段誉
     * @date 2022/9/22 15:50
     */
    public function setSearch(): array
    {
        $allowSearch = [];
        return array_intersect(array_keys($this->params), $allowSearch);
    }

    /**
     * 获取列表
     *
     * @return array
     */
    public function lists(): array
    {
        $lists = ConsumeRecharge::field([
            'cr.id as id',
            'cr.sn',
            'cr.user_id',
            'cr.account',
            'cr.name_area',
            'cr.recharge_price',
            'cr.recharge_up_price',
            'cr.recharge_down_price',
            'cr.balances_price',
            'cr.status',
            'cr.pay_time',
            'cr.create_time',
            'u.nickname'
        ])
            ->alias('cr')
            ->leftJoin('user u', 'cr.user_id = u.id')
            ->where($this->handleWhereData($this->params, 'cr.'))
            ->order('cr.id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        if (empty($lists)) {
            return [];
        }

        $newData = [];
        foreach ($lists as $item) {
            $cTime = '';
            if (!empty($item['pay_time'])) {
                $seconds = $item['pay_time'] - strtotime($item['create_time']);

                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $seconds = $seconds % 60;

                $cTime = sprintf("%02d时%02d分%02d秒", $hours, $minutes, $seconds);
            }

            $newData[] = [
                'id' => $item['id'],
                'sn' => $item['sn'],
                'user_show' => '[ID: ' . $item['user_id'] . '] ' . $item['nickname'],
                'account_show' => $item['account'] . ' ' . $item['name_area'],
                'price' => $item['recharge_price'],
                'up_price' => $item['recharge_up_price'],
                'down_price' => $item['recharge_down_price'],
                'balances_price' => $item['balances_price'],
                'status' => $item['status'],
                'time' => $item['create_time'],
                'ctime' => $cTime,
                'sa' => false
            ];
        }

        return $newData;
    }

    /**
     * 获取数量
     *
     * @return int
     */
    public function count(): int
    {
        return ConsumeRecharge::where($this->handleWhereData($this->params))->count();
    }

    /**
     * @return float
     */
    public function sum(): float
    {
        return ConsumeRecharge::where($this->handleWhereData($this->params))->sum('recharge_price');
    }

    /**
     * @param $params
     * @param $pre
     * @return array
     */
    private function handleWhereData($params, $pre = '')
    {
        $newData = [];
        if (isset($params['sn']) && $params['sn'] !== '' && $params['sn'] !== null) {
            $newData[] = [
                $pre . 'sn', 'like', '%' . $params['sn'] . '%'
            ];
        }

        if (isset($params['account']) && $params['account'] !== '' && $params['account'] !== null) {
            $newData[] = [
                $pre . 'account', 'like', '%' . $params['account'] . '%'
            ];
        }

        if (!empty($params['status'])) {
            $newData[] = [
                $pre . 'status', '=', $params['status']
            ];
        }

        if (!empty($params['type'])) {
            $newData[] = [
                $pre . 'type', '=', $params['type']
            ];
        }

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $newData[] = [
                $pre . 'create_time', 'BETWEEN', [strtotime($params['start_time']), strtotime($params['end_time'])]
            ];
        }

        return $newData;
    }

    /**
     * @notes 导出文件名
     * @return string
     * @author 段誉
     * @date 2022/11/24 16:17
     */
    public function setFileName(): string
    {
        return '订单列表';
    }

    /**
     * @notes 导出字段
     * @return string[]
     * @author 段誉
     * @date 2022/11/24 16:17
     */
    public function setExcelFields(): array
    {
        return [
            'sn' => '用户编号',
            'nickname' => '用户昵称',
            'account' => '账号',
            'mobile' => '手机号码',
            'channel' => '注册来源',
            'create_time' => '注册时间',
        ];
    }

}
