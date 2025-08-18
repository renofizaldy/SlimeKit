<?php
declare(strict_types=1);

namespace MyMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240513170203_CreateTables extends AbstractMigration
{
  public function getDescription(): string
  {
    return '';
  }

  public function up(Schema $schema): void
  {
    $this->addSql("CREATE TYPE action_enum AS ENUM ('INSERT', 'UPDATE', 'DELETE')");
    $this->addSql("CREATE TYPE status_enum AS ENUM ('active', 'inactive')");

    $this->addSql("CREATE TABLE tb_user_role (
      id BIGSERIAL PRIMARY KEY,
      label VARCHAR(255),
      role JSON,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_user (
      id BIGSERIAL PRIMARY KEY,
      name VARCHAR(250),
      phone VARCHAR(250),
      email VARCHAR(250),
      username VARCHAR(250),
      password TEXT,
      status status_enum,
      last_login TIMESTAMP,
      id_user_role BIGINT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_picture (
      id BIGSERIAL PRIMARY KEY,
      id_cloud TEXT,
      original TEXT,
      thumbnail TEXT,
      caption TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_option (
      id BIGSERIAL PRIMARY KEY,
      name VARCHAR(250),
      value VARCHAR(250),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_log (
      id BIGSERIAL PRIMARY KEY,
      id_user BIGINT,
      id_record BIGINT,
      table_name VARCHAR(255),
      action action_enum,
      changes TEXT,
      ip_address VARCHAR(255),
      user_agent TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_seo_meta (
      id BIGSERIAL PRIMARY KEY,
      id_parent BIGINT,
      type VARCHAR(250),
      meta_title TEXT,
      meta_description TEXT,
      meta_robots VARCHAR(50),
      seo_keyphrase VARCHAR(250),
      seo_analysis INT,
      seo_readability INT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_content_gallery (
      id BIGSERIAL PRIMARY KEY,
      id_picture BIGINT,
      name VARCHAR(250),
      description TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_content_contact (
      id BIGSERIAL PRIMARY KEY,
      name VARCHAR(250),
      value TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_content_faq (
      id BIGSERIAL PRIMARY KEY,
      title VARCHAR(250),
      description TEXT,
      sort INT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_content_team (
      id BIGSERIAL PRIMARY KEY,
      id_picture BIGINT,
      name VARCHAR(250),
      title VARCHAR(250),
      link JSON,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_article (
      id BIGSERIAL PRIMARY KEY,
      id_picture BIGINT,
      id_category BIGINT,
      status status_enum,
      slug VARCHAR(250),
      title TEXT,
      content TEXT,
      excerpt VARCHAR(250),
      author VARCHAR(50),
      publish TIMESTAMP,
      featured TEXT[],
      read_time INTEGER,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_article_category (
      id BIGSERIAL PRIMARY KEY,
      id_picture BIGINT,
      id_parent BIGINT,
      status status_enum,
      slug VARCHAR(250),
      title TEXT,
      description TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $this->addSql("CREATE TABLE tb_cronhooks (
      id BIGSERIAL PRIMARY KEY,
      id_parent BIGINT,
      type VARCHAR(250),
      id_cronhooks TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
  }

  public function down(Schema $schema): void
  {
    // Drop tables
    $this->addSql('DROP TABLE tb_user_role');
    $this->addSql('DROP TABLE tb_user');
    $this->addSql('DROP TABLE tb_picture');
    $this->addSql('DROP TABLE tb_option');
    $this->addSql('DROP TABLE tb_log');
    $this->addSql('DROP TABLE tb_content_gallery');
    $this->addSql('DROP TABLE tb_content_contact');
    $this->addSql('DROP TABLE tb_content_faq');
    $this->addSql('DROP TABLE tb_content_team');
    $this->addSql('DROP TABLE tb_article');
    $this->addSql('DROP TABLE tb_article_category');
  }
}
