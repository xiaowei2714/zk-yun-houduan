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

namespace app\api\logic;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

/**
 * 网站设置
 * Class WebSettingLogic
 * @package app\adminapi\logic\setting
 */
class WebSettingLogic extends BaseLogic
{
    /**
     * 获取汇率
     *
     * @return array|int|mixed|string
     */
    public static function getReferenceRate()
    {
        return ConfigService::get('website', 'reference_rate');
    }

    /**
     * 获取查询电话花费Y币
     *
     * @return array|int|mixed|string
     */
    public static function getQueryPhone()
    {
        return ConfigService::get('website', 'query_hf');
    }

    /**
     * 获取查询电费花费Y币
     *
     * @return array|int|mixed|string
     */
    public static function getQueryElectricity()
    {
        return ConfigService::get('website', 'query_df');
    }
}
