<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261008120014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen orders.coupon_code from VARCHAR(3) to VARCHAR(50)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ALTER coupon_code TYPE VARCHAR(50)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ALTER coupon_code TYPE VARCHAR(3)');
    }
}
