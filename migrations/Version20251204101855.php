<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204101855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE car (id INT AUTO_INCREMENT NOT NULL, brand VARCHAR(50) NOT NULL, model VARCHAR(50) NOT NULL, color VARCHAR(30) DEFAULT NULL, license_plate VARCHAR(20) NOT NULL, seats INT NOT NULL, year INT DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_773DE69D7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ride (id INT AUTO_INCREMENT NOT NULL, departure_date DATE NOT NULL, departure_time TIME NOT NULL, departure_place VARCHAR(255) NOT NULL, arrival_date DATE DEFAULT NULL, arrival_time TIME NOT NULL, arrival_place VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, available_seats INT NOT NULL, price_per_seat NUMERIC(10, 2) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, driver_id INT NOT NULL, car_id INT NOT NULL, INDEX IDX_9B3D7CD0C3423909 (driver_id), INDEX IDX_9B3D7CD0C3C6F69F (car_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ride_booking (id INT AUTO_INCREMENT NOT NULL, seats_booked INT NOT NULL, status VARCHAR(255) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, ride_id INT NOT NULL, passenger_id INT NOT NULL, INDEX IDX_A7A3C292302A8A70 (ride_id), INDEX IDX_A7A3C2924502E565 (passenger_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE car ADD CONSTRAINT FK_773DE69D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0C3423909 FOREIGN KEY (driver_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0C3C6F69F FOREIGN KEY (car_id) REFERENCES car (id)');
        $this->addSql('ALTER TABLE ride_booking ADD CONSTRAINT FK_A7A3C292302A8A70 FOREIGN KEY (ride_id) REFERENCES ride (id)');
        $this->addSql('ALTER TABLE ride_booking ADD CONSTRAINT FK_A7A3C2924502E565 FOREIGN KEY (passenger_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car DROP FOREIGN KEY FK_773DE69D7E3C61F9');
        $this->addSql('ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0C3423909');
        $this->addSql('ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0C3C6F69F');
        $this->addSql('ALTER TABLE ride_booking DROP FOREIGN KEY FK_A7A3C292302A8A70');
        $this->addSql('ALTER TABLE ride_booking DROP FOREIGN KEY FK_A7A3C2924502E565');
        $this->addSql('DROP TABLE car');
        $this->addSql('DROP TABLE ride');
        $this->addSql('DROP TABLE ride_booking');
    }
}
