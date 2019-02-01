<?php

namespace homm\hommsitemap\migrations;

use Craft;
use craft\db\Migration;

/**
 * m171217_220906_c_crawler_visit_table migration.
 */
class m171217_220906_c_crawler_visit_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->tableExists('{{%homm_sitemap_crawler_visits}}')) {

            $this->createTable(
                '{{%homm_sitemap_crawler_visits}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'name' => $this->string(150)->notNull()->defaultValue(''),
                ]
            );
            return true;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%homm_sitemap_crawler_visits}}');
        return true;
    }
}
