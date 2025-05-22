<?php

namespace app\api\controller;

use app\api\logic\NoticeRecordLogic;
use app\api\validate\NoticeValidate;
use app\common\logic\NoticeLogic;
use think\facade\Log;
use think\response\Json;
use Exception;

/**
 * 消息通知
 */
class NoticeController extends BaseApiController
{
    /**
     * 每项都是最新的
     *
     * @return Json
     */
    public function everyNewest(): Json
    {
        try {

            $newData = [
                'notice' => [],
                'order' => [],
                'transfer' => [],
                'recharge' => []
            ];

            $tmpData = NoticeLogic::newestInfo(1);
            if (!empty($tmpData)) {
                $newData['notice'] = [
                    'title' => $tmpData['title'],
                    'time' => date('n/j', strtotime($tmpData['create_time'])),
                ];
            }

            $tmpData = NoticeRecordLogic::newestInfo($this->userId, 1);
            if (!empty($tmpData)) {
                $newData['order'] = [
                    'title' => $tmpData['title'],
                    'time' => date('n/j', strtotime($tmpData['create_time'])),
                ];
            }

            $tmpData = NoticeRecordLogic::newestInfo($this->userId, 2);
            if (!empty($tmpData)) {
                $newData['transfer'] = [
                    'title' => $tmpData['title'],
                    'time' => date('n/j', strtotime($tmpData['create_time'])),
                ];
            }

            $tmpData = NoticeRecordLogic::newestInfo($this->userId, 3);
            if (!empty($tmpData)) {
                $newData['recharge'] = [
                    'title' => $tmpData['title'],
                    'time' => date('n/j', strtotime($tmpData['create_time'])),
                ];
            }

            return $this->data($newData);

        } catch (Exception $e) {
            Log::record('Exception: api-NoticeController-everyNewest Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * 列表
     *
     * @return Json
     */
    public function listByUser(): Json
    {
        $params = (new NoticeValidate())->get()->goCheck('type', [
            'user_id' => $this->userId
        ]);

        try {

            $list = NoticeRecordLogic::listByUser($params['user_id'], $params['type']);
            if ($list === false) {
                return $this->fail('系统异常，请联系客服');
            }

            $newData = [];
            foreach ($list as $value) {
                $tmpData = [
                    'id' => $value['id'],
                    'title' => $value['title'],
                    'content' => $value['content'],
                    'ctime' => date('n月j日 H:i', strtotime($value['create_time'])),
                    'time' => $value['create_time']
                ];

                $newData[] = $tmpData;
            }

            return $this->data($newData);

        } catch (Exception $e) {
            Log::record('Exception: api-NoticeController-listByUser Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }

    /**
     * @return Json
     */
    public function info(): Json
    {
        $params = (new NoticeValidate())->get()->goCheck('id', [
            'user_id' => $this->userId
        ]);

        try {

            $info = NoticeRecordLogic::info($params['id']);
            if ($info === false) {
                return $this->fail('系统异常，请联系客服');
            }
            if (empty($info['id'])) {
                return $this->fail('请正确传入ID');
            }
            if ($info['user_id'] != $this->userId) {
                return $this->fail('数据异常，请联系客服');
            }

            $newData = [
                'title' => $info['title'],
                'content' => $info['content'],
                'create_time' => $info['create_time']
            ];

            return $this->data($newData);

        } catch (Exception $e) {
            Log::record('Exception: api-NoticeController-info Error: ' . $e->getMessage() . ' 文件：' . $e->getFile() . ' 行号：' . $e->getLine());
            return $this->fail('系统错误');
        }
    }
}
