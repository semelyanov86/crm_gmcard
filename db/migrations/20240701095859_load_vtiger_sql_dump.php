<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LoadVtigerSqlDump extends AbstractMigration
{
    public function up(): void
    {
        $sqlFilePath = 'db/dump/vtiger.sql';

        if (!file_exists($sqlFilePath)) {
            throw new RuntimeException("SQL file not found: {$sqlFilePath}");
        }

        $sql = file_get_contents($sqlFilePath);

        // Execute the SQL dump
        $this->execute($sql);
    }

    public function down(): void
    {
        $tables = $this->fetchAll('SHOW TABLES');

        $this->execute('SET foreign_key_checks = 0');

        // Drop all tables
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $this->execute("DROP TABLE IF EXISTS `{$tableName}`");
        }

        // Re-enable foreign key checks
        $this->execute('SET foreign_key_checks = 1');
    }
}
