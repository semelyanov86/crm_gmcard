<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/**
 * Base Model Class.
 */
#[AllowDynamicProperties]
class Vtiger_Base_Model
{
    protected $valueMap;

    /**
     * Constructor.
     * @param array $values
     */
    public function __construct($values = [])
    {
        $this->valueMap = $values;
    }

    /**
     * Function to get the value for a given key.
     * @return Value for the given key
     */
    public function get($key)
    {
        return $this->valueMap[$key] ?? null;
    }

    /**
     * Function to get the raw value for a given key.
     * @return Raw Value for the given key
     */
    public function getRaw($key)
    {
        return $this->rawData[$key] ?? '';
    }

    /**
     * Function to get the value if its safe to use for SQL Query (column).
     * @param <String> $key
     * @param <Boolean> $skipEmpty - Skip the check if string is empty
     * @return Value for the given key
     */
    public function getForSql($key, $skipEmtpy = true)
    {
        return Vtiger_Util_Helper::validateStringForSql($this->get($key), $skipEmtpy);
    }

    /**
     * Function to set the value for a given key.
     * @return Vtiger_Base_Model
     */
    public function set($key, $value)
    {
        $this->valueMap[$key] = $value;

        return $this;
    }

    /**
     * Function to set all the values for the Object.
     * @param array (key-value mapping) $values
     * @return Vtiger_Base_Model
     */
    public function setData($values)
    {
        $this->valueMap = $values;

        return $this;
    }

    /**
     * Function to get all the values of the Object.
     * @return array (key-value mapping)
     */
    public function getData()
    {
        return $this->valueMap;
    }

    /**
     * Function to check if the key exists.
     * @param string $key
     */
    public function has($key)
    {
        return array_key_exists($key, (array) $this->valueMap); // valueMap can be array or TrackableObject
    }

    /**
     * Function to check if the key is empty.
     * @param type $key
     */
    public function isEmpty($key)
    {
        return !isset($this->valueMap[$key]) || empty($this->valueMap[$key]);
    }
}
