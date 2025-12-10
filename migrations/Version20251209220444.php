<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209220444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review ADD booking_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C63301C60 FOREIGN KEY (booking_id) REFERENCES ride_booking (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_794381C63301C60 ON review (booking_id)');
        $this->addSql('ALTER TABLE ride_booking ADD resolution_notes LONGTEXT DEFAULT NULL, ADD resolved_at DATETIME DEFAULT NULL, ADD resolved_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ride_booking ADD CONSTRAINT FK_A7A3C2926713A32B FOREIGN KEY (resolved_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_A7A3C2926713A32B ON ride_booking (resolved_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C63301C60');
        $this->addSql('DROP INDEX UNIQ_794381C63301C60 ON review');
        $this->addSql('ALTER TABLE review DROP booking_id');
        $this->addSql('ALTER TABLE ride_booking DROP FOREIGN KEY FK_A7A3C2926713A32B');
        $this->addSql('DROP INDEX IDX_A7A3C2926713A32B ON ride_booking');
        $this->addSql('ALTER TABLE ride_booking DROP resolution_notes, DROP resolved_at, DROP resolved_by_id');
    }
}
