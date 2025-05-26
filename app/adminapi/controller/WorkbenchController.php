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

use app\adminapi\logic\ConsumeQueryLogic;
use app\adminapi\logic\ConsumeRechargeLogic;
use app\adminapi\logic\RechargeLogic;
use app\adminapi\logic\user\UserLogic;
use app\adminapi\logic\WorkbenchLogic;
use app\common\model\ConsumeRecharge;

/**
 * 工作台
 * Class WorkbenchCotroller
 * @package app\adminapi\controller
 */
class WorkbenchController extends BaseAdminController
{

    /**
     * @notes 工作台
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/29 17:01
     */
    public function index()
    {
        $newData = [

            // 版本信息
            'version' => WorkbenchLogic::versionInfo(),

            // 今日数据
            'today' => $this->getSumData(),

            // 常用功能
            'menu' => WorkbenchLogic::menu(),

            // 服务支持
            'support' => WorkbenchLogic::support(),

            // 充值数据
            'visitor' => $this->getGroupRechargeData(),

            // 销售数据
            'sale' => $this->getGroupSalesData()
        ];

        return $this->data($newData);
    }

    /**
     * 汇总数据
     *
     * @return array
     */
    private function getSumData(): array
    {
        $todayTime = strtotime(date('Y-m-d 00:00:00'));

        // 充值数据
        $todayConsumeRechargeData = ConsumeRechargeLogic::countSum($todayTime);
        $totalConsumeRechargeData = ConsumeRechargeLogic::countSum();

        // 查询数据
        $todayQueryData = ConsumeQueryLogic::countSum($todayTime);
        $totalQueryData = ConsumeQueryLogic::countSum();

        // 查询数据
        $todayUserData = UserLogic::count($todayTime);
        $totalUserData = UserLogic::count();

        // 充值额
        $statusArr = [1, 2, 3];
        $todayRechargeData = RechargeLogic::getSum($statusArr, $todayTime);
        $totalRechargeData = RechargeLogic::getSum($statusArr);

        return [
            'time' => date('Y-m-d H:i:s'),

            // 今日销售额
            'today_sales' => number_format((($todayConsumeRechargeData['sum'] ?? 0) + ($todayQueryData['sum'] ?? 0)), 3),

            // 总销售额
            'total_sales' => number_format((($totalConsumeRechargeData['sum'] ?? 0) + ($totalQueryData['sum'] ?? 0)), 3),

            // 订单量 (笔)
            'order_num' => ($todayConsumeRechargeData['cou'] ?? 0) + ($todayQueryData['cou'] ?? 0),

            // 总订单量
            'order_sum' => ($totalConsumeRechargeData['cou'] ?? 0) + ($totalQueryData['cou'] ?? 0),

            // 今日新增用户量
            'today_new_user' => $todayUserData,

            // 总用户量
            'total_new_user' => $totalUserData,

            // 今日充值量
            'today_recharge' => number_format($todayRechargeData['sum'] ?? 0, 3),

            // 总充值量
            'total_recharge' => number_format($totalRechargeData['sum'] ?? 0, 3)
        ];
    }

    /**
     * 充值分天数据
     *
     * @return array
     */
    private function getGroupRechargeData(): array
    {
        $startTime = strtotime(date('Y-m-d 00:00:00', time() - 14 * 24 * 3600));

        $statusArr = [1, 2, 3];
        $data = RechargeLogic::getGroupSumByDay($statusArr, $startTime);
        $data = array_column($data, 'sum', 'date');

        $newData = [
            'date' => [],
            'list' => [
                'name' => '充值金额',
                'data' => [],
            ]
        ];

        while ($startTime < time()) {
            $tmpTime = date('m/d', $startTime);

            $newData['date'][] = $tmpTime;
            if (isset($data[$tmpTime])) {
                $newData['list'][0]['data'][] = $data[$tmpTime];
            } else {
                $newData['list'][0]['data'][] = 0;
            }

            $startTime += 24 * 3600;
        }

        rsort($newData['date']);
        return $newData;
    }

    /**
     * 销售分天数据
     *
     * @return array
     */
    private function getGroupSalesData(): array
    {
        $startTime = strtotime(date('Y-m-d 00:00:00', time() - 6 * 24 * 3600));

        $consumeRechargeData = ConsumeRechargeLogic::getGroupSumByDay($startTime);
        $consumeRechargeData = array_column($consumeRechargeData, 'sum', 'date');
        $consumeQueryData = ConsumeQueryLogic::getGroupSumByDay($startTime);
        $consumeQueryData = array_column($consumeQueryData, 'sum', 'date');

        $newData = [
            'date' => [],
            'list' => [
                'name' => '销售量',
                'data' => [],
            ]
        ];

        while ($startTime < time()) {
            $tmpTime = date('m/d', $startTime);

            $newData['date'][] = $tmpTime;
            $tmpConsumeRecharge = $consumeRechargeData[$tmpTime] ?? 0;
            $tmpConsumeQuery = $consumeQueryData[$tmpTime] ?? 0;

            $newData['list'][0]['data'][] = $tmpConsumeRecharge + $tmpConsumeQuery;

            $startTime += 24 * 3600;
        }

        rsort($newData['date']);
        return $newData;
    }
}
