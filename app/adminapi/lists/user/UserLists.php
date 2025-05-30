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
namespace app\adminapi\lists\user;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\user\UserTerminalEnum;
use app\common\lists\ListsExcelInterface;
use app\common\model\ConsumeRecharge;
use app\common\model\user\User;


/**
 * 用户列表
 * Class UserLists
 * @package app\adminapi\lists\user
 */
class UserLists extends BaseAdminDataLists implements ListsExcelInterface
{

    /**
     * @notes 搜索条件
     * @return array
     * @author 段誉
     * @date 2022/9/22 15:50
     */
    public function setSearch(): array
    {
        $allowSearch = ['keyword', 'channel', 'create_time_start', 'create_time_end'];
        return array_intersect(array_keys($this->params), $allowSearch);
    }


    /**
     * @notes 获取用户列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/22 15:50
     */
    public function lists(): array
    {
        $field = "id,sn,nickname,sex,avatar,account,mobile,channel,create_time,p_first_user_id,user_money,freeze_money";
        $lists = User::withSearch($this->setSearch(), $this->params)
            ->limit($this->limitOffset, $this->limitLength)
            ->field($field)
            ->order('id desc')
            ->select()->toArray();

        $userIds = [];
        $pFirstUserIds = [];
        foreach ($lists as $item) {
            $userIds[] = $item['id'];
            $pFirstUserIds[] = $item['p_first_user_id'];
        }

        $userIds = array_unique($userIds);
        $pFirstUserIds = array_unique($pFirstUserIds);

        // 获取上级
        $pUserData = User::field('id,nickname')->whereIn('id', $pFirstUserIds)->select()->toArray();
        $pUserData = array_column($pUserData, 'nickname', 'id');

        // 获取下级数量
        $nUserData = User::field([
            'p_first_user_id',
            'count(`id`) as cou'
        ])->whereIn('p_first_user_id', $userIds)
            ->group('p_first_user_id')
            ->select()
            ->toArray();

        $nUserData = array_column($nUserData, 'cou', 'p_first_user_id');

        // 获取今日消费
        $todayData = ConsumeRecharge::field([
            'user_id',
            'sum(`pay_price`) as pay_price'
        ])->whereIn('user_id', $userIds)
            ->where('status', 3)
            ->where('create_time', '>=', strtotime(date('Y-m-d 00:00:00')))
            ->group('user_id')
            ->select()
            ->toArray();

        $todayData = array_column($todayData, 'pay_price', 'user_id');

        // 获取总消费
        $totalData = ConsumeRecharge::field([
            'user_id',
            'sum(`pay_price`) as pay_price'
        ])->whereIn('user_id', $userIds)
            ->where('status', 3)
            ->group('user_id')
            ->select()
            ->toArray();

        $totalData = array_column($totalData, 'pay_price', 'user_id');

        foreach ($lists as &$item) {
//            $item['channel'] = UserTerminalEnum::getTermInalDesc($item['channel']);
            $item['parent_user'] = $pUserData[$item['p_first_user_id']] ?? '暂无';
            $item['subordinate_user'] = $nUserData[$item['id']] ?? 0;
            $item['today_price'] = $todayData[$item['id']] ?? 0;
            $item['total_price'] = $totalData[$item['id']] ?? 0;
        }

        return $lists;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2022/9/22 15:51
     */
    public function count(): int
    {
        return User::withSearch($this->setSearch(), $this->params)->count();
    }


    /**
     * @notes 导出文件名
     * @return string
     * @author 段誉
     * @date 2022/11/24 16:17
     */
    public function setFileName(): string
    {
        return '用户列表';
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
