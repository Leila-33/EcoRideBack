<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904185219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF060BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF085C0B3BE FOREIGN KEY (chauffeur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8F91ABF060BB6FE6 ON avis (auteur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8F91ABF085C0B3BE ON avis (chauffeur_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP id_chauffeur
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF060BB6FE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF085C0B3BE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8F91ABF060BB6FE6 ON avis
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8F91ABF085C0B3BE ON avis
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD id_chauffeur INT NOT NULL
        SQL);
    }
}
