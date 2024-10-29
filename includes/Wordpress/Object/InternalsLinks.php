<?php

namespace TraduireSansMigraine\Wordpress\Object;

use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

class InternalsLinks
{
    private $ID;
    private $postId;
    private $slugPost;
    private $notTranslatedUrl;
    private $notTranslatedPostId;
    private $canBeFixed;
    private $hasBeenFixed;

    public function __construct($args)
    {
        $this->ID = $args["ID"];
        $this->postId = $args["postId"];
        $this->slugPost = $args["slugPost"];
        $this->notTranslatedUrl = $args["notTranslatedUrl"];
        $this->notTranslatedPostId = $args["notTranslatedPostId"];
        $this->canBeFixed = $args["canBeFixed"];
        $this->hasBeenFixed = $args["hasBeenFixed"];
    }

    public static function getById($id)
    {
        $data = DAOInternalsLinks::getById($id);
        if ($data === null) {
            return null;
        }
        return new InternalsLinks($data);
    }

    public static function verifyPost($post)
    {
        global $tsm;
        $postLanguage = LanguagePost::getLanguage($post->ID);
        if (empty($postLanguage)) {
            return;
        }
        $languagesActives = $tsm->getPolylangManager()->getLanguagesActives();
        $postMeta = get_post_meta($post->ID);
        foreach ($languagesActives as $slug => $languageActive) {
            if ($slug === $postLanguage["code"]) {
                continue;
            }
            $result = $tsm->getLinkManager()->getIssuedInternalLinks($post->post_content, $slug, $postLanguage["code"]);
            self::saveInternalsLinks($post->ID, $postLanguage["code"], $slug, $result["notTranslated"], false);
            self::saveInternalsLinks($post->ID, $postLanguage["code"], $slug, $result["notPublished"], false);
            self::saveInternalsLinks($post->ID, $postLanguage["code"], $slug, $result["translatable"], true);

            foreach ($postMeta as $key => $value) {
                $valueKey = $value[0];
                if (!is_string($valueKey)) {
                    continue;
                }
                $result = $tsm->getLinkManager()->getIssuedInternalLinks($valueKey, $slug, $postLanguage["code"]);
                self::saveInternalsLinks($post->ID, $postLanguage["code"], $slug, $result["notTranslated"], false);
                self::saveInternalsLinks($post->ID, $postLanguage["code"], $slug, $result["notPublished"], false);
                self::saveInternalsLinks($post->ID, $postLanguage["code"], $slug, $result["translatable"], true);
            }
        }
    }

    private static function saveInternalsLinks($postId, $slugPost, $slugLinks, $links, $canBeFixed)
    {
        foreach ($links as $notTranslatedUrl => $notTranslatedPostId) {
            $languagePostNotTranslated = LanguagePost::getLanguage($notTranslatedPostId);
            if (empty($languagePostNotTranslated) || $languagePostNotTranslated["code"] !== $slugLinks) {
                continue;
            }
            $internalsLinks = self::loadOrCreateByPostAndUrl($postId, $notTranslatedPostId);
            $internalsLinks
                ->setSlugPost($slugPost)
                ->setNotTranslatedUrl($notTranslatedUrl)
                ->setHasBeenFixed(false)
                ->setCanBeFixed($canBeFixed)
                ->save();
        }
    }

    public static function loadOrCreateByPostAndUrl($postId, $notTranslatedPostId)
    {
        $data = DAOInternalsLinks::loadByPostIds($postId, $notTranslatedPostId);
        if ($data === null) {
            return new InternalsLinks([
                "ID" => null,
                "postId" => $postId,
                "slugPost" => null,
                "notTranslatedUrl" => null,
                "notTranslatedPostId" => $notTranslatedPostId,
                "canBeFixed" => false,
                "hasBeenFixed" => false,
            ]);
        }
        return new InternalsLinks($data);
    }

    public function save()
    {
        if (empty($this->ID)) {
            $this->ID = DAOInternalsLinks::create([
                "postId" => $this->postId,
                "slugPost" => $this->slugPost,
                "notTranslatedUrl" => $this->notTranslatedUrl,
                "notTranslatedPostId" => $this->notTranslatedPostId,
                "canBeFixed" => $this->canBeFixed,
                "hasBeenFixed" => $this->hasBeenFixed,
            ]);
        } else {
            DAOInternalsLinks::update($this->ID, [
                "hasBeenFixed" => $this->hasBeenFixed,
                "canBeFixed" => $this->canBeFixed,
            ]);
        }
        return $this;
    }

    public function getID()
    {
        return $this->ID;
    }

    public function setID($ID)
    {
        $this->ID = $ID;
        return $this;
    }

    public function getPostId()
    {
        return $this->postId;
    }

    public function setPostId($postId)
    {
        $this->postId = $postId;
        return $this;
    }

    public function getCanBeFixed()
    {
        return $this->canBeFixed;
    }

    public function setCanBeFixed($canBeFixed)
    {
        $this->canBeFixed = $canBeFixed;
        return $this;
    }

    public function getHasBeenFixed()
    {
        return $this->hasBeenFixed;
    }

    public function setHasBeenFixed($hasBeenFixed)
    {
        $this->hasBeenFixed = $hasBeenFixed;
        return $this;
    }

    public function fix()
    {
        global $tsm;
        $postContent = get_post_field('post_content', $this->postId);
        if ($postContent === null) {
            return $this;
        }

        $postContent = $tsm->getLinkManager()->replaceLink($postContent, $this->getNotTranslatedUrl(), $this->getNotTranslatedPostId(), $this->getSlugPost());
        wp_update_post([
            "ID" => $this->postId,
            "post_content" => $postContent,
        ]);

        $postMetas = get_post_meta($this->postId);
        foreach ($postMetas as $key => $value) {
            $valueKey = $value[0];
            if (!strstr($valueKey, $this->getNotTranslatedUrl())) {
                continue;
            }
            $valueKey = $tsm->getLinkManager()->replaceLink($valueKey, $this->getNotTranslatedUrl(), $this->getNotTranslatedPostId(), $this->getSlugPost());
            update_post_meta($this->postId, $key, $valueKey);
        }

        $this->setHasBeenFixed(true);
        return $this;
    }


    public function getNotTranslatedUrl()
    {
        return $this->notTranslatedUrl;
    }

    public function setNotTranslatedUrl($notTranslatedUrl)
    {
        $this->notTranslatedUrl = $notTranslatedUrl;
        return $this;
    }

    public function getNotTranslatedPostId()
    {
        return $this->notTranslatedPostId;
    }

    public function setNotTranslatedPostId($notTranslatedPostId)
    {
        $this->notTranslatedPostId = $notTranslatedPostId;
        return $this;
    }

    public function getSlugPost()
    {
        return $this->slugPost;
    }

    public function setSlugPost($slugPost)
    {
        $this->slugPost = $slugPost;
        return $this;
    }

}