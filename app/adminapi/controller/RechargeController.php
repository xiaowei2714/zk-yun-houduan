<?php

namespace app\adminapi\controller;

use app\adminapi\lists\RechargeLists;
use app\adminapi\logic\RechargeLogic;
use app\adminapi\validate\RechargeValidate;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * Recharge控制器
 * Class RechargeController
 * @package app\adminapi\controller
 */
class RechargeController extends BaseAdminController
{
    /**
     * 获取列表
     *
     * @return Json
     */
    public function lists()
    {
        try {
            return $this->dataLists(new RechargeLists());
        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-lists Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 获取列表
     *
     * @return Json
     */
    public function sum(): Json
    {
        try {
            $newData = [
                'all_count' => 0,
                'all_sum' => '0.00',
                'today_count' => 0,
                'today_sum' => '0.00',
                'seven_days_count' => 0,
                'seven_days_sum' => '0.00',
                'month_count' => 0,
                'month_sum' => '0.00',
            ];

            $statusArr = [1,2,3];
            $data = RechargeLogic::getSum($statusArr);
            $newData['all_count'] = $data['cou'];
            $newData['all_sum'] = number_format($data['sum'], 2);

            $startTime = strtotime(date('Y-m-d 00:00:00'));
            $data = RechargeLogic::getSum($statusArr, $startTime);
            $newData['today_count'] = $data['cou'];
            $newData['today_sum'] = number_format($data['sum'], 2);

            $startTime = strtotime(date('Y-m-d 00:00:00', time() - 6 * 24 * 3600));
            $data = RechargeLogic::getSum($statusArr, $startTime);
            $newData['seven_days_count'] = $data['cou'];
            $newData['seven_days_sum'] = number_format($data['sum'], 2);

            $startTime = strtotime(date('Y-m-d 00:00:00', time() - 29 * 24 * 3600));
            $data = RechargeLogic::getSum($statusArr, $startTime);
            $newData['month_count'] = $data['cou'];
            $newData['month_sum'] = number_format($data['sum'], 2);

            return $this->success('', $newData);
        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-sum Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 清理订单
     *
     * @return Json
     */
    public function clear(): Json
    {
        try {

            $startTime = time() - 20 * 60;
            $res = RechargeLogic::deleteUnPayOrder($startTime);
            if ($res === false) {
                return $this->fail(RechargeLogic::getError());
            }
            if (!$res) {
                return $this->fail('未查询到有符合条件的订单');
            }

            return $this->success('清理成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-clear Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * @notes 添加
     * @return Json
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public function add()
    {
        $params = (new RechargeValidate())->post()->goCheck('add');
        $result = RechargeLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(RechargeLogic::getError());
    }


    /**
     * @notes 编辑
     * @return Json
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public function edit()
    {
        $params = (new RechargeValidate())->post()->goCheck('edit');
        $result = RechargeLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(RechargeLogic::getError());
    }

    /**
     * @notes 删除
     * @return Json
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public function delete()
    {
        $params = (new RechargeValidate())->post()->goCheck('delete');
        RechargeLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }


    /**
     * @notes 获取详情
     * @return Json
     * @author Jarshs
     * @date 2025/03/31 16:12
     */
    public function detail()
    {
        $params = (new RechargeValidate())->goCheck('detail');
        $result = RechargeLogic::detail($params);
        return $this->data($result);
    }
}
