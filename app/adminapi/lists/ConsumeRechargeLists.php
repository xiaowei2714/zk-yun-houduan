<?php

namespace app\adminapi\lists;

use app\common\lists\ListsExcelInterface;
use app\common\model\ConsumeRecharge;
use think\facade\Config;

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
        $alias = 'cr';
        $aliasD = $alias . '.';
        $obj = ConsumeRecharge::field([
            $aliasD . 'id as id',
            $aliasD . 'sn',
            $aliasD . 'user_id',
            $aliasD . 'account',
            $aliasD . 'account_type',
            $aliasD . 'name_area',
            $aliasD . 'recharge_price',
            $aliasD . 'recharge_up_price',
            $aliasD . 'recharge_down_price',
            $aliasD . 'balances_price',
            $aliasD . 'pay_price',
            $aliasD . 'status',
            $aliasD . 'type',
            $aliasD . 'pay_time',
            $aliasD . 'create_time',
            'a.name as admin_name',
            'u.nickname'
        ])->alias($alias)
            ->leftJoin('user u', $aliasD . 'user_id = u.id')
            ->leftJoin('admin a', $aliasD . 'admin_id = a.id');

        $obj = $this->handleWhereData($obj, $this->params, $aliasD);

        $lists = $obj->order($aliasD . 'id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        if (empty($lists)) {
            return [];
        }

        $areaData = Config::get('project.area');

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

            $accountTypeShow = '';
            switch ($item['account_type']) {
                case 1:
                    $accountTypeShow = ' 移动';
                    break;

                case 2:
                    $accountTypeShow = ' 联通';
                    break;

                case 3:
                    $accountTypeShow = ' 电信';
                    break;

                case 4:
                    $accountTypeShow = ' 虚拟';
                    break;
            }

            $nameArea = $item['name_area'];
            if ($item['type'] == 2) {
                $nameArea = $areaData[$item['name_area']] ?? '';
            }

            $newData[] = [
                'id' => $item['id'],
                'sn' => $item['sn'],
                'admin_name' => $item['admin_name'],
                'user_show' => '[ID: ' . $item['user_id'] . '] ' . $item['nickname'],
                'account_show' => $item['account'],
                'account_type_show' => $accountTypeShow,
                'name_show' => $nameArea,
                'price' => $item['recharge_price'],
                'up_price' => $item['recharge_up_price'],
                'down_price' => $item['recharge_down_price'],
                'balances_price' => $item['balances_price'],
                'pay_price' => $item['pay_price'],
                'status' => $item['status'],
                'time' => $item['create_time'],
                'type' => $item['type'],
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
        $obj = ConsumeRecharge::field('id');
        $obj = $this->handleWhereData($obj, $this->params);
        return $obj->count();
    }

    /**
     * @return float
     */
    public function sum(): float
    {
        $obj = ConsumeRecharge::field('id');
        $obj = $this->handleWhereData($obj, $this->params);
        return $obj->sum('recharge_price');
    }

    /**
     * @param $obj
     * @param $params
     * @param $pre
     * @return mixed
     */
    private function handleWhereData($obj, $params, $pre = '')
    {
        if (isset($params['sn']) && $params['sn'] !== '' && $params['sn'] !== null) {
            $obj = $obj->where($pre . 'sn', 'like', '%' . $params['sn'] . '%');
        }

        if (!empty($params['account'])) {
            if (is_string($params['account'])) {
                $obj = $obj->where($pre . 'account|name_area', 'like', '%' . $params['account'] . '%');
            }

            if (is_array($params['account'])) {
                $accountParams = [];
                foreach ($params['account'] as $value) {
                    $tmp = trim($value);
                    if (strpos($tmp, ' ') !== false) {
                        $tmpData = explode(' ', $tmp);
                        $accountParams = array_merge($accountParams, $tmpData);
                    } else {
                        $accountParams[] = $tmp;
                    }
                }

                $accountParams = array_unique($accountParams);
                $accountParams = array_values($accountParams);

                $obj = $obj->where(function ($query) use ($accountParams, $pre) {
                    foreach ($accountParams as $key => $value) {
                        if ($key == 0) {
                            $query->where($pre . 'account|name_area', 'like', '%' . $value . '%');
                        } else {
                            $query->whereOr($pre . 'account|name_area', 'like', '%' . $value . '%');
                        }
                    }
                });
            }
        }

        if (!empty($params['status'])) {
            $obj = $obj->where($pre . 'status', '=', $params['status']);
        }

        if (!empty($params['type'])) {
            $obj = $obj->where($pre . 'type', '=', $params['type']);
        }

        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $obj = $obj->where($pre . 'create_time', 'BETWEEN', [strtotime($params['start_time']), strtotime($params['end_time'])]);
        }

        if (!empty($params['account_type'])) {
            $obj = $obj->where($pre . 'account_type', '=', $params['account_type']);
        }

        return $obj;
    }

    /**
     * @param $params
     * @param $pre
     * @return array
     */
    private function handleWhereData1($params, $pre = '')
    {
        $newData = [];
        if (isset($params['sn']) && $params['sn'] !== '' && $params['sn'] !== null) {
            $newData[] = [
                $pre . 'sn', 'like', '%' . $params['sn'] . '%'
            ];
        }

        if (!empty($params['account'])) {
            $tmpAccount = '';
            if (is_string($params['account'])) {
                $newData[] = [
                    $pre . 'account|name_area', 'like', '%' . $params['account'] . '%'
                ];
            }

            if (is_array($params['account'])) {

            }
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

        if (!empty($params['account_type'])) {
            $newData[] = [
                $pre . 'account_type', '=', $params['account_type']
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
