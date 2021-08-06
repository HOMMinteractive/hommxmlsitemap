<?php
/**
 * HOMM XML Sitemap plugin for Craft CMS 3.x
 *
 * Craft 3 plugin that provides an easy way to enable and manage a xml sitemap for search engines like Google
 *
 * @link      https://github.com/HOMMinteractive/hommxmlsitemap
 * @copyright Copyright (c) 2017 Johan Zandstra
 */

namespace homm\hommxmlsitemap\controllers;

use homm\hommxmlsitemap\records\SitemapEntry;
use homm\hommxmlsitemap\HOMMXMLSitemap;

use Craft;
use craft\db\Query;
use craft\web\Controller;
use craft\commerce\db\Table as CommerceTable;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Johan Zandstra
 * @package   Sitemap
 * @since     1.0.0
 */
class SettingsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = false;

    private function _createEntrySectionQuery(): Query
    {
        return (new Query())
            ->select([
                'sections.id',
                'sections.structureId',
                'sections.name',
                'sections.handle',
                'sections.type',
                'count(DISTINCT entries.id) entryCount',
                'count(DISTINCT elements.id) elementCount',
                'sitemap_entries.id sitemapEntryId',
                'sitemap_entries.changefreq changefreq',
                'sitemap_entries.priority priority',
            ])
            ->from(['{{%sections}} sections'])
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
            ->innerJoin('{{%sections_sites}} sections_sites', '[[sections_sites.sectionId]] = [[sections.id]] AND [[sections_sites.hasUrls]] = 1')
            ->leftJoin('{{%entries}} entries', '[[sections.id]] = [[entries.sectionId]]')
            ->leftJoin('{{%elements}} elements', '[[entries.id]] = [[elements.id]] AND [[elements.enabled]] = 1')
            ->leftJoin('{{%homm_sitemap_entries}} sitemap_entries', '[[sections.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "section"')

            ->groupBy(['sections.id'])
            ->where(['sections.dateDeleted' => null])
            ->orderBy(['type' => SORT_ASC],['name' => SORT_ASC]);
    }

    private function _createCategoryQuery(): Query
    {
        return (new Query())
            ->select([
                'categorygroups.id',
                'categorygroups.name',
                'count(DISTINCT entries.id) entryCount',
                'count(DISTINCT elements.id) elementCount',
                'sitemap_entries.id sitemapEntryId',
                'sitemap_entries.changefreq changefreq',
                'sitemap_entries.priority priority',
            ])
            ->from(['{{%categories}} categories'])
            ->innerJoin('{{%categorygroups}} categorygroups', '[[categories.groupId]] = [[categorygroups.id]]')
            ->innerJoin('{{%categorygroups_sites}} categorygroups_sites', '[[categorygroups_sites.groupId]] = [[categorygroups.id]] AND [[categorygroups_sites.hasUrls]] = 1')
            ->leftJoin('{{%entries}} entries', '[[categories.id]] = [[entries.sectionId]]')
            ->leftJoin('{{%elements}} elements', '[[entries.id]] = [[elements.id]] AND [[elements.enabled]] = 1')
            ->leftJoin('{{%homm_sitemap_entries}} sitemap_entries', '[[categorygroups.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "category"')
            ->groupBy(['categorygroups.id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    private function _createProductTypeQuery(): Query
    {
        return (new Query())
            ->select([
                'productTypes.id',
                'productTypes.name',
                'count(DISTINCT products.id) elementCount',
                'sitemap_entries.id sitemapEntryId',
                'sitemap_entries.changefreq changefreq',
                'sitemap_entries.priority priority',
            ])
            ->from([CommerceTable::PRODUCTTYPES . ' productTypes'])
            ->leftJoin(CommerceTable::PRODUCTS . ' products', '[[products.typeId]] = [[productTypes.id]] AND [[products.availableForPurchase]] = 1')
            ->leftJoin('{{%homm_sitemap_entries}} sitemap_entries', '[[productTypes.id]] = [[sitemap_entries.linkId]] AND [[sitemap_entries.type]] = "productType"')
            ->groupBy(['productTypes.id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/sitemap/default
     *
     * @return mixed
     */
    public function actionIndex(): craft\web\Response
    {
        $this->requireLogin();

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();

        $source = (isset($routeParameters['source'])?$routeParameters['source']:'CpSection');


        // $allSections = Craft::$app->getSections()->getAllSections();
        $allSections = $this->_createEntrySectionQuery()->all();
        $allStructures = [];

        if (is_array($allSections)) {
            foreach ($allSections as $section) {
                $allStructures[] = [
                    'id' => $section['id'],
                    'type' => $section['type'],
                    'heading' => $section['name'],
                    'enabled' => ($section['sitemapEntryId'] > 0 ? true : false),
                    'elementCount' => $section['elementCount'],
                    'changefreq' => ($section['sitemapEntryId'] > 0 ? $section['changefreq'] : 'weekly'),
                    'priority' => ($section['sitemapEntryId'] > 0 ? $section['priority'] : 0.5),
                ];
            }
        }

        $allCategories = $this->_createCategoryQuery()->all();
        $allCategoryStructures = [];
        if (is_array($allCategories)) {
            foreach ($allCategories as $category) {
                $allCategoryStructures[] = [
                    'id' => $category['id'],
                    'type' => 'category',
                    'heading' => $category['name'],
                    'enabled' => ($category['sitemapEntryId'] > 0 ? true : false),
                    'elementCount' => $category['elementCount'],
                    'changefreq' => ($category['sitemapEntryId'] > 0 ? $category['changefreq'] : 'weekly'),
                    'priority' => ($category['sitemapEntryId'] > 0 ? $category['priority'] : 0.5),
                ];
            }
        }

        $allProductTypeStructures = [];
        $commerce = Craft::$app->plugins->getPlugin('commerce');
        if ($commerce && $commerce->isInstalled) {
            $allProductTypes = $this->_createProductTypeQuery()->all();
            if (is_array($allProductTypes)) {
                foreach ($allProductTypes as $productType) {
                    $allProductTypeStructures[] = [
                        'id' => $productType['id'],
                        'type' => 'productType',
                        'heading' => $productType['name'],
                        'enabled' => ($productType['sitemapEntryId'] > 0 ? true : false),
                        'elementCount' => $productType['elementCount'],
                        'changefreq' => ($productType['sitemapEntryId'] > 0 ? $productType['changefreq'] : 'weekly'),
                        'priority' => ($productType['sitemapEntryId'] > 0 ? $productType['priority'] : 0.5),
                    ];
                }
            }
        }

        $variables = [
            'settings' => HOMMXMLSitemap::$plugin->getSettings(),
            'source' => $source,
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/': ''),
            'allStructures' => $allStructures,
            'allCategories' => $allCategoryStructures,
            'allProductTypes' => $allProductTypeStructures,
            // 'allRedirects' => $allRedirects
        ];

        return $this->renderTemplate('hommxmlsitemap/settings', $variables);
    }

    /**
     * Called when saving the settings.
     *
     * @throws \Throwable
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\ServerErrorHttpException
     * @return \Craft\web\Response
     */
    public function actionSaveSitemap(): craft\web\Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $request = Craft::$app->getRequest();
        // @TODO: check the input and save the sections
        $sitemapSections = $request->getBodyParam('sitemapSections');
        // filter the enabled sections
        $allSectionIds = [];

        $siteMapService = HOMMXMLSitemap::getInstance()->getSiteMap();

        if (is_array($sitemapSections)) {
            foreach ($sitemapSections as $key => $entry) {
                if ($entry['enabled']) {
                    // filter section id from key

                    $id = (int)str_replace('id:', '', $key);
                    if ($id > 0) {
                        // find the entry, else add one
                        $sitemapEntry = SitemapEntry::find()->where(['linkId' => $id, 'type' => 'section'])->one();
                        if (!$sitemapEntry) {
                            // insert / update this section
                            $sitemapEntry = new SitemapEntry();
                        }
                        $sitemapEntry->linkId = $id;
                        $sitemapEntry->type = 'section';
                        $sitemapEntry->priority = $entry['priority'];
                        $sitemapEntry->changefreq = $entry['changefreq'];
                        $siteMapService->saveEntry($sitemapEntry);
                        $allSectionIds[] = $id;
                    }
                }
            }
        }
        // remove all sitemaps not in the id list
        if(count($allSectionIds) == 0) {
            $records = SitemapEntry::findAll(['type' => 'section']);
            foreach ($records as $record){
                $siteMapService->deleteEntry($record);
            }
        } else {
            foreach (SitemapEntry::find()->where(['type' => 'section'])->andWhere(['NOT IN','linkId',$allSectionIds])->all() as $entry) {
                $siteMapService->deleteEntry($entry);
            }
        }

        // now save the sitemapCategories
        $sitemapCategories = $request->getBodyParam('sitemapCategories');
        // filter the enabled sections
        $allCategoryIds = [];
        if (is_array($sitemapCategories)) {
            foreach ($sitemapCategories as $key => $entry) {
                if ($entry['enabled']) {
                    // filter section id from key

                    $id = (int)str_replace('id:', '', $key);
                    if ($id > 0) {
                        // find the entry, else add one
                        $sitemapEntry = SitemapEntry::find()->where(['linkId' => $id, 'type' => 'category'])->one();
                        if (!$sitemapEntry) {
                            // insert / update this section
                            $sitemapEntry = new SitemapEntry();
                        }
                        $sitemapEntry->linkId = $id;
                        $sitemapEntry->type = 'category';
                        $sitemapEntry->priority = $entry['priority'];
                        $sitemapEntry->changefreq = $entry['changefreq'];
                        $siteMapService->saveEntry($sitemapEntry);
                        $allCategoryIds[] = $id;
                    }
                }
            }
        }
        // remove all sitemaps not in the id list
        if(count($allCategoryIds) == 0) {
            $entries = SitemapEntry::findAll(['type' => 'category']);
            foreach ($entries as $entry){
                $siteMapService->deleteEntry($entry);
            }
        } else {
            foreach (SitemapEntry::find()->where(['type' => 'category'])->andWhere(['NOT IN','linkId',$allCategoryIds])->all() as $entry) {
                $siteMapService->deleteEntry($entry);
            }
        }

        // now save the sitemapProductTypes
        $sitemapProductTypes = $request->getBodyParam('sitemapProductTypes');
        // filter the enabled sections
        $allProductTypeIds = [];
        if (is_array($sitemapProductTypes)) {
            foreach ($sitemapProductTypes as $key => $entry) {
                if ($entry['enabled']) {
                    // filter section id from key
                    $id = (int)str_replace('id:', '', $key);
                    if ($id > 0) {
                        // find the entry, else add one
                        $sitemapEntry = SitemapEntry::find()->where(['linkId' => $id, 'type' => 'productType'])->one();
                        if (!$sitemapEntry) {
                            // insert / update this section
                            $sitemapEntry = new SitemapEntry();
                        }
                        $sitemapEntry->linkId = $id;
                        $sitemapEntry->type = 'productType';
                        $sitemapEntry->priority = $entry['priority'];
                        $sitemapEntry->changefreq = $entry['changefreq'];
                        $siteMapService->saveEntry($sitemapEntry);
                        $allProductTypeIds[] = $id;
                    }
                }
            }
        }
        // remove all sitemaps not in the id list
        if(count($allProductTypeIds) == 0) {
            $entries = SitemapEntry::findAll(['type' => 'productType']);
            foreach ($entries as $entry){
                $siteMapService->deleteEntry($entry);
            }
        } else {
            foreach (SitemapEntry::find()->where(['type' => 'productType'])->andWhere(['NOT IN','linkId',$allProductTypeIds])->all() as $entry) {
                $siteMapService->deleteEntry($entry);
            }
        }

        return $this->actionIndex();
    }
}
