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

namespace app\adminapi\logic\setting\web;


use app\common\logic\BaseLogic;
use app\common\service\ConfigService;
use app\common\service\FileService;


/**
 * 网站设置
 * Class WebSettingLogic
 * @package app\adminapi\logic\setting
 */
class WebSettingLogic extends BaseLogic
{

    /**
     * @notes 获取网站信息
     * @return array
     * @author 段誉
     * @date 2021/12/28 15:43
     */
    public static function getWebsiteInfo(): array
    {
        return [
            'name' => ConfigService::get('website', 'name'),
            'web_favicon' => FileService::getFileUrl(ConfigService::get('website', 'web_favicon')),
            'web_logo' => FileService::getFileUrl(ConfigService::get('website', 'web_logo')),
            'login_image' => FileService::getFileUrl(ConfigService::get('website', 'login_image')),
            'shop_name' => ConfigService::get('website', 'shop_name'),
            'shop_logo' => FileService::getFileUrl(ConfigService::get('website', 'shop_logo')),
            'index_banner' => FileService::getFileUrl(ConfigService::get('website', 'index_banner')),

            'pc_logo' => FileService::getFileUrl(ConfigService::get('website', 'pc_logo')),
            'pc_title' => ConfigService::get('website', 'pc_title', ''),
            'pc_ico' => FileService::getFileUrl(ConfigService::get('website', 'pc_ico')),
            'pc_desc' => ConfigService::get('website', 'pc_desc', ''),
            'pc_keywords' => ConfigService::get('website', 'pc_keywords', ''),

            'h5_favicon' => FileService::getFileUrl(ConfigService::get('website', 'h5_favicon')),

            'email_host' => ConfigService::get('website', 'email_host', ''),
            'email_username' => ConfigService::get('website', 'email_username', ''),
            'email_password' => ConfigService::get('website', 'email_password', ''),
            'email_port' => ConfigService::get('website', 'email_port', ''),
            'email_encryption' => ConfigService::get('website', 'email_encryption', ''),
            'email_from' => ConfigService::get('website', 'email_from', ''),
            'email_from_name' => ConfigService::get('website', 'email_from_name', ''),

            'reference_rate' => ConfigService::get('website', 'reference_rate', ''),
            'query_hf' => ConfigService::get('website', 'query_hf', ''),
            'query_df' => ConfigService::get('website', 'query_df', ''),
            'share_tips' => ConfigService::get('website', 'share_tips', ''),
            'share_rules' => ConfigService::get('website', 'share_rules', ''),
            'open_substation_tips' => ConfigService::get('website', 'open_substation_tips', ''),
            'substation_price' => ConfigService::get('website', 'substation_price', ''),
            'invite_logo' => FileService::getFileUrl(ConfigService::get('website', 'invite_logo')),
            'kf_qrcode' => FileService::getFileUrl(ConfigService::get('website', 'kf_qrcode')),
            'kf_mobile' => ConfigService::get('website', 'kf_mobile', ''),
            'kf_time' => ConfigService::get('website', 'kf_time', ''),
        ];
    }


    /**
     * @notes 设置网站信息
     * @param array $params
     * @author 段誉
     * @date 2021/12/28 15:43
     */
    public static function setWebsiteInfo(array $params)
    {
        $h5favicon = FileService::setFileUrl($params['h5_favicon']);
        $favicon = FileService::setFileUrl($params['web_favicon']);
        $logo = FileService::setFileUrl($params['web_logo']);
        $login = FileService::setFileUrl($params['login_image']);
        $shopLogo = FileService::setFileUrl($params['shop_logo']);
        $pcLogo = FileService::setFileUrl($params['pc_logo']);
        $pcIco = FileService::setFileUrl($params['pc_ico'] ?? '');
        $indexBanner = FileService::setFileUrl($params['index_banner'] ?? '');
        $inviteLogo = FileService::setFileUrl($params['invite_logo'] ?? '');
        $kfQrcode = FileService::setFileUrl($params['kf_qrcode'] ?? '');

        ConfigService::set('website', 'name', $params['name']);
        ConfigService::set('website', 'web_favicon', $favicon);
        ConfigService::set('website', 'web_logo', $logo);
        ConfigService::set('website', 'login_image', $login);
        ConfigService::set('website', 'shop_name', $params['shop_name']);
        ConfigService::set('website', 'shop_logo', $shopLogo);
        ConfigService::set('website', 'pc_logo', $pcLogo);
        ConfigService::set('website', 'index_banner', $indexBanner);

        ConfigService::set('website', 'pc_title', $params['pc_title']);
        ConfigService::set('website', 'pc_ico', $pcIco);
        ConfigService::set('website', 'pc_desc', $params['pc_desc'] ?? '');
        ConfigService::set('website', 'pc_keywords', $params['pc_keywords'] ?? '');

        ConfigService::set('website', 'h5_favicon', $h5favicon);

        ConfigService::set('website', 'email_host', $params['email_host']);
        ConfigService::set('website', 'email_username', $params['email_username']);
        ConfigService::set('website', 'email_password', $params['email_password']);
        ConfigService::set('website', 'email_port', $params['email_port']);
        ConfigService::set('website', 'email_encryption', $params['email_encryption']);
        ConfigService::set('website', 'email_from', $params['email_from']);
        ConfigService::set('website', 'email_from_name', $params['email_from_name']);

        ConfigService::set('website', 'reference_rate', $params['reference_rate']);
        ConfigService::set('website', 'query_hf', $params['query_hf']);
        ConfigService::set('website', 'query_df', $params['query_df']);
        ConfigService::set('website', 'share_tips', $params['share_tips']);
        ConfigService::set('website', 'share_rules', $params['share_rules']);
        ConfigService::set('website', 'open_substation_tips', $params['open_substation_tips']);
        ConfigService::set('website', 'substation_price', $params['substation_price']);
        ConfigService::set('website', 'invite_logo', $inviteLogo);
        ConfigService::set('website', 'kf_qrcode', $kfQrcode);
        ConfigService::set('website', 'kf_mobile', $params['kf_mobile']);
        ConfigService::set('website', 'kf_time', $params['kf_time']);
    }


    /**
     * @notes 获取版权备案
     * @return array
     * @author 段誉
     * @date 2021/12/28 16:09
     */
    public static function getCopyright() : array
    {
        return ConfigService::get('copyright', 'config', []);
    }


    /**
     * @notes 设置版权备案
     * @param array $params
     * @return bool
     * @author 段誉
     * @date 2022/8/8 16:33
     */
    public static function setCopyright(array $params)
    {
        try {
            if (!is_array($params['config'])) {
                throw new \Exception('参数异常');
            }
            ConfigService::set('copyright', 'config', $params['config'] ?? []);
            return true;
        } catch (\Exception $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }


    /**
     * @notes 设置政策协议
     * @param array $params
     * @author ljj
     * @date 2022/2/15 10:59 上午
     */
    public static function setAgreement(array $params)
    {
        $serviceContent = clear_file_domain($params['service_content'] ?? '');
        $privacyContent = clear_file_domain($params['privacy_content'] ?? '');
        $safetyContent = clear_file_domain($params['safety_content'] ?? '');
        ConfigService::set('agreement', 'service_title', $params['service_title'] ?? '');
        ConfigService::set('agreement', 'service_content', $serviceContent);
        ConfigService::set('agreement', 'privacy_title', $params['privacy_title'] ?? '');
        ConfigService::set('agreement', 'safety_title', $params['safety_title'] ?? '');
        ConfigService::set('agreement', 'privacy_content', $privacyContent);
        ConfigService::set('agreement', 'safety_content', $safetyContent);
    }


    /**
     * @notes 获取政策协议
     * @return array
     * @author ljj
     * @date 2022/2/15 11:15 上午
     */
    public static function getAgreement() : array
    {
        $config = [
            'service_title' => ConfigService::get('agreement', 'service_title'),
            'service_content' => ConfigService::get('agreement', 'service_content'),
            'privacy_title' => ConfigService::get('agreement', 'privacy_title'),
            'privacy_content' => ConfigService::get('agreement', 'privacy_content'),
            'safety_title' => ConfigService::get('agreement', 'safety_title'),
            'safety_content' => ConfigService::get('agreement', 'safety_content'),
        ];

        $config['service_content'] = get_file_domain($config['service_content']);
        $config['privacy_content'] = get_file_domain($config['privacy_content']);
        $config['safety_content'] = get_file_domain($config['safety_content']);

        return $config;
    }

    /**
     * @notes 获取站点统计配置
     * @return array
     * @author yfdong
     * @date 2024/09/20 22:25
     */
    public static function getSiteStatistics()
    {
        return [
            'clarity_code' => ConfigService::get('siteStatistics', 'clarity_code')
        ];
    }

    /**
     * @notes 设置站点统计配置
     * @param array $params
     * @return void
     * @author yfdong
     * @date 2024/09/20 22:31
     */
    public static function setSiteStatistics(array $params)
    {
        ConfigService::set('siteStatistics', 'clarity_code', $params['clarity_code']);
    }
}