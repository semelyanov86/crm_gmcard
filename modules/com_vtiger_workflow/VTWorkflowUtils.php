<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

// A collection of util functions for the workflow module

class VTWorkflowUtils
{
    public static $userStack;

    public static $loggedInUser;

    public function __construct()
    {
        global $current_user;
        if (empty(self::$userStack)) {
            self::$userStack = [];
        }
    }

    /**
     * Check whether the given identifier is valid.
     */
    public function validIdentifier($identifier)
    {
        if (is_string($identifier)) {
            return preg_match('/^[a-zA-Z][a-zA-Z_0-9]+$/', $identifier);
        }

        return false;

    }

    /**
     * Push the admin user on to the user stack
     * and make it the $current_user.
     */
    public function adminUser()
    {
        $user = Users::getActiveAdminUser();
        global $current_user;
        if (empty(self::$userStack) || php7_count(self::$userStack) == 0) {
            self::$loggedInUser = $current_user;
        }
        array_push(self::$userStack, $current_user);
        $current_user = $user;

        return $user;
    }

    /**
     * Push the logged in user on the user stack
     * and make it the $current_user.
     */
    public function loggedInUser()
    {
        $user = self::$loggedInUser;
        global $current_user;
        array_push(self::$userStack, $current_user);
        $current_user = $user;

        return $user;
    }

    /**
     * Revert to the previous use on the user stack.
     */
    public function revertUser()
    {
        global $current_user;
        if (php7_count(self::$userStack) != 0) {
            $current_user = array_pop(self::$userStack);
        } else {
            $current_user = null;
        }

        return $current_user;
    }

    /**
     * Get the current user.
     */
    public function currentUser()
    {
        return $current_user;
    }

    /**
     * The the webservice entity type of an EntityData object.
     */
    public function toWSModuleName($entityData)
    {
        $moduleName = $entityData->getModuleName();
        if ($moduleName == 'Activity') {
            $arr = ['Task' => 'Calendar', 'Emails' => 'Emails'];
            $moduleName = $arr[getActivityType($entityData->getId())];
            if ($moduleName == null) {
                $moduleName = 'Events';
            }
        }

        return $moduleName;
    }

    /**
     * Insert redirection script.
     */
    public function redirectTo($to, $message)
    {
        ?>
		<script type="text/javascript" charset="utf-8">
			window.location="<?php echo $to; ?>";
		</script>
		<a href="<?php echo $to; ?>"><?php echo $message; ?></a>
<?php
    }

    /**
     * Check if the current user is admin.
     */
    public function checkAdminAccess()
    {
        global $current_user;

        return strtolower($current_user->is_admin) === 'on';
    }

    /* function to check if the module has workflow
     * @params :: $modulename - name of the module
     */

    public static function checkModuleWorkflow($modulename)
    {
        $result = true;
        if (in_array($modulename, ['Emails', 'Faq', 'PBXManager', 'Users']) || !getTabid($modulename)) {
            $result = false;
        }

        return $result;
    }

    public function vtGetModules($adb)
    {
        $modules_not_supported = ['Emails', 'PBXManager'];
        $sql = 'select distinct vtiger_field.tabid, name
			from vtiger_field
			inner join vtiger_tab
				on vtiger_field.tabid=vtiger_tab.tabid
			where vtiger_tab.name not in(' . generateQuestionMarks($modules_not_supported) . ') and vtiger_tab.isentitytype=1 and vtiger_tab.presence in (0,2) ';
        $it = new SqlResultIterator($adb, $adb->pquery($sql, [$modules_not_supported]));
        $modules = [];
        foreach ($it as $row) {
            $modules[] = $row->name;
        }

        return $modules;
    }
}
