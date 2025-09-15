<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915154250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE actividad (id_actividad INT AUTO_INCREMENT NOT NULL, id_programa INT NOT NULL, id_tipo_actividad INT NOT NULL, actividad VARCHAR(200) NOT NULL, descripcion LONGTEXT DEFAULT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_8DF2BD06522BF898 (id_programa), INDEX IDX_8DF2BD0652FABF4E (id_tipo_actividad), PRIMARY KEY(id_actividad)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comision (id_comision INT AUTO_INCREMENT NOT NULL, id_actividad INT NOT NULL, comision VARCHAR(200) DEFAULT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_1013896FDC70121 (id_actividad), PRIMARY KEY(id_comision)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE encuentro (id_encuentro INT AUTO_INCREMENT NOT NULL, id_comision INT NOT NULL, id_modalidad_encuentro INT NOT NULL, encuentro VARCHAR(200) DEFAULT NULL, fecha_hora_inicio DATETIME DEFAULT NULL, fecha_hora_fin DATETIME DEFAULT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_CDFA77FA40C865FA (id_comision), INDEX IDX_CDFA77FA9D2908A2 (id_modalidad_encuentro), PRIMARY KEY(id_encuentro)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE programa (id_programa INT AUTO_INCREMENT NOT NULL, programa VARCHAR(100) DEFAULT NULL, descripcion LONGTEXT DEFAULT NULL, vigente TINYINT(1) DEFAULT 1, activo TINYINT(1) DEFAULT 1 NOT NULL, PRIMARY KEY(id_programa)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD06522BF898 FOREIGN KEY (id_programa) REFERENCES programa (id_programa)');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD0652FABF4E FOREIGN KEY (id_tipo_actividad) REFERENCES tipo_actividad (id_tipo_actividad)');
        $this->addSql('ALTER TABLE comision ADD CONSTRAINT FK_1013896FDC70121 FOREIGN KEY (id_actividad) REFERENCES actividad (id_actividad)');
        $this->addSql('ALTER TABLE encuentro ADD CONSTRAINT FK_CDFA77FA40C865FA FOREIGN KEY (id_comision) REFERENCES comision (id_comision)');
        $this->addSql('ALTER TABLE encuentro ADD CONSTRAINT FK_CDFA77FA9D2908A2 FOREIGN KEY (id_modalidad_encuentro) REFERENCES modalidad_encuentro (id_modalidad_encuentro)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD06522BF898');
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD0652FABF4E');
        $this->addSql('ALTER TABLE comision DROP FOREIGN KEY FK_1013896FDC70121');
        $this->addSql('ALTER TABLE encuentro DROP FOREIGN KEY FK_CDFA77FA40C865FA');
        $this->addSql('ALTER TABLE encuentro DROP FOREIGN KEY FK_CDFA77FA9D2908A2');
        $this->addSql('DROP TABLE actividad');
        $this->addSql('DROP TABLE comision');
        $this->addSql('DROP TABLE encuentro');
        $this->addSql('DROP TABLE programa');
    }
}
