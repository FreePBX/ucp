<?php
namespace FreePBX\modules\Ucp;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		$this->addDependency('userman');
		$this->addDependency('pm2');
		$this->addDependency('certman');
		$this->addDependency('core');
		$this->addConfigs([
			'tables' => $this->dumpTables(),
			'settings' => $this->dumpAdvancedSettings()
		]);
	}
}