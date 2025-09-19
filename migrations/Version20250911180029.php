<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250911180029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP note_chauffeur, CHANGE nbPlaces nb_places INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_5A6F91CEA4D60759 ON marque (libelle)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE operation CHANGE operation montant NUMERIC(5, 2) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE date_naissance date_naissance DATE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture CHANGE nbPlaces nb_places INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD note_chauffeur NUMERIC(2, 1) DEFAULT NULL, CHANGE nb_places nbPlaces INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_5A6F91CEA4D60759 ON marque
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE operation CHANGE montant operation NUMERIC(5, 2) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE date_naissance date_naissance DATE DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture CHANGE nb_places nbPlaces INT NOT NULL
        SQL);
    }
}
