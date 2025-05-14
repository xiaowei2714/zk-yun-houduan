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
use app\adminapi\lists\SetMealLists;
use app\adminapi\logic\SetMealLogic;
use app\adminapi\validate\SetMealValidate;


/**
 * SetMeal控制器
 * Class SetMealController
 * @package app\adminapi\controller
 */
class SetMealController extends BaseAdminController
{


    /**
     * @notes 获取列表
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function lists()
    {
        return $this->dataLists(new SetMealLists());
    }


    /**
     * @notes 添加
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function add()
    {
        $params = (new SetMealValidate())->post()->goCheck('add');
//        print_r($params);exit;
        $result = SetMealLogic::add($params);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(SetMealLogic::getError());
    }


    /**
     * @notes 编辑
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function edit()
    {
        $params = (new SetMealValidate())->post()->goCheck('edit');
        $result = SetMealLogic::edit($params);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(SetMealLogic::getError());
    }


    /**
     * @notes 删除
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function delete()
    {
        $params = (new SetMealValidate())->post()->goCheck('delete');
        SetMealLogic::delete($params);
        return $this->success('删除成功', [], 1, 1);
    }


    /**
     * @notes 获取详情
     * @return \think\response\Json
     * @author Jarshs
     * @date 2025/04/02 14:35
     */
    public function detail()
    {
        $params = (new SetMealValidate())->goCheck('detail');
        $result = SetMealLogic::detail($params);
        return $this->data($result);
    }


}