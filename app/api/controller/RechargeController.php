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
namespace app\api\controller;

use app\api\lists\recharge\RechargeLists;
use app\api\logic\RechargeLogic;
use app\api\service\UserMealService;
use app\api\validate\RechargeValidate;
use think\response\Json;


/**
 * 充值控制器
 * Class RechargeController
 * @package app\shopapi\controller
 */
class RechargeController extends BaseApiController
{

    /**
     * @notes 获取充值列表
     * @return Json
     * @author 段誉
     * @date 2023/2/23 18:55
     */
    public function lists()
    {
        return $this->dataLists(new RechargeLists());
    }

    /**
     * @notes 充值配置
     * @return Json
     * @author 段誉
     * @date 2023/2/24 16:56
     */
    public function config()
    {
        return $this->data(RechargeLogic::config($this->userId));
    }
}
