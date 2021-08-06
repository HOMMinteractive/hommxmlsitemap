<?php
/**
 * HOMM XML Sitemap plugin for Craft CMS 3.x
 *
 * Craft 3 plugin that provides an easy way to enable and manage a xml sitemap for search engines like Google
 *
 * @link      https://github.com/HOMMinteractive/hommxmlsitemap
 * @copyright Copyright (c) 2017 Johan Zandstra
 */

namespace homm\hommxmlsitemap\services;

use Craft;
use craft\base\Component;
use craft\db\Table;
use craft\events\ConfigEvent;
use craft\events\RebuildConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use homm\hommxmlsitemap\records\SitemapEntry;

/**
 * SitemapService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Johan Zandstra
 * @package   Sitemap
 * @since     1.0.0
 */
class SitemapService extends Component
{
    /**
     * Key for the project config
     */
    const PROJECT_CONFIG_KEY = 'homm_sitemap_entries';

    // Public Methods
    // =========================================================================

    /**
     * Save a new entry to the project config
     *
     * @param \homm\hommxmlsitemap\records\SitemapEntry $record
     *
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\web\ServerErrorHttpException
     * @throws \yii\base\ErrorException
     */
    public function saveEntry(SitemapEntry $record): bool
    {
        // is it a new one?
        $isNew = empty($record->id);
        if ($isNew) {
            $record->uid = StringHelper::UUID();
        } else {
            if (!$record->uid) {
                $record->uid = Db::uidById(SitemapEntry::tableName(), $record->id);
            }
        }

        if (!$record->validate()) {
            return false;
        }
        $path = self::PROJECT_CONFIG_KEY . ".{$record->uid}";

        // set it in the config
        Craft::$app->getProjectConfig()->set(
            $path,
            [
                'linkId'     => $this->getUidById($record->type, $record->linkId),
                'type'       => $record->type,
                'priority'   => $record->priority,
                'changefreq' => $record->changefreq,
            ]
        );

        if ($isNew) {
            $record->id = Db::idByUid(SitemapEntry::tableName(), $record->uid);
        }

        return true;
    }

    /**
     * Delete an entry from project config
     *
     * @param \homm\hommxmlsitemap\records\SitemapEntry $record
     */
    public function deleteEntry(SitemapEntry $record)
    {
        $path = self::PROJECT_CONFIG_KEY . ".{$record->uid}";
        Craft::$app->projectConfig->remove($path);
    }

    /**
     * handleChangedSiteMapEntry
     *
     * @param \craft\events\ConfigEvent $event
     */
    public function handleChangedSiteMapEntry(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $id = Db::idByUid(SitemapEntry::tableName(), $uid);

        if ($id === null) {
            // new one
            $record = new SitemapEntry();
        } else {
            // update an existing one
            $record = SitemapEntry::findOne((int) $id);
        }

        if (!empty($event->newValue['linkId'])) {
            $record->uid = $uid;
            $record->linkId = $this->getIdByUid($event->newValue['type'], $event->newValue['linkId']);
            $record->type = $event->newValue['type'];
            $record->priority = $event->newValue['priority'];
            $record->changefreq = $event->newValue['changefreq'];
            $record->save();
        }
    }

    /**
     * handleDeletedSiteMapEntry
     *
     * @param \craft\events\ConfigEvent $event
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function handleDeletedSiteMapEntry(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        // grab the record
        $record = SitemapEntry::find()->where(['uid' => $uid])->one();
        if ($record === null) {
            return;
        }

        // delete it
        $record->delete();
    }

    /**
     * rebuildProjectConfig
     *
     * @param \craft\events\RebuildConfigEvent $e
     */
    public function rebuildProjectConfig(RebuildConfigEvent $e)
    {
        /** @var SitemapEntry[] $records */
        $records = SitemapEntry::find()->all();
        $e->config[self::PROJECT_CONFIG_KEY] = [];
        foreach ($records as $record) {
            $e->config[self::PROJECT_CONFIG_KEY][$record->uid] = [
                'linkId' => $this->getUidById($record->type, $record->linkId),
                'type' => $record->type,
                'priority' => $record->priority,
                'changefreq' => $record->changefreq,
            ];
        }
    }

    /**
     * Get the UID by the record ID
     *
     * @param string $type
     * @param int $linkId
     * @return string|null
     * @throws \Exception
     */
    public function getUidById(string $type, int $linkId)
    {
        switch ($type) {
            case 'section':
                return Db::uidById(Table::SECTIONS, $linkId);
            case 'category':
                return Db::uidById(Table::CATEGORYGROUPS, $linkId);
            case 'productType':
                $commerce = Craft::$app->plugins->getPlugin('commerce');
                if ($commerce && $commerce->isInstalled) {
                    return Db::uidById(\craft\commerce\db\Table::PRODUCTTYPES, $linkId);
                }
                throw new \Exception('The product type is only supported if craft commerce is installed.');
            default:
                throw new \Exception('Sitemap record type is unknown.');
        }
    }

    /**
     * Get the ID by the record UID
     *
     * @param string $type
     * @param string $uid
     * @return string|null
     * @throws \Exception
     */
    public function getIdByUid(string $type, string $uid)
    {
        switch ($type) {
            case 'section':
                return Db::idByUid(Table::SECTIONS, $uid);
            case 'category':
                return Db::idByUid(Table::CATEGORYGROUPS, $uid);
            case 'productType':
                $commerce = Craft::$app->plugins->getPlugin('commerce');
                if ($commerce && $commerce->isInstalled) {
                    return Db::idByUid(\craft\commerce\db\Table::PRODUCTTYPES, $uid);
                }
                throw new \Exception('The product type is only supported if craft commerce is installed.');
            default:
                throw new \Exception('Sitemap record type is unknown.');
        }
    }
}
