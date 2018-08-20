<?php
namespace FreePBX\modules\Ucp;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $configs = [];
    $this->addDependency('userman');
    $this->addDependency('ps2');
    $this->addDependency('certman');
    $this->addDependency('core');
    $this->addConfigs($configs);
  }
}