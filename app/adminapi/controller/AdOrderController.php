<?php

namespace app\adminapi\controller;

use app\adminapi\lists\AdOrderLists;
use app\adminapi\logic\AdOrderLogic;
use app\adminapi\validate\AdOrderValidate;
use app\api\logic\AdLogic;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * AdOrder控制器
 * Class AdOrderController
 * @package app\adminapi\controller
 */
class AdOrderController extends BaseAdminController
{

    /**
     * @notes 获取列表
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function lists()
    {
        return $this->dataLists(new AdOrderLists());
    }


    /**
     * @notes 添加
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function add()
    {
        $params = (new AdOrderValidate())->post()->goCheck('add');
        $result = AdOrderLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(AdOrderLogic::getError());
    }


    /**
     * @notes 编辑
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function edit()
    {
        $params = (new AdOrderValidate())->post()->goCheck('edit');
        $result = AdOrderLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(AdOrderLogic::getError());
    }


    /**
     * @notes 删除
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function delete()
    {
        $params = (new AdOrderValidate())->post()->goCheck('delete');
        AdOrderLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }


    /**
     * @notes 获取详情
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/05/06 17:29
     */
    public function detail()
    {
        $params = (new AdOrderValidate())->goCheck('detail');
        $result = AdOrderLogic::detail($params);
        return $this->data($result);
    }

    /**
     * 完成订单
     *
     * @return Json
     */
    public function completeOrder(): Json
    {
        $params = (new AdOrderValidate())->post()->goCheck('detail');

        try {

            $res = AdOrderLogic::completeOrder($params['id'], $this->adminId);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success('操作成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-AdOrderController-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 完成订单
     *
     * @return Json
     */
    public function batchCompleteOrder(): Json
    {
        $params = (new AdOrderValidate())->post()->goCheck('detail');

        try {
            $failMsg = '';
            foreach ($params['id'] as $id) {
                $res = AdOrderLogic::completeOrder($id, $this->adminId);
                if (!$res) {
                    $failMsg .= AdLogic::getError() . PHP_EOL;
                }
            }

            if (!empty($failMsg)) {
                return $this->fail($failMsg);
            }

            return $this->success('操作成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-AdOrderController-batchCancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 取消订单
     *
     * @return Json
     */
    public function cancelOrder(): Json
    {
        $params = (new AdOrderValidate())->post()->goCheck('detail');

        try {
            $res = AdOrderLogic::cancelOrder($params['id'], $this->adminId);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success('操作成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-AdOrderController-cancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 取消订单
     *
     * @return Json
     */
    public function batchCancelOrder(): Json
    {
        $params = (new AdOrderValidate())->post()->goCheck('detail');

        try {
            $failMsg = '';
            foreach ($params['id'] as $id) {
                $res = AdOrderLogic::cancelOrder($id, $this->adminId);
                if (!$res) {
                    $failMsg .= AdLogic::getError() . PHP_EOL;
                }
            }

            if (!empty($failMsg)) {
                return $this->fail($failMsg);
            }

            return $this->success('操作成功', [], 1, 1);

        } catch (Exception $e) {
            Log::record('Exception: api-AdOrderController-batchCancelOrder Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
