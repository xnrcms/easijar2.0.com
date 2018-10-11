<?php
/**
 * actionlog.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-23 15:30
 * @modified   2018-05-23 15:30
 */

namespace Models;

use Carbon\Carbon;

class ActionLog extends Base
{
    const SAVE_ORIGIN_DATA = false;
    protected $table = 'action_log';
    protected $fillable = ['user_id', 'table', 'action', 'origin_data', 'sql', 'ip', 'user_agent', 'url',
        'request_key', 'date_added'];

    public function getPrimaryName()
    {
        return 'id';
    }

    public static function createActionLog($sql)
    {
        $actionLog = new self();
        return $actionLog->checkAndSaveLog($sql);
    }

    public static function getActionLogs($filters, $withPage = false)
    {
        $filterUser = array_get($filters, 'filter_user');
        $filterAction = array_get($filters, 'filter_action');
        $filterIp = array_get($filters, 'filter_ip');
        $filterDateAdded = array_get($filters, 'filter_date_added');
        $filterTable = array_get($filters, 'filter_table');
        $filterSql = array_get($filters, 'filter_sql');
        $start = array_get($filters, 'start', 0);
        $limit = array_get($filters, 'limit', 20);
        $builder = self::query();

        if ($filterUser) {
            $builder->join('user', 'user.user_id', '=', 'action_log.user_id')
                ->where(function ($query) use ($filterUser) {
                    $query->where('action_log.user_id', $filterUser)
                        ->orwhere('username', 'like', "%$filterUser%")
                        ->orwhere('fullname', 'like', "%$filterUser%");
                });
            $builder->select(['action_log.*', 'user.username', 'user.fullname']);
        }
        if ($filterAction) {
            $builder->where('action', $filterAction);
        }
        if ($filterIp) {
            $builder->where('ip', $filterIp);
        }
        if ($filterDateAdded) {
            $datetime = Carbon::createFromFormat('Y-m-d', $filterDateAdded, 'PRC');
            $startDate = $datetime->startOfDay()->toDateTimeString();
            $endDate = $datetime->addDay()->toDateTimeString();
            $builder->where('action_log.date_added', '>=', $startDate)
                ->where('action_log.date_added', '<=', $endDate);
        }
        if ($filterTable) {
            $builder->where('table', 'like', "%{$filterTable}%");
        }
        if ($filterSql) {
            $builder->where('sql', 'like', "%{$filterSql}%");
        }

        if ($withPage) {
            $builder->skip($start)->take($limit);
        }
        $builder->orderByDesc('id');
        return $builder->get();
    }


    private function checkAndSaveLog($sql)
    {
        if (!is_admin()) {
            return false;
        }
        $this->setSessionData();
        if (!$this->user_id) {
            return false;
        }
        $sql = strtolower($sql);
        $this->action = strtok($sql, ' ');
        if (!in_array($this->action, ['insert', 'update', 'delete'])) {
            return false;
        }
        $this->table = $this->parseTable($sql);
        if (!$this->table) {
            return false;
        }
        $blacklistTables = $this->getBlacklistTables();
        if (in_array($this->table, $blacklistTables)) {
            return false;
        }
        $blacklistSql = $this->getBlacklistSQLs();
        if (in_array($sql, $blacklistSql)) {
            return false;
        }
        return $this->recordLog($sql);
    }

    private function setSessionData()
    {
        $session = registry()->get('session');
        if (!is_admin()) {
            return false;
        }
        $this->user_id = array_get($session->data, 'user_id');
        $this->customer_id = array_get($session->data, 'customer_id');
        $this->request_key = substr(md5(time()), 0, 6);
        return $this;
    }

    private function getBlacklistTables()
    {
        return [
            DB_PREFIX . 'cart',
            DB_PREFIX . 'session'
        ];
    }

    private function getBlacklistSQLs()
    {
        $list = array();
        $ip = $_SERVER['REMOTE_ADDR'];

        $list[] = "update " . DB_PREFIX . "user set ip = '{$ip}' where user_id = '{$this->user_id}'";
        if ($this->customer_id) {
            $languageId = config('config_language_id');
            $list[] = "update " . DB_PREFIX . "customer set language_id = '{$languageId}', ip = '{$ip}' where customer_id = '{$this->customer_id}'";
        }
        return $list;
    }

    private function recordLog($sql)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('PRC'));
        $logData = array(
            'user_id' => (int)$this->user_id,
            'table' => $this->table,
            'action' => $this->action,
            'origin_data' => $this->getOriginData($sql),
            'sql' => $sql,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'url' => $_SERVER['REQUEST_URI'],
            'request_key' => $this->request_key,
            'date_added' => $dateTime->format('Y-m-d H:i:s')
        );
        return self::create($logData);
    }

    private function getOriginData($sql)
    {
        if (!self::SAVE_ORIGIN_DATA) {
            return '';
        }

        if ($this->action == 'insert') {
            return json_encode([]);
        }
        $adaptor = registry()->get('db')->getAdaptor();
        $wheres = explode('where', $sql);
        $condition = array_get($wheres, '1');
        $sql = "SELECT * FROM " . $this->table . " WHERE " . $condition;
        $query = $adaptor->query($sql);
        return json_encode($query->rows);
    }

    private function parseTable($sql)
    {
        $table = '';
        if (!$this->action) {
            return $table;
        }
        if ($this->action == 'insert') {
            $sqlStr = trim(str_replace('insert into', '', $sql));
            $table = strtok($sqlStr, ' ');
        } elseif ($this->action == 'update') {
            $sqlStr = trim(str_replace('update', '', $sql));
            $table = strtok($sqlStr, ' ');
        } elseif ($this->action == 'delete') {
            $sqlStr = trim(str_replace('delete from', '', $sql));
            $table = strtok($sqlStr, ' ');
        }
        $table = str_replace('`', '', $table);
        if (!stristr($table, 'oc_')) {
            return '';
        }
        return $table;
    }
}
