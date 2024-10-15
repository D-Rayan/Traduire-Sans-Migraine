<?php

namespace TraduireSansMigraine\Wordpress\Object;

use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;

class InternalsLinks
{
    private $ID;
    private $postId;
    private $slugPost;
    private $notTranslatedUrl;
    private $notTranslatedPostId;
    private $canBeFixed;
    private $lock;

    public function __construct($args)
    {
        $this->ID = $args["ID"];
        $this->postId = $args["postId"];
        $this->slugPost = $args["slugPost"];
        $this->notTranslatedUrl = $args["notTranslatedUrl"];
        $this->notTranslatedPostId = $args["notTranslatedPostId"];
        $this->canBeFixed = $args["canBeFixed"];
        $this->lock = $args["lock"];
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
        $postLanguageSlug = $tsm->getPolylangManager()->getLanguageSlugForPost($post->ID);
        $languagesActives = $tsm->getPolylangManager()->getLanguagesActives();
        foreach ($languagesActives as $slug => $languageActive) {
            if ($slug === $postLanguageSlug) {
                continue;
            }
            $result = $tsm->getLinkManager()->getIssuedInternalLinks($post->post_content, $postLanguageSlug, $slug);
            self::saveInternalsLinks($post->ID, $postLanguageSlug, $result["notTranslated"], false);
            self::saveInternalsLinks($post->ID, $postLanguageSlug, $result["notPublished"], false);
            self::saveInternalsLinks($post->ID, $postLanguageSlug, $result["translatable"], true);
        }
    }

    private static function saveInternalsLinks($postId, $slugPost, $links, $canBeFixed)
    {
        foreach ($links as $notTranslatedUrl => $notTranslatedPostId) {
            $internalsLinks = self::loadOrCreateByPostAndUrl($postId, $notTranslatedPostId);
            $internalsLinks
                ->setSlugPost($slugPost)
                ->setNotTranslatedUrl($notTranslatedUrl)
                ->setLock(null)
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
                "lock" => null,
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
                "lock" => $this->lock,
            ]);
        } else {
            DAOInternalsLinks::update($this->ID, [
                "lock" => $this->lock,
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

    public function getSlugPost()
    {
        return $this->slugPost;
    }

    public function setSlugPost($slugPost)
    {
        $this->slugPost = $slugPost;
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

    public function getCanBeFixed()
    {
        return $this->canBeFixed;
    }

    public function setCanBeFixed($canBeFixed)
    {
        $this->canBeFixed = $canBeFixed;
        return $this;
    }

    public function getLock()
    {
        return $this->lock;
    }

    public function setLock($lock)
    {
        $this->lock = $lock;
        return $this;
    }

}