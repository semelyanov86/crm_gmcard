<?php

class ChecklistItems_List_View extends Vtiger_Index_View
{
    public function __construct()
    {
        header('Location: index.php?module=ChecklistItems&view=Settings&parent=Settings');
    }
}
