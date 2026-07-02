<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702120202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content_likes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, content_id INTEGER NOT NULL, CONSTRAINT FK_A2091AAFA76ED395 FOREIGN KEY (user_id) REFERENCES user_data (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A2091AAF84A0A3ED FOREIGN KEY (content_id) REFERENCES content (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A2091AAFA76ED395 ON content_likes (user_id)');
        $this->addSql('CREATE INDEX IDX_A2091AAF84A0A3ED ON content_likes (content_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__content AS SELECT id, title, type, description, url, release_date, status, cover_file_id, created_by, created_at, updated_at, likes_count, dislikes_count, views_count, trending_score FROM content');
        $this->addSql('DROP TABLE content');
        $this->addSql('CREATE TABLE content (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title CLOB NOT NULL, type CLOB NOT NULL, description CLOB DEFAULT NULL, url CLOB DEFAULT NULL, release_date CLOB DEFAULT NULL, status CLOB NOT NULL, cover_file_id CLOB DEFAULT NULL, created_by INTEGER NOT NULL, created_at CLOB DEFAULT \'CURRENT_TIMESTAMP\', updated_at CLOB DEFAULT \'CURRENT_TIMESTAMP\', likes_count INTEGER DEFAULT 0 NOT NULL, dislikes_count INTEGER DEFAULT 0 NOT NULL, views_count INTEGER DEFAULT 0 NOT NULL, trending_score DOUBLE PRECISION DEFAULT 0 NOT NULL, CONSTRAINT FK_FEC530A9DE12AB56 FOREIGN KEY (created_by) REFERENCES user_data (user_id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO content (id, title, type, description, url, release_date, status, cover_file_id, created_by, created_at, updated_at, likes_count, dislikes_count, views_count, trending_score) SELECT id, title, type, description, url, release_date, status, cover_file_id, created_by, created_at, updated_at, likes_count, dislikes_count, views_count, trending_score FROM __temp__content');
        $this->addSql('DROP TABLE __temp__content');
        $this->addSql('CREATE INDEX IDX_FEC530A9DE12AB56 ON content (created_by)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_data AS SELECT user_id, user_state, current_panel, current_page, role FROM user_data');
        $this->addSql('DROP TABLE user_data');
        $this->addSql('CREATE TABLE user_data (user_id INTEGER NOT NULL, user_state CLOB DEFAULT NULL, current_panel INTEGER DEFAULT NULL, current_page CLOB DEFAULT NULL, role CLOB DEFAULT NULL, PRIMARY KEY (user_id))');
        $this->addSql('INSERT INTO user_data (user_id, user_state, current_panel, current_page, role) SELECT user_id, user_state, current_panel, current_page, role FROM __temp__user_data');
        $this->addSql('DROP TABLE __temp__user_data');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_states AS SELECT user_id, state_key, state_value FROM user_states');
        $this->addSql('DROP TABLE user_states');
        $this->addSql('CREATE TABLE user_states (user_id INTEGER NOT NULL, state_key CLOB NOT NULL, state_value CLOB DEFAULT NULL, PRIMARY KEY (user_id, state_key), CONSTRAINT FK_54906C70A76ED395 FOREIGN KEY (user_id) REFERENCES user_data (user_id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_states (user_id, state_key, state_value) SELECT user_id, state_key, state_value FROM __temp__user_states');
        $this->addSql('DROP TABLE __temp__user_states');
        $this->addSql('CREATE INDEX IDX_54906C70A76ED395 ON user_states (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE content_likes');
        $this->addSql('CREATE TEMPORARY TABLE __temp__content AS SELECT id, title, type, description, url, release_date, status, cover_file_id, created_at, updated_at, likes_count, dislikes_count, views_count, trending_score, created_by FROM content');
        $this->addSql('DROP TABLE content');
        $this->addSql('CREATE TABLE content (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title CLOB NOT NULL, type CLOB NOT NULL, description CLOB DEFAULT NULL, url CLOB DEFAULT NULL, release_date CLOB DEFAULT NULL, status CLOB NOT NULL, cover_file_id CLOB DEFAULT NULL, created_at CLOB DEFAULT \'CURRENT_TIMESTAMP\', updated_at CLOB DEFAULT \'CURRENT_TIMESTAMP\', likes_count INTEGER DEFAULT 0 NOT NULL, dislikes_count INTEGER DEFAULT 0 NOT NULL, views_count INTEGER DEFAULT 0 NOT NULL, trending_score DOUBLE PRECISION DEFAULT \'0\' NOT NULL, created_by INTEGER NOT NULL, CONSTRAINT FK_FEC530A9DE12AB56 FOREIGN KEY (created_by) REFERENCES user_data (user_id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO content (id, title, type, description, url, release_date, status, cover_file_id, created_at, updated_at, likes_count, dislikes_count, views_count, trending_score, created_by) SELECT id, title, type, description, url, release_date, status, cover_file_id, created_at, updated_at, likes_count, dislikes_count, views_count, trending_score, created_by FROM __temp__content');
        $this->addSql('DROP TABLE __temp__content');
        $this->addSql('CREATE INDEX IDX_FEC530A9DE12AB56 ON content (created_by)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_data AS SELECT user_id, user_state, current_panel, current_page, role FROM user_data');
        $this->addSql('DROP TABLE user_data');
        $this->addSql('CREATE TABLE user_data (user_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_state CLOB DEFAULT NULL, current_panel INTEGER DEFAULT NULL, current_page CLOB DEFAULT NULL, role CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO user_data (user_id, user_state, current_panel, current_page, role) SELECT user_id, user_state, current_panel, current_page, role FROM __temp__user_data');
        $this->addSql('DROP TABLE __temp__user_data');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_states AS SELECT state_key, state_value, user_id FROM user_states');
        $this->addSql('DROP TABLE user_states');
        $this->addSql('CREATE TABLE user_states (state_key CLOB NOT NULL, state_value CLOB DEFAULT NULL, user_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, CONSTRAINT FK_54906C70A76ED395 FOREIGN KEY (user_id) REFERENCES user_data (user_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_states (state_key, state_value, user_id) SELECT state_key, state_value, user_id FROM __temp__user_states');
        $this->addSql('DROP TABLE __temp__user_states');
        $this->addSql('CREATE INDEX IDX_54906C70A76ED395 ON user_states (user_id)');
    }
}
