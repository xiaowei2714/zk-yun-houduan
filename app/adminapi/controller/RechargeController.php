<?php

namespace app\adminapi\controller;

use app\adminapi\lists\RechargeLists;
use app\adminapi\logic\RechargeLogic;
use app\adminapi\validate\RechargeValidate;
use app\api\logic\AdLogic;
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
                'all_sum' => '0.000',
                'today_count' => 0,
                'today_sum' => '0.000',
                'seven_days_count' => 0,
                'seven_days_sum' => '0.000',
                'month_count' => 0,
                'month_sum' => '0.000',
            ];

            $statusArr = [2];
            $data = RechargeLogic::getSum($statusArr);
            $newData['all_count'] = $data['cou'];
            if (!empty($data['sum'])) {
                $newData['all_sum'] = substr($data['sum'], 0, strpos($data['sum'], '.') + 4);
            }

            $startTime = strtotime(date('Y-m-d 00:00:00'));
            $data = RechargeLogic::getSum($statusArr, $startTime);
            $newData['today_count'] = $data['cou'];
            if (!empty($data['sum'])) {
                $newData['today_sum']  = substr($data['sum'], 0, strpos($data['sum'], '.') + 4);
            }

            $startTime = strtotime(date('Y-m-d 00:00:00', time() - 6 * 24 * 3600));
            $data = RechargeLogic::getSum($statusArr, $startTime);
            $newData['seven_days_count'] = $data['cou'];
            if (!empty($data['sum'])) {
                $newData['seven_days_sum'] = substr($data['sum'], 0, strpos($data['sum'], '.') + 4);
            }

            $startTime = strtotime(date('Y-m-d 00:00:00', time() - 29 * 24 * 3600));
            $data = RechargeLogic::getSum($statusArr, $startTime);
            $newData['month_count'] = $data['cou'];
            if (!empty($data['sum'])) {
                $newData['month_sum'] = substr($data['sum'], 0, strpos($data['sum'], '.') + 4);
            }

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

    /**
     * 设置为成功
     *
     * @return Json
     */
    public function setSuccess(): Json
    {
        $params = (new RechargeValidate())->post()->goCheck('detail');

        try {

            $res = RechargeLogic::setSuccess($params['id'], $this->adminId);
            if (!$res) {
                return $this->fail(RechargeLogic::getError());
            }

            return $this->success('操作成功', [], 1, 1);
        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-setSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 设置为成功
     *
     * @return Json
     */
    public function setBatchSuccess(): Json
    {
        $params = (new RechargeValidate())->post()->goCheck('detail');

        try {

            $failMsg = '';
            foreach ($params['id'] as $id) {
                $res = RechargeLogic::setSuccess($id, $this->adminId);
                if (!$res) {
                    $failMsg .= AdLogic::getError() . PHP_EOL;
                }
            }

            if (!empty($failMsg)) {
                return $this->fail($failMsg);
            }

            return $this->success('操作成功', [], 1, 1);
        } catch (Exception $e) {
            Log::record('Exception: api-RechargeController-setBatchSuccess Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
