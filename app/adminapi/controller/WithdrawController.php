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


namespace app\adminapi\controller;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\WithdrawLists;
use app\adminapi\logic\WithdrawLogic;
use app\adminapi\validate\WithdrawValidate;
use app\common\model\user\User;
use app\common\model\UserMoneyLog;
use app\common\model\Withdraw;


/**
 * Withdraw控制器
 * Class WithdrawController
 * @package app\adminapi\controller
 */
class WithdrawController extends BaseAdminController
{


    /**
     * @notes 获取列表
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function lists()
    {
        return $this->dataLists(new WithdrawLists());
    }

    /**
     * 提现失败
     * Author: Jarshs
     * 2025/5/1
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function txFail()
    {
        $id = request()->post('id');
        $withdraw = Withdraw::find($id);
        if (!$withdraw) {
            return $this->fail('数据不存在');
        }
        $withdraw->status = 3;
        $withdraw->save();
        $user = User::find($withdraw->user_id);
        $user->freeze_money -= $withdraw->money;
        $user->user_money += $withdraw->money;
        $user->save();
        $user = User::find($withdraw->user_id);
        UserMoneyLog::create([
            'user_id' => $withdraw->user_id,
            'type' => 8,
            'desc' => '提现失败返回',
            'change_type' => 1,
            'change_money' => $withdraw->money,
            'changed_money' => $user->user_money,
        ]);
        $this->success();
    }

    /**
     * 提现成功
     * Author: Jarshs
     * 2025/5/1
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function successt()
    {
        $id = request()->post('id');
        $withdraw = Withdraw::find($id);
        if (!$withdraw) {
            return $this->fail('数据不存在');
        }
//        $user = User::find($withdraw->user_id);
//        print_r($user->freeze_money);exit;
        $withdraw->status = 2;
        $withdraw->save();
        $user = User::find($withdraw->user_id);
        $user->freeze_money -= $withdraw->money;
        $user->save();
        $this->success();
    }

    /**
     * 获取提现数据
     * Author: Jarshs
     * 2025/5/1
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function info()
    {
        $jtstartTime = strtotime(date('Y-m-d 00:00:00'));
        $jtendTime = strtotime(date('Y-m-d 23:59:59'));
        $jtMoney = Withdraw::whereBetween('create_time', [$jtstartTime, $jtendTime])
            ->sum('money');
        $jtMoney = number_format($jtMoney, 2, '.', '');
        $jtCount = Withdraw::whereBetween('create_time', [$jtstartTime, $jtendTime])
            ->count();

        $qtStartTime = strtotime(date('Y-m-d 00:00:00', strtotime('-6 days')));
        $qtEndTime = strtotime(date('Y-m-d 23:59:59'));
        $qtMoney = Withdraw::whereBetween('create_time', [$qtStartTime, $qtEndTime])
            ->sum('money');
        $qtMoney = number_format($qtMoney, 2, '.', '');
        $qtCount = Withdraw::whereBetween('create_time', [$qtStartTime, $qtEndTime])
            ->count();

        $ssStartTime = strtotime(date('Y-m-d 00:00:00', strtotime('-29 days')));
        $ssEndTime = strtotime(date('Y-m-d 23:59:59'));
        $ssMoney = Withdraw::whereBetween('create_time', [$ssStartTime, $ssEndTime])
            ->sum('money');
        $ssMoney = number_format($ssMoney, 2, '.', '');
        $ssCount = Withdraw::whereBetween('create_time', [$ssStartTime, $ssEndTime])
            ->count();

        $totalMoney = Withdraw::sum('money');
        $totalMoney = number_format($totalMoney, 2, '.', '');
        $totalCount = Withdraw::count();

        return $this->success('', [
            'today' => $jtMoney,
            'todayCount' => $jtCount,
            'qiDay' => $qtMoney,
            'qiCount' => $qtCount,
            'sanshiDay' => $ssMoney,
            'sanshiCount' => $ssCount,
            'total' => $totalMoney,
            'totalCount' => $totalCount,
        ]);
    }


    /**
     * @notes 添加
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function add()
    {
        $params = (new WithdrawValidate())->post()->goCheck('add');
        $result = WithdrawLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(WithdrawLogic::getError());
    }


    /**
     * @notes 编辑
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function edit()
    {
        $params = (new WithdrawValidate())->post()->goCheck('edit');
        $result = WithdrawLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(WithdrawLogic::getError());
    }


    /**
     * @notes 删除
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function delete()
    {
        $params = (new WithdrawValidate())->post()->goCheck('delete');
        WithdrawLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }


    /**
     * @notes 获取详情
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/03/31 17:07
     */
    public function detail()
    {
        $params = (new WithdrawValidate())->goCheck('detail');
        $result = WithdrawLogic::detail($params);
        return $this->data($result);
    }


}