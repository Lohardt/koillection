<?php

declare(strict_types=1);

namespace App\Migrations\Mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200921211919 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add counters properties';
    }

    public function up(Schema $schema) : void
    {
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE koi_album ADD photos_counter INT DEFAULT 0 NOT NULL, ADD children_counter INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE koi_collection ADD items_counter INT DEFAULT 0 NOT NULL, ADD children_counter INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE koi_wishlist ADD wishes_counter INT DEFAULT 0 NOT NULL, ADD children_counter INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->skipIf(true, 'Always move forward.');
    }
}
