<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813191646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table with a unique index on email (not only a primary key).';
    }

    public function up(Schema $schema): void
    {
        // Important: uniq_users_email is a UNIQUE INDEX, separate from PRIMARY KEY (id).
        // Note: e-mail uniqueness is enforced here even if two requests insert at the same time.
        // Nota bene: do not replace this index with a PHP uniqueness check.
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, registered_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, verification_token VARCHAR(64) DEFAULT NULL, UNIQUE INDEX uniq_users_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
