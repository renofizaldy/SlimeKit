<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateArticleAgentTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('tb_article_agent', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'char', ['limit' => 36])
            ->addColumn('id_parent', 'biginteger', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('user_input_topic', 'text', ['null' => true, 'default' => null])
            ->addColumn('brainstorming_options', 'json', ['null' => true, 'default' => null])
            ->addColumn('selected_option_data', 'json', ['null' => true, 'default' => null])
            ->addColumn('research_raw_tavily', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null])
            ->addColumn('research_summary', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null])
            ->addColumn('generated_article_content', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['created_at'])
            ->create();
    }
}
