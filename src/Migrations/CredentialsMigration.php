<?php

namespace PaymentSystem\Laravel\Nuvei\Migrations;

use PaymentSystem\Laravel\Contracts\MigrationTemplateInterface;

final class CredentialsMigration implements MigrationTemplateInterface
{
    public function getStubPath(): string
    {
        return __DIR__ . '/stubs/create_nuvei_credentials_table.stub';
    }

    public function getTableName(): string
    {
        return 'nuvei_credentials';
    }
}