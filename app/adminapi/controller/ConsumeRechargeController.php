<?php

namespace app\adminapi\controller;

use app\adminapi\lists\ConsumeRechargeLists;
use app\adminapi\logic\ConsumeRechargeLogic;
use app\adminapi\validate\ConsumeRechargeValidate;
use think\response\Json;

/**
 * 话费、电费充值
 * Class RechargeController
 * @package app\adminapi\controller
 */
class ConsumeRechargeController extends BaseAdminController
{
    /**
     * 获取列表
     *
     * @return Json
     */
    public function lists(): Json
    {
        return $this->dataLists(new ConsumeRechargeLists());
    }

    /**
     * 获取列表
     *
     * @return Json
     */
    public function sum(): Json
    {
        $data = (new ConsumeRechargeLists())->sum();
        return $this->success('', ['sum' => number_format($data, 2)]);
    }

    /**
     * 设置为充值中
     *
     * @return Json
     */
    public function setRecharging(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        $info = ConsumeRechargeLogic::info($params['id']);
        if (empty($info) || empty($info['id'])) {
            return $this->fail('不存在的数据，请刷新页面后再试');
        }
        if ($info['status'] == 2) {
            return $this->success('设置成功', [], 1, 1);
        }
        if ($info['status'] == 3) {
            return $this->fail('设置失败，当前状态为已成功');
        }
        if ($info['status'] == 4) {
            return $this->fail('设置失败，当前状态为已失败');
        }

        $res = ConsumeRechargeLogic::setRecharging($info['id']);

        if (!$res) {
            return $this->fail('设置失败');
        }

        return $this->success('设置成功', [], 1, 1);
    }

    /**
     * 设置为批量充值中
     *
     * @return Json
     */
    public function setBatchRecharging(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

        $data = ConsumeRechargeLogic::getData($params['ids']);
        if (empty($data)) {
            return $this->fail('不存在的数据，请刷新页面后再试');
        }

        $selectIds = [];
        $updateIds = [];
        $failMsg = '';
        foreach ($data as $value) {

            $selectIds[] = $value['id'];

            if ($value['status'] == 2) {
                continue;
            }
            if ($value['status'] == 3) {
                $failMsg .= '单号：' . $value['sn'] . ' 设置失败，当前状态为已成功；';
                continue;
            }
            if ($value['status'] == 4) {
                $failMsg .= '单号：' . $value['sn'] . ' 设置失败，当前状态为已失败；';
                continue;
            }

            $updateIds[] = $value['id'];
        }

        if (!empty($updateIds)) {
            $res = ConsumeRechargeLogic::setBatchRecharging($updateIds);
            if (!$res) {
                return $this->fail('批量设置失败');
            }
        }

        if (!empty($failMsg)) {
            return $this->fail('部分失败：' . $failMsg);
        }

        $diffIds = array_diff($params['ids'], $selectIds);
        if (!empty($diffIds)) {
            $failMsg = '部分失败，ID：' . implode('、', $diffIds) . ' 设置失败，找不到订单';
            return $this->fail($failMsg);
        }

        return $this->success('设置成功', [], 1, 1);
    }

    /**
     * 设置为成功
     *
     * @return Json
     */
    public function setSuccess(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        $info = ConsumeRechargeLogic::info($params['id']);
        if (empty($info) || empty($info['id'])) {
            return $this->fail('不存在的数据，请刷新页面后再试');
        }
        if ($info['status'] == 3) {
            return $this->success('设置成功', [], 1, 1);
        }
        if ($info['status'] == 4) {
            return $this->fail('设置失败，当前状态为已失败');
        }

        $res = ConsumeRechargeLogic::setSuccess($info['id']);

        if (!$res) {
            return $this->fail('设置失败');
        }

        return $this->success('设置成功', [], 1, 1);
    }

    /**
     * 设置为批量成功
     *
     * @return Json
     */
    public function setBatchSuccess(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

        $data = ConsumeRechargeLogic::getData($params['ids']);
        if (empty($data)) {
            return $this->fail('不存在的数据，请刷新页面后再试');
        }

        $selectIds = [];
        $updateIds = [];
        $failMsg = '';
        foreach ($data as $value) {

            $selectIds[] = $value['id'];

            if ($value['status'] == 3) {
                continue;
            }
            if ($value['status'] == 4) {
                $failMsg .= '单号：' . $value['sn'] . ' 设置失败，当前状态为已失败；';
                continue;
            }

            $updateIds[] = $value['id'];
        }

        if (!empty($updateIds)) {
            $res = ConsumeRechargeLogic::setBatchSuccess($updateIds);
            if (!$res) {
                return $this->fail('批量设置失败');
            }
        }

        if (!empty($failMsg)) {
            return $this->fail('部分失败：' . $failMsg);
        }

        $diffIds = array_diff($params['ids'], $selectIds);
        if (!empty($diffIds)) {
            $failMsg = '部分失败，ID：' . implode('、', $diffIds) . ' 设置失败，找不到订单';
            return $this->fail($failMsg);
        }

        return $this->success('设置成功', [], 1, 1);
    }

    /**
     * 设置为失败
     *
     * @return Json
     */
    public function setFail(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needId');

        $info = ConsumeRechargeLogic::info($params['id']);
        if (empty($info) || empty($info['id'])) {
            return $this->fail('不存在的数据，请刷新页面后再试');
        }
        if ($info['status'] == 4) {
            return $this->success('设置成功', [], 1, 1);
        }
        if ($info['status'] == 3) {
            return $this->fail('设置失败，当前状态为已成功');
        }

        $res = ConsumeRechargeLogic::setFail($info['id']);

        if (!$res) {
            return $this->fail('设置失败');
        }

        return $this->success('设置成功', [], 1, 1);
    }

    /**
     * 设置为批量失败
     *
     * @return Json
     */
    public function setBatchFail(): Json
    {
        $params = (new ConsumeRechargeValidate())->post()->goCheck('needIds');

        $data = ConsumeRechargeLogic::getData($params['ids']);
        if (empty($data)) {
            return $this->fail('不存在的数据，请刷新页面后再试');
        }

        $selectIds = [];
        $updateIds = [];
        $failMsg = '';
        foreach ($data as $value) {

            $selectIds[] = $value['id'];

            if ($value['status'] == 3) {
                $failMsg = '设置失败，当前状态为已成功；';
                continue;
            }
            if ($value['status'] == 4) {
                continue;
            }

            $updateIds[] = $value['id'];
        }

        if (!empty($updateIds)) {
            $res = ConsumeRechargeLogic::setBatchFail($updateIds);
            if (!$res) {
                return $this->fail('批量设置失败');
            }
        }

        if (!empty($failMsg)) {
            return $this->fail('部分失败：' . $failMsg);
        }

        $diffIds = array_diff($params['ids'], $selectIds);
        if (!empty($diffIds)) {
            $failMsg = '部分失败，ID：' . implode('、', $diffIds) . ' 设置失败，找不到订单';
            return $this->fail($failMsg);
        }

        return $this->success('设置成功', [], 1, 1);
    }
}
