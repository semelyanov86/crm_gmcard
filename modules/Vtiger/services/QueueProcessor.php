<?php

class Vtiger_QueueProcessor_Service
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): bool
    {
        return true;
    }
}