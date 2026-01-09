<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        // Helper untuk default timestamp
        $currentTimestamp = 'CURRENT_TIMESTAMP';

        $this->execute("CREATE TYPE action_enum AS ENUM ('INSERT', 'UPDATE', 'DELETE')");
        $this->execute("CREATE TYPE status_enum AS ENUM ('active', 'inactive')");

        // -- TB_USER_ROLE --
        $table = $this->table('tb_user_role', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('label', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('role', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_USER --
        $table = $this->table('tb_user', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('phone', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('username', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('password', 'text', ['null' => true])
            ->addColumn('status', 'string', ['null' => true])
            ->addColumn('last_login', 'timestamp', ['null' => true])
            ->addColumn('id_user_role', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();
        $this->execute('ALTER TABLE tb_user ALTER COLUMN status TYPE status_enum USING status::status_enum');

        // -- TB_PICTURE --
        $table = $this->table('tb_picture', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_cloud', 'text', ['null' => true])
            ->addColumn('original', 'text', ['null' => true])
            ->addColumn('thumbnail', 'text', ['null' => true])
            ->addColumn('caption', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_OPTION --
        $table = $this->table('tb_option', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('value', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_LOG --
        $table = $this->table('tb_log', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_user', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('id_record', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('table_name', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('action', 'string', ['null' => true])
            ->addColumn('changes', 'text', ['null' => true])
            ->addColumn('ip_address', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('user_agent', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();
        $this->execute('ALTER TABLE tb_log ALTER COLUMN action TYPE action_enum USING action::action_enum');

        // -- TB_SEO_META --
        $table = $this->table('tb_seo_meta', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_parent', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('type', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('meta_title', 'text', ['null' => true])
            ->addColumn('meta_description', 'text', ['null' => true])
            ->addColumn('meta_robots', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('seo_keyphrase', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('seo_analysis', 'integer', ['null' => true])
            ->addColumn('seo_readability', 'integer', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_CONTENT_GALLERY --
        $table = $this->table('tb_content_gallery', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_picture', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_CONTENT_CONTACT --
        $table = $this->table('tb_content_contact', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('value', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_CONTENT_FAQ --
        $table = $this->table('tb_content_faq', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('title', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('sort', 'integer', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_CONTENT_TEAM --
        $table = $this->table('tb_content_team', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_picture', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('title', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('link', 'json', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();

        // -- TB_ARTICLE --
        $table = $this->table('tb_article', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_picture', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('id_category', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('status', 'string', ['null' => true])
            ->addColumn('slug', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('title', 'text', ['null' => true])
            ->addColumn('content', 'text', ['null' => true])
            ->addColumn('excerpt', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('author', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('publish', 'timestamp', ['null' => true])
            ->addColumn('featured', 'json', ['null' => true])
            ->addColumn('read_time', 'integer', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();
        $this->execute('ALTER TABLE tb_article ALTER COLUMN status TYPE status_enum USING status::status_enum');

        // -- TB_ARTICLE_CATEGORY --
        $table = $this->table('tb_article_category', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_picture', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('id_parent', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('status', 'string', ['null' => true])
            ->addColumn('slug', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('title', 'text', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();
        $this->execute('ALTER TABLE tb_article_category ALTER COLUMN status TYPE status_enum USING status::status_enum');

        // -- TB_CRONHOOKS --
        $table = $this->table('tb_cronhooks', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('id_parent', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('type', 'string', ['limit' => 250, 'null' => true])
            ->addColumn('id_cronhooks', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => $currentTimestamp])
            ->addColumn('updated_at', 'timestamp', ['default' => $currentTimestamp])
            ->create();
    }

    public function down(): void
    {
        $this->table('tb_cronhooks')->drop()->save();
        $this->table('tb_article_category')->drop()->save();
        $this->table('tb_article')->drop()->save();
        $this->table('tb_content_team')->drop()->save();
        $this->table('tb_content_faq')->drop()->save();
        $this->table('tb_content_contact')->drop()->save();
        $this->table('tb_content_gallery')->drop()->save();
        $this->table('tb_seo_meta')->drop()->save();
        $this->table('tb_log')->drop()->save();
        $this->table('tb_option')->drop()->save();
        $this->table('tb_picture')->drop()->save();
        $this->table('tb_user')->drop()->save();
        $this->table('tb_user_role')->drop()->save();

        $this->execute("DROP TYPE IF EXISTS status_enum");
        $this->execute("DROP TYPE IF EXISTS action_enum");
    }
}
