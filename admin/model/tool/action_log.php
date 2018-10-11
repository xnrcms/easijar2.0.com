<?php
/**
 * action_log.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-06-01 09:38
 * @modified   2018-06-01 09:38
 */


class ModelToolActionLog extends Model
{
    public function getTotalActionLogs($filterData)
    {
        return \Models\ActionLog::getActionLogs($filterData)->count();
    }

    public function getActionLogs($filterData)
    {
        return \Models\ActionLog::getActionLogs($filterData, true)->toArray();
    }

    public function getActionLog($id)
    {
        return \Models\ActionLog::find($id)->toArray();
    }
}
