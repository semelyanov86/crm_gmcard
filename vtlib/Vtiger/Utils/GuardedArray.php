<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Vtiger_GuardedArray implements ArrayAccess
{
    private $data;

    public function __construct($data = null)
    {
        $this->data = is_null($data) || $data === false ? [] : $data;
    }

    #[ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return isset($this->data[$key]) && array_key_exists($key, $this->data);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return $this->data[$key];
        }

        return null;
    }

    #[ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }
}
