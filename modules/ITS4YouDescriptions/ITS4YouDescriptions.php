<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class ITS4YouDescriptions extends CRMEntity
{
    public $log;

    public $db;

    public $moduleName = 'ITS4YouDescriptions';

    public $parentName = 'Tools';

    public $table_name = 'its4you_descriptions';

    public $table_index = 'descriptionid';

    public $entity_table = 'vtiger_crmentity';

    public $customFieldTable = [
        'its4you_descriptionscf',
        'descriptionid',
    ];

    public $tab_name = [
        'vtiger_crmentity',
        'its4you_descriptions',
        'its4you_descriptionscf',
    ];

    public $tab_name_index = [
        'vtiger_crmentity' => 'crmid',
        'its4you_descriptions' => 'descriptionid',
        'its4you_descriptionscf' => 'descriptionid',
    ];

    /**
     * @var array [<fieldlabel> => array(<tablename> => <columnname>)]
     */
    public $list_fields = [
        'Description Name' => ['its4you_descriptions' => 'descriptionname'],
        'Description No' => ['its4you_descriptions' => 'description_no'],
        'Module' => ['its4you_descriptions' => 'desc4youmodule'],
        'Field' => ['its4you_descriptions' => 'desc4youfield'],
        'Assigned To' => ['crmentity' => 'smownerid'],
        'Description' => ['crmentity' => 'description'],
    ];

    /**
     * @var array [<fieldlabel> => <fieldname>]
     */
    public $list_fields_name = [
        'Description Name' => 'descriptionname',
        'Description No' => 'description_no',
        'Module' => 'desc4youmodule',
        'Field' => 'desc4youfield',
        'Assigned To' => 'assigned_user_id',
        'Description' => 'description',
    ];

    public $column_fields = [];

    /**
     * [module, type, label, url, icon, sequence, handlerInfo].
     * @return array
     */
    public $registerCustomLinks = [
        ['ITS4YouDescriptions', 'HEADERSCRIPT', 'ITS4YouDescriptions_HeaderScript', 'layouts/v7/modules/ITS4YouDescriptions/resources/ITS4YouDescriptions_Hs.js'],
    ];

    public function __construct()
    {
        global $log;
        $this->column_fields = getColumnFields(get_class($this));
        $this->db = PearDatabase::getInstance();
        $this->log = $log;
    }

    public function save_module($module)
    {
        // module specific save
    }

    public function vtlib_handler($moduleName, $eventType)
    {
        require_once 'include/utils/utils.php';
        require_once 'vtlib/Vtiger/Module.php';

        $moduleInstance = Vtiger_Module::getInstance($moduleName);

        switch ($eventType) {
            case 'module.postinstall':
            case 'module.postupdate':
            case 'module.enabled':
                $this->addCustomLinks();
                break;
            case 'module.preupdate':
            case 'module.preuninstall':
            case 'module.disabled':
                $this->deleteCustomLinks();
                break;
        }
    }

    public function addCustomLinks()
    {
        $this->updateNumbering();
        $this->updateCustomLinks();
        Settings_MenuEditor_Module_Model::addModuleToApp($this->moduleName, $this->parentName);

        $this->db->pquery('UPDATE vtiger_field SET presence=? WHERE columnname=? AND tablename=?', [2, 'solution', 'vtiger_troubletickets']);

        include_once 'modules/ModComments/ModComments.php';
        ModComments::addWidgetTo([$this->moduleName]);
        include_once 'modules/ModTracker/ModTracker.php';
        ModTracker::enableTrackingForModule(getTabid($this->moduleName));
    }

    /**
     * @param bool $register
     */
    public function updateCustomLinks($register = true)
    {
        foreach ($this->registerCustomLinks as $customLink) {
            $module = Vtiger_Module::getInstance($customLink[0]);
            $type = $customLink[1];
            $label = $customLink[2];
            $url = str_replace('$LAYOUT$', Vtiger_Viewer::getDefaultLayoutName(), $customLink[3]);

            if ($module) {
                $module->deleteLink($type, $label);

                if ($register) {
                    $module->addLink($type, $label, $url, $customLink[4], $customLink[5], $customLink[6]);
                }
            }
        }
    }

    public function updateNumbering()
    {
        $this->setModuleSeqNumber('configure', $this->moduleName, 'DES', 1);
        $this->updateMissingSeqNumber($this->moduleName);
    }

    public function deleteCustomLinks()
    {
        $this->updateCustomLinks(false);

        include_once 'modules/ModComments/ModComments.php';
        ModComments::removeWidgetFrom([$this->moduleName]);
        include_once 'modules/ModTracker/ModTracker.php';
        ModTracker::disableTrackingForModule(getTabid($this->moduleName));
    }
}
