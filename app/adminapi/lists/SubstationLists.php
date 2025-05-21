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

namespace app\adminapi\lists;


use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\Substation;
use app\common\lists\ListsSearchInterface;
use think\facade\Db;


/**
 * Substation列表
 * Class SubstationLists
 * @package app\adminapi\lists
 */
class SubstationLists extends BaseAdminDataLists implements ListsSearchInterface
{


    /**
     * @notes 设置搜索条件
     * @return \string[][]
     * @author Jarshs
     * @date 2025/03/31 15:52
     */
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
            '%like%' => ['user_id'],
        ];
    }

    /**
     * 获取列表
     *
     * @return array
     */
    public function lists(): array
    {
        $tablePre = env('database.prefix');
        $alias = '`sub`';
        $aliasD = $alias . '.';
        $userTable = $tablePre . 'user';
        $subTable = $tablePre . 'substation';
        $userMoneyLogTable = $tablePre . 'user_money_log';

        $params = $this->params;
        $newWhere = $aliasD . '`delete_time` IS NULL';
        if (isset($params['user_id']) && $params['user_id'] !== '' && $params['user_id'] !== null) {
            $newWhere .= ' AND `u`.`nickname` like "%' . $params['user_id']. '%"';
        }

        if (!empty($params['status'])) {
            $newWhere .= ' AND ' . $aliasD . '`status` = ' . $params['status'];
        }

        $today = strtotime(date('Y-m-d 00:00:00'));

        $sql = <<< EOT
SELECT
	$aliasD`id`,
	$aliasD`user_id`,
	$aliasD`parent_user_id`,
	$aliasD`status`,
	$aliasD`create_time`,
	u.user_money AS user_money,
	u.total_award_price AS user_award_price,
	u.nickname AS u_nickname,
	pu.nickname AS pu_nickname,
	(
	    SELECT count(*) FROM $userTable WHERE p_first_user_id = `user_id` 
	) AS n_count,
    (
	    SELECT sum( `change_money` ) FROM $userMoneyLogTable WHERE user_id = `user_id` AND create_time >= $today 
	) AS today_award_price 
    FROM
	    `$subTable` `sub`
	    LEFT JOIN `$userTable` `u` ON `sub`.`user_id` = `u`.`id`
	    LEFT JOIN `$userTable` `pu` ON `sub`.`parent_user_id` = `pu`.`id` 
    WHERE
        $newWhere
    ORDER BY
        `id` DESC 
        LIMIT $this->limitOffset,
        $this->limitLength
EOT;

        $lists = Db::query($sql);

        $newData = [];
        foreach ($lists as $item) {
            $newData[] = [
                'id' => $item['id'],
                'user_id' => $item['user_id'],
                'parent_user_id' => $item['parent_user_id'],
                'user_money' => $item['user_money'],
                'today_award_price' => $item['today_award_price'] ?? '0.00',
                'user_award_price' => $item['user_award_price'] ?? '0.00',
                'user_show' => $item['u_nickname'],
                'p_user_show' => $item['pu_nickname'],
                'n_count' => $item['n_count'],
                'status' => $item['status'],
                'ctime' => $item['create_time'],
            ];
        }

        return $newData;
    }


    /**
     * @notes 获取数量
     * @return int
     * @author Jarshs
     * @date 2025/03/31 15:52
     */
    public function count(): int
    {
        $tablePre = env('database.prefix');
        $userTable = $tablePre . 'user';
        $subTable = $tablePre . 'substation';

        $params = $this->params;
        $newWhere = '`sub`.`delete_time` IS NULL';
        if (isset($params['user_id']) && $params['user_id'] !== '' && $params['user_id'] !== null) {
            $newWhere .= ' AND `u`.`nickname` like "%' . $params['user_id']. '%"';
        }

        if (!empty($params['status'])) {
            $newWhere .= ' AND `sub`.`status` = ' . $params['status'];
        }

        $sql = <<< EOT
SELECT COUNT(*) AS cou FROM `$subTable` `sub` LEFT JOIN `$userTable` `u` ON `sub`.`user_id` = `u`.`id` WHERE $newWhere
EOT;

        $res = Db::query($sql);
        return $res[0]['cou'] ?? 0;
    }
}
