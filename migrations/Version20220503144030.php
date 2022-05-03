<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220503144030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog_categories (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, description JSON NOT NULL, is_active TINYINT(1) NOT NULL, sorting INT UNSIGNED NOT NULL, UNIQUE INDEX UNIQ_DC3564815E237E06 (name), UNIQUE INDEX UNIQ_DC356481E16C6B94 (alias), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE blog_comments (id INT UNSIGNED AUTO_INCREMENT NOT NULL, author_id INT UNSIGNED DEFAULT NULL, post_id INT UNSIGNED DEFAULT NULL, parent_comment_id INT UNSIGNED DEFAULT NULL, content JSON NOT NULL, is_deleted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_2BC3B20DF675F31B (author_id), INDEX IDX_2BC3B20D4B89032C (post_id), INDEX IDX_2BC3B20DBF2AF943 (parent_comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE blog_posts (id INT UNSIGNED AUTO_INCREMENT NOT NULL, author_id INT UNSIGNED DEFAULT NULL, title VARCHAR(255) NOT NULL, alias VARCHAR(255) NOT NULL, description JSON NOT NULL, content JSON NOT NULL, image VARCHAR(255) NOT NULL, is_published TINYINT(1) NOT NULL, is_deleted TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, published_at DATETIME NOT NULL, edited_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_78B2F932F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE blog_posts_categories (post_id INT UNSIGNED NOT NULL, category_id INT UNSIGNED NOT NULL, INDEX IDX_DCC081A04B89032C (post_id), INDEX IDX_DCC081A012469DE2 (category_id), PRIMARY KEY(post_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, alias VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, is_email_hidden TINYINT(1) NOT NULL, phone VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL, contacts JSON NOT NULL, birthdate DATE DEFAULT NULL, avatar VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, biography JSON NOT NULL, first_useragent VARCHAR(255) NOT NULL, first_ip VARCHAR(255) NOT NULL, is_banned TINYINT(1) NOT NULL, is_communication_banned TINYINT(1) NOT NULL, is_deleted TINYINT(1) NOT NULL, is_erased TINYINT(1) NOT NULL, is_allowed_adv_notifications TINYINT(1) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', sorting INT NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, erased_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E16C6B94 (alias), UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users_refresh_tokens (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, token VARCHAR(255) NOT NULL, useragent VARCHAR(255) NOT NULL, ip VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, is_used TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_E5147E545F37A13B (token), INDEX IDX_E5147E54A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE blog_comments ADD CONSTRAINT FK_2BC3B20DF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_comments ADD CONSTRAINT FK_2BC3B20D4B89032C FOREIGN KEY (post_id) REFERENCES blog_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_comments ADD CONSTRAINT FK_2BC3B20DBF2AF943 FOREIGN KEY (parent_comment_id) REFERENCES blog_comments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_posts ADD CONSTRAINT FK_78B2F932F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE blog_posts_categories ADD CONSTRAINT FK_DCC081A04B89032C FOREIGN KEY (post_id) REFERENCES blog_posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_posts_categories ADD CONSTRAINT FK_DCC081A012469DE2 FOREIGN KEY (category_id) REFERENCES blog_categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_refresh_tokens ADD CONSTRAINT FK_E5147E54A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_posts_categories DROP FOREIGN KEY FK_DCC081A012469DE2');
        $this->addSql('ALTER TABLE blog_comments DROP FOREIGN KEY FK_2BC3B20DBF2AF943');
        $this->addSql('ALTER TABLE blog_comments DROP FOREIGN KEY FK_2BC3B20D4B89032C');
        $this->addSql('ALTER TABLE blog_posts_categories DROP FOREIGN KEY FK_DCC081A04B89032C');
        $this->addSql('ALTER TABLE blog_comments DROP FOREIGN KEY FK_2BC3B20DF675F31B');
        $this->addSql('ALTER TABLE blog_posts DROP FOREIGN KEY FK_78B2F932F675F31B');
        $this->addSql('ALTER TABLE users_refresh_tokens DROP FOREIGN KEY FK_E5147E54A76ED395');
        $this->addSql('DROP TABLE blog_categories');
        $this->addSql('DROP TABLE blog_comments');
        $this->addSql('DROP TABLE blog_posts');
        $this->addSql('DROP TABLE blog_posts_categories');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_refresh_tokens');
    }
}
