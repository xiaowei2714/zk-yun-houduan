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
use app\common\model\Withdraw;
use app\common\lists\ListsSearchInterface;


/**
 * Withdraw列表
 * Class WithdrawLists
 * @package app\adminapi\lists
 */
class WithdrawLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
            '%like%' => ['order_no'],
        ];
    }


    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function lists(): array
    {
        $alias = 'w';
        $aliasD = $alias . '.';
        $list = Withdraw::where($this->searchWhere)
            ->field([
                $aliasD . 'id',
                $aliasD . 'user_id',
                $aliasD . 'user_id',
                $aliasD . 'money',
                $aliasD . 'address',
                $aliasD . 'order_no',
                $aliasD . 'create_time',
                $aliasD . 'pay_time',
                $aliasD . 'status',
                $aliasD . 'fail_msg',
                'u.sn as user_sn',
            ])
            ->alias($alias)
            ->leftJoin('user u', $aliasD . 'user_id = u.id')
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            if ($item['status'] == 1) {
                $item['status_txt'] = '待审核';
            } elseif ($item['status'] == 2) {
                $item['status_txt'] = '提现成功';
            } elseif ($item['status'] == 3) {
                $item['status_txt'] = '提现失败';
            }
        }

        return $list;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function count(): int
    {
        return Withdraw::where($this->searchWhere)->count();
    }

}
