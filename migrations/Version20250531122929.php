<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250531122929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP reponses, DROP reponses1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse ADD user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5FB6DEC7A76ED395 ON reponse (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD reponses LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', ADD reponses1 LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_5FB6DEC7A76ED395 ON reponse
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse DROP user_id
        SQL);
    }
}
