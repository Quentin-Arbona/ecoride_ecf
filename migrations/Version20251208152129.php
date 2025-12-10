<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208152129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `employee` (id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, rating SMALLINT NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, author_id INT NOT NULL, driver_id INT NOT NULL, ride_id INT NOT NULL, validated_by_id INT DEFAULT NULL, INDEX IDX_794381C6F675F31B (author_id), INDEX IDX_794381C6C3423909 (driver_id), INDEX IDX_794381C6302A8A70 (ride_id), INDEX IDX_794381C6C69DE5E5 (validated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `employee` ADD CONSTRAINT FK_5D9F75A1BF396750 FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6C3423909 FOREIGN KEY (driver_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `employee` DROP FOREIGN KEY FK_5D9F75A1BF396750');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6F675F31B');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6C3423909');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6302A8A70');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6C69DE5E5');
        $this->addSql('DROP TABLE `employee`');
        $this->addSql('DROP TABLE review');
    }
}
