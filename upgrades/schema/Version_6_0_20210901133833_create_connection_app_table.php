<?php declare(strict_types=1);

namespace Pim\Upgrade\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version_6_0_20210901133833_create_connection_app_table extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(<<<'SQL'
        CREATE TABLE IF NOT EXISTS akeneo_connectivity_app(
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            logo VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            partner VARCHAR(255) DEFAULT NULL NULL,
            categories JSON NOT NULL,
            certified TINYINT(1) DEFAULT 0 NOT NULL,
            connection_code VARCHAR(100) NOT NULL,
            scopes JSON NOT NULL,
            external_url VARCHAR(255) DEFAULT NULL NULL,
            CONSTRAINT FK_CONNECTIVITY_CONNECTION_connection_code FOREIGN KEY (connection_code) REFERENCES akeneo_connectivity_connection (code)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL
        );
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
