<?php

namespace app\adminapi\controller;

use app\adminapi\lists\AdLists;
use app\adminapi\logic\AdLogic;
use app\adminapi\validate\AdValidate;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * Ad控制器
 */
class AdController extends BaseAdminController
{

    /**
     * 获取列表
     *
     * @return Json
     */
    public function lists(): Json
    {
        try {
            return $this->dataLists(new AdLists());
        } catch (Exception $e) {
            Log::record('Exception: api-AdController-lists Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 上架
     *
     * @return Json
     */
    public function onAd(): Json
    {
        $params = (new AdValidate())->post()->goCheck('detail');

        try {
            $res = AdLogic::onAd($params['id']);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success('上架成功', [], 1, 1);
        } catch (Exception $e) {
            Log::record('Exception: api-AdController-onAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量上架
     *
     * @return Json
     */
    public function batchOnAd(): Json
    {
        $params = (new AdValidate())->post()->goCheck('detail');

        try {
            $res = AdLogic::batchOnAd($params['id']);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success('上架成功', [], 1, 1);
        } catch (Exception $e) {
            Log::record('Exception: api-AdController-batchOnAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 下架
     *
     * @return Json
     */
    public function offAd(): Json
    {
        $params = (new AdValidate())->post()->goCheck('detail');

        try {
            $res = AdLogic::offAd($params['id']);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success('下架成功', [], 1, 1);
        } catch (Exception $e) {
            Log::record('Exception: api-AdController-offAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 批量下架
     *
     * @return Json
     */
    public function batchOffAd(): Json
    {
        $params = (new AdValidate())->post()->goCheck('detail');

        try {
            $res = AdLogic::batchOffAd($params['id']);
            if (!$res) {
                return $this->fail(AdLogic::getError());
            }

            return $this->success('下架成功', [], 1, 1);
        } catch (Exception $e) {
            Log::record('Exception: api-AdController-batchOffAd Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 删除
     *
     * @return Json
     */
    public function delete(): Json
    {
        $params = (new AdValidate())->post()->goCheck('detail');

        try {
            if (is_numeric($params['id'])) {
                $res = AdLogic::deleteData($params['id']);
                if (!$res) {
                    return $this->fail(AdLogic::getError());
                }

                return $this->success('删除成功', [], 1, 1);
            }

            if (is_array($params['id'])) {

                $failMsg = '';
                foreach ($params['id'] as $id) {
                    $res = AdLogic::deleteData($id);
                    if (!$res) {
                        $failMsg .= $failMsg . PHP_EOL;
                    }
                }

                if (!empty($failMsg)) {
                    return $this->fail($failMsg);
                }

                return $this->success('删除成功', [], 1, 1);
            }

        } catch (Exception $e) {
            Log::record('Exception: api-AdController-delete Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
