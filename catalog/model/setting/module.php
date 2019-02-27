<?php
class ModelSettingModule extends Model {
	public function getModule($module_id)
	{
		$cache_key   = 'SettingModule_id=' . (int)$module_id . '.getModule.ByModuleId';
        $result      = $this->cache->get($cache_key);

        if ($result && is_array($result))  return $result;

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "module WHERE module_id = '" . (int)$module_id . "'");
		
		$setting 	= [];
		if ($query->row)
		{
			$setting = !empty($query->row['setting'])?json_decode($query->row['setting'], true):[];	
			$this->cache->set($cache_key, $setting);
		}

		return $setting;
	}
}