<?php

namespace Icap\BlogBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2018/03/13 09:20:45
 */
class Version20200924102700 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
            DROP TABLE IF EXISTS icap__blog_widget_blog
        ');
    }

    public function down(Schema $schema)
    {
    }
}
