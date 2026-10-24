<?php

namespace App\Console\Commands;

use Illuminate\Database\Console\Migrations\MigrateCommand as BaseMigrateCommand;

class MigrateCommand extends BaseMigrateCommand
{
    /**
     * Schema dumps are named by connection, but the testing connection may use mysql or sqlite.
     * Always load the sqlite dump for sqlite connections.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return string
     */
    protected function schemaPath($connection)
    {
        if (! $this->option('schema-path') && $connection->getDriverName() === 'sqlite') {
            return database_path('schema/testing_persistent-schema.sql');
        }

        return parent::schemaPath($connection);
    }
}
