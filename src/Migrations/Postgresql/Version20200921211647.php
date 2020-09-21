<?php

declare(strict_types=1);

namespace App\Migrations\Postgresql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200921211647 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add counters properties';
    }

    public function up(Schema $schema) : void
    {
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE koi_album ADD photos_counter INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE koi_album ADD children_counter INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE koi_collection ADD items_counter INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE koi_collection ADD children_counter INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE koi_wishlist ADD wishes_counter INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE koi_wishlist ADD children_counter INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->skipIf(true, 'Always move forward.');
    }
}
