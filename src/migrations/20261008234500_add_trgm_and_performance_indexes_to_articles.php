<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTrgmAndPerformanceIndexesToArticles extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        // 1. Ensure pg_trgm extension is active for trigram similarity
        $this->execute('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        // 2. Add GIN index on article title for fast similarity search
        $this->execute('CREATE INDEX IF NOT EXISTS idx_tb_article_title_trgm ON tb_article USING gin (title gin_trgm_ops);');

        // 3. Add composite B-Tree indexes for frequent queries on tb_article
        $this->execute('CREATE INDEX IF NOT EXISTS idx_tb_article_site_status_slug ON tb_article (site, status, slug);');
        $this->execute('CREATE INDEX IF NOT EXISTS idx_tb_article_site_status_publish ON tb_article (site, status, publish);');

        // 4. Add composite B-Tree index for category lookup on tb_article_category
        $this->execute('CREATE INDEX IF NOT EXISTS idx_tb_article_cat_site_status_slug ON tb_article_category (site, status, slug);');
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        $this->execute('DROP INDEX IF EXISTS idx_tb_article_cat_site_status_slug;');
        $this->execute('DROP INDEX IF EXISTS idx_tb_article_site_status_publish;');
        $this->execute('DROP INDEX IF EXISTS idx_tb_article_site_status_slug;');
        $this->execute('DROP INDEX IF EXISTS idx_tb_article_title_trgm;');
    }
}
