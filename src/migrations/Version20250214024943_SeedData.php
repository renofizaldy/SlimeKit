<?php

declare(strict_types=1);

namespace MyMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214024943_SeedData extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO tb_user_role (label, role)
            VALUES
                (
                    'Admin',
                    '["dashboard:view","dashboard:crud","article:view","article:crud","content_gallery:view","content_gallery:crud","content_faq:view","content_faq:crud","content_contact:view","content_contact:crud","setting_user:view","setting_user:crud","setting_option:view","setting_option:crud"]'::json
                ),
                (
                    'Moderator',
                    '["dashboard:view","article:view","article:crud","content_gallery:view","content_gallery:crud","content_faq:view","content_faq:crud","content_contact:view","content_contact:crud"]'::json
                );
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO tb_user (
                name,
                phone,
                email,
                username,
                password,
                status,
                id_user_role,
                created_at
            )
            VALUES (
                'Demo',
                '081234567890',
                'demo@gmail.com',
                'demo',
                '\$2y\$10\$TTQNEVnH1ZN33uBxNqw69.TQl1Mwmk7vca3fYSpkGEr/fPIcpZ3vi',
                'active',
                1,
                '2025-02-05 08:10:49.22271'
            );
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM tb_user WHERE username = 'demo';");
        $this->addSql("DELETE FROM tb_user_role WHERE id IN (1, 2);");
    }
}
