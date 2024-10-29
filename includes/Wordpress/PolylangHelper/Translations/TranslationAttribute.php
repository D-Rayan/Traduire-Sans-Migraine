<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper\Translations;

class TranslationAttribute extends Translation
{
    private $objectId;

    public function __construct($id = null, $relatedId = null, $translations = [], $objectId = null)
    {
        $this->objectId = $objectId;
        parent::__construct($id, $relatedId, $translations);
    }

    public static function findTranslationFor($objectId)
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT tt.term_taxonomy_id, tt.term_id, tt.description FROM  $wpdb->term_taxonomy tt 
            WHERE tt.taxonomy = '%s'", self::getTaxonomy($objectId));

        $result = $wpdb->get_row($query);

        return !$result ? new static(null, null, [], $objectId) : new static($result->term_id, $result->term_taxonomy_id, unserialize($result->description), $objectId);
    }

    private static function getTaxonomy($id)
    {
        return "attribute_translations_" . $id;
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
    }

    public function delete()
    {
        global $wpdb;
        if (empty($this->id)) {
            return;
        }
        $wpdb->delete($wpdb->term_taxonomy, ['term_id' => $this->id]);
        $wpdb->delete($wpdb->terms, ['term_id' => $this->id]);
    }

    private function create()
    {
        global $wpdb;
        $name = "pll_" . dechex(intval(microtime(true)) * 1000);
        $wpdb->insert($wpdb->terms, ['name' => $name, 'slug' => $name, 'term_group' => 0]);
        $this->id = $wpdb->insert_id;
        $wpdb->insert($wpdb->term_taxonomy, ['term_id' => $this->id, 'taxonomy' => self::getTaxonomy($this->objectId), 'description' => serialize($this->translations), 'parent' => 0, "count" => count($this->translations)]);
        $this->relatedId = $wpdb->insert_id;
    }

    private function update()
    {
        global $wpdb;

        $wpdb->update($wpdb->term_taxonomy, ['description' => serialize($this->translations)], ['term_id' => $this->id]);
    }
}