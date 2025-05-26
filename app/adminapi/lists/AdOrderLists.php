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
use app\common\model\AdOrder;
use app\common\lists\ListsSearchInterface;
use app\common\model\user\User;


/**
 * AdOrder列表
 * Class AdOrderLists
 * @package app\adminapi\lists
 */
class AdOrderLists extends BaseAdminDataLists implements ListsSearchInterface
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
            '=' => ['order_type', 'status', 'appeal'],
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
     * @date 2025/05/06 17:29
     */
    public function lists(): array
    {
        $list = AdOrder::where($this->searchWhere)
            ->field(['id', 'user_id', 'to_user_id', 'ad_id', 'order_no', 'order_type', 'num', 'price', 'dan_price', 'pay_type', 'status', 'appeal', 'create_time', 'cancel_type'])
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $user = User::find($item['user_id']);
            $item['username'] = $user['nickname'] ?? '未知';
            $toUser = User::find($item['to_user_id']);
            $item['to_username'] = $toUser['nickname'] ?? '未知';
        }

        return $list;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function count(): int
    {
        return AdOrder::where($this->searchWhere)->count();
    }

}
