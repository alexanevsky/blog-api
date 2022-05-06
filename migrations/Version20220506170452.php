<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220506170452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_comments CHANGE edited_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at trashed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_posts CHANGE edited_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at trashed_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD created_by_id INT UNSIGNED DEFAULT NULL, CHANGE edited_at updated_at DATETIME DEFAULT NULL, CHANGE deleted_at trashed_at DATETIME DEFAULT NULL, CHANGE birthdate birthdate VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9B03A8386 ON users (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_comments CHANGE updated_at edited_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_posts CHANGE updated_at edited_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9B03A8386');
        $this->addSql('DROP INDEX IDX_1483A5E9B03A8386 ON users');
        $this->addSql('ALTER TABLE users CHANGE updated_at edited_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, DROP created_by_id, CHANGE birthdate birthdate DATE DEFAULT NULL');
    }
}
