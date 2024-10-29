<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper\Translations;

abstract class Translation
{
    protected $id; // term_id
    protected $relatedId; // term_taxonomy_id

    protected $count;
    protected $translations;
    protected $addedTranslations;
    protected $deletedTranslations;

    public function __construct($id = null, $relatedId = null, $translations = [])
    {
        $this->id = $id;
        $this->relatedId = $relatedId;
        $this->translations = $translations;
        $this->count = count($this->translations);
        $this->addedTranslations = [];
        $this->deletedTranslations = [];
    }

    public static function findTranslationFor($objectId)
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT tr.term_taxonomy_id, tt.term_id, tt.description FROM $wpdb->term_relationships tr 
            LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
            WHERE tr.object_id = %d AND tt.taxonomy = '%s'", $objectId, self::getTaxonomy());

        $result = $wpdb->get_row($query);

        return !$result ? new static() : new static($result->term_id, $result->term_taxonomy_id, unserialize($result->description));
    }

    private static function getTaxonomy()
    {
        if (static::class === TranslationTerms::class) {
            return "term_translations";
        } else {
            return "post_translations";
        }
    }

    public static function load($id)
    {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("SELECT tt.taxonomy, tt.term_taxonomy_id, tt.description FROM $wpdb->term_taxonomy tt WHERE tt.term_id = %d", $id));
        if (!$result) {
            return null;
        }
        if ($result->taxonomy !== self::getTaxonomy()) {
            return null;
        }
        $instance = new static($id, $result->term_taxonomy_id, unserialize($result->description));
        return $instance;
    }

    public function save()
    {
        if (empty($this->translations)) {
            $this->delete();
            return;
        }
        if (empty($this->id)) {
            $this->create();
        } else {
            $this->update();
        }
        $this->handleUpdatedTranslations();
    }

    private function delete()
    {
        global $wpdb;
        if (empty($this->id)) {
            return;
        }
        $wpdb->delete($wpdb->term_taxonomy, ['term_id' => $this->id]);
        $wpdb->delete($wpdb->terms, ['term_id' => $this->id]);
        if (empty($this->relatedId)) {
            return;
        }
        $wpdb->delete($wpdb->term_relationships, ['term_taxonomy_id' => $this->relatedId]);
    }

    private function create()
    {
        global $wpdb;
        $name = "pll_" . dechex(intval(microtime(true)) * 1000);
        $wpdb->insert($wpdb->terms, ['name' => $name, 'slug' => $name, 'term_group' => 0]);
        $this->id = $wpdb->insert_id;
        $wpdb->insert($wpdb->term_taxonomy, ['term_id' => $this->id, 'taxonomy' => self::getTaxonomy(), 'description' => serialize($this->translations), 'parent' => 0, "count" => count($this->translations)]);
        $this->relatedId = $wpdb->insert_id;
    }

    private function update()
    {
        global $wpdb;

        $wpdb->update($wpdb->term_taxonomy, ['description' => serialize($this->translations)], ['term_id' => $this->id]);
    }

    private function handleUpdatedTranslations()
    {
        $this->handleDeletedTranslations();
        $this->handleAddedTranslations();
    }

    private function handleDeletedTranslations()
    {
        global $wpdb;

        foreach ($this->deletedTranslations as $languageSlug => $objectId) {
            $wpdb->delete($wpdb->term_relationships, ['object_id' => $objectId, 'term_taxonomy_id' => $this->relatedId]);
        }
    }

    private function handleAddedTranslations()
    {
        global $wpdb;

        foreach ($this->addedTranslations as $languageSlug => $objectId) {
            $wpdb->insert($wpdb->term_relationships, ['object_id' => $objectId, 'term_taxonomy_id' => $this->relatedId, 'term_order' => 0]);
        }
    }

    public function getCount()
    {
        return $this->count;
    }

    public function canMerge($translation)
    {
        foreach ($this->translations as $languageSlug => $objectId) {
            $objectId2 = $translation->getTranslation($languageSlug);
            if ($objectId2 && $objectId && $objectId2 != $objectId) {
                return false;
            }
        }
        return true;
    }

    public function getTranslation($languageSlug)
    {
        return isset($this->translations[$languageSlug]) ? $this->translations[$languageSlug] : 0;
    }

    /**
     * @param $translation TranslationTerms|TranslationPost
     * @return void
     */
    public function merge(&$translation)
    {
        foreach ($translation->getTranslations() as $languageSlug => $objectId) {
            $this->translations[$languageSlug] = $objectId;
            $translation->removeTranslation($languageSlug);
        }
        if ($translation->getId() && !$this->getId()) {
            $this->id = $translation->getId();
            $this->relatedId = $translation->getRelatedId();
        }
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function setTranslations($translations)
    {
        foreach ($this->translations as $slug => $objectId) {
            if (isset($translations[$slug])) {
                continue;
            }
            $this->removeTranslation($slug);
        }
        foreach ($translations as $languageSlug => $objectId) {
            $this->addTranslation($languageSlug, $objectId);
        }
    }

    public function removeTranslation($languageSlug)
    {
        if (!isset($this->translations[$languageSlug])) {
            return $this;
        }
        $this->deletedTranslations[$languageSlug] = $this->translations[$languageSlug];
        unset($this->translations[$languageSlug]);
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRelatedId()
    {
        return $this->relatedId;
    }

    public function addTranslation($languageSlug, $objectId)
    {
        $translationExist = isset($this->translations[$languageSlug]);
        if ($translationExist) {
            if ($this->getTranslation($languageSlug) == $objectId) {
                return $this;
            }
            $this->deletedTranslations[$languageSlug] = $this->getTranslation($languageSlug);
        }
        $this->addedTranslations[$languageSlug] = $objectId;
        $this->translations[$languageSlug] = $objectId;

        return $this;
    }
}