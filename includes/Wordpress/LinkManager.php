<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;

class LinkManager
{

    private $linksTranslatedCount = 0;

    public function __construct() {}

    public function translateInternalLinks($postContent, $translateFrom, $translateTo)
    {
        $internalsPostIds = $this->extractAndRetrieveInternalLinks($postContent, $translateFrom, $translateTo);
        $newContent = $postContent;
        foreach ($internalsPostIds as $urlToReplace => $postIdRelated) {
            $newContent = $this->replaceLink($newContent, $urlToReplace, $postIdRelated, $translateTo);
        }

        return $newContent;
    }

    public function extractAndRetrieveInternalLinks($postContent, $translateFrom, $translateTo, $getErrors = false)
    {
        if (!is_string($translateFrom) || !is_string($translateTo)) {
            return [];
        }
        $homeUrl = str_replace("/", "\/", home_url());
        $regexAbsoluteUrl = '/' . $homeUrl . '\/(' . $translateFrom . '\/)?([a-z0-9-_\/\=\?#]+)(\/)*(#[a-z0-9-_\/\=\%]+)?/i';
        $regexRelativeUrl = '/"\/(' . $translateFrom . '\/)?([a-z0-9-_\/\=\?#]+)(\/)*(#[a-z0-9-_\/\=\%]+)?"/i';
        if ($translateTo !== $translateFrom) {
            $regexAbsoluteUrlExclude = '/' . $homeUrl . '\/' . $translateTo . '\/([a-z0-9-_\/\=\?#]+)(\/)*(#[a-z0-9-_\/\=\%]+)?/i';
            $regexRelativeUrlExclude = '/"\/' . $translateTo . '\/([a-z0-9-_\/\=\?#]+)(\/)*(#[a-z0-9-_\/\=\%]+)?"/i';
        } else {
            $regexAbsoluteUrlExclude = null;
            $regexRelativeUrlExclude = null;
        }
        return array_merge(
            $this->regexOnContent($regexAbsoluteUrl, $postContent, $getErrors, $regexAbsoluteUrlExclude),
            $this->regexOnContent($regexRelativeUrl, $postContent, $getErrors, $regexRelativeUrlExclude)
        );
    }

    private function regexOnContent($regex, $postContent, $getErrors = false, $regexExclude = null)
    {
        preg_match_all($regex, $postContent, $matches);
        $extractedUrls = $matches[0];
        $internalsPostsId = [];
        foreach ($extractedUrls as $extractedUrl) {
            if ($regexExclude && preg_match($regexExclude, $extractedUrl)) {
                continue;
            }
            $internalUrl = str_replace('"', '', $extractedUrl);
            $completeInternalUrl = $this->formatUrlToAbsolute($internalUrl);
            if ($this->isRestrictedURL($completeInternalUrl)) {
                continue;
            }
            $internalPostId = url_to_postid($completeInternalUrl);
            if (!$internalPostId) {
                if ($getErrors) {
                    $internalsPostsId[$internalPostId] = "notFound";
                }
                continue;
            }
            if ($getErrors) {
                continue;
            }
            $internalsPostsId[$internalUrl] = $internalPostId;
        }

        return $internalsPostsId;
    }

    public function formatUrlToAbsolute($url, $withSlash = true)
    {
        $result = $this->splitAllQueryAndAnchor($url);
        $url = $result["url"];
        if (false === strrpos($url, 'http://') && false === strrpos($url, 'https://')) {
            $resultUrl = get_home_url();
            if ($url[0] === "/") {
                $resultUrl .= $url;
            } else {
                $resultUrl .= "/" . $url;
            }
        } else {
            $resultUrl = $url;
        }

        $resultUrl = $this->addOrRemoveSlash($resultUrl, $withSlash);

        return $this->combineQueryAndAnchor($this->cleanMultiplesSlash($resultUrl), $result["query"], $result["anchor"]);
    }

    public function splitAllQueryAndAnchor($url)
    {
        $urlParts = explode("#", $url, 2);
        $url = $urlParts[0];
        $anchor = $urlParts[1] ?? "";

        $queryParts = explode("?", $url, 2);
        $url = $queryParts[0];
        $query = $queryParts[1] ?? "";

        return [
            "url" => $url,
            "query" => $query,
            "anchor" => $anchor
        ];
    }

    public function addOrRemoveSlash($url, $withSlash = true)
    {
        $result = $this->splitAllQueryAndAnchor($url);
        $resultUrl = $result["url"];
        if (empty($resultUrl)) {
            return $url;
        }
        if ($resultUrl[strlen($resultUrl) - 1] !== "/" && $withSlash) {
            $lastDotPosition = strrpos($resultUrl, ".");
            $lastSlashPosition = strrpos($resultUrl, "/");
            if ($lastDotPosition < $lastSlashPosition || $lastDotPosition === false) {
                $resultUrl .= "/";
            }
        } else if (!$withSlash && $resultUrl[strlen($resultUrl) - 1] == "/") {
            $resultUrl = substr($resultUrl, 0, -1);
        }
        return $this->combineQueryAndAnchor($resultUrl, $result["query"], $result["anchor"]);
    }

    public function combineQueryAndAnchor($url, $query, $anchor)
    {
        if (strlen($query) > 0) {
            $url .= "?" . $query;
        }
        if (strlen($anchor) > 0) {
            $url .= "#" . $anchor;
        }

        if (empty($url)) {
            return "#";
        }

        return $url;
    }

    public function cleanMultiplesSlash($urlWithoutExtra)
    {
        $urlWithoutExtra = str_replace("//", "/", $urlWithoutExtra);
        $urlWithoutExtra = str_replace("https:/", "https://", $urlWithoutExtra);
        $urlWithoutExtra = str_replace("http:/", "http://", $urlWithoutExtra);
        return $urlWithoutExtra;
    }

    private function isRestrictedURL($absoluteURI)
    {
        return false !== strpos($absoluteURI, "/wp-content/") || false !== strpos($absoluteURI, "/wp-includes/");
    }

    public function replaceLink($value, $linkToReplace, $linkPostId, $slugTo)
    {
        global $tsm;

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->replaceLink($val, $linkToReplace, $linkPostId, $slugTo);
            }
        } else if (is_string($value)) {
            if ($this->is_json($value)) {
                return $this->replaceLink(wp_slash($value), $linkToReplace, $linkPostId, $slugTo);
            } else if ($this->is_serialized($value)) {
                return $this->replaceLink(unserialize($value), $linkToReplace, $linkPostId, $slugTo);
            }
            $translations = TranslationPost::findTranslationFor($linkPostId);
            $internalPostIdTranslated = $translations->getTranslation($slugTo);
            if (empty($internalPostIdTranslated)) {
                return $value;
            }
            $titleInternalPostIdTranslated = get_permalink($internalPostIdTranslated);
            if (empty($titleInternalPostIdTranslated) || false !== strpos($titleInternalPostIdTranslated, "p=")) {
                return $value;
            }
            $this->linksTranslatedCount += substr_count($value, $linkToReplace);
            $value = str_replace($linkToReplace, $this->formatUrlToAbsolute($titleInternalPostIdTranslated), $value);
        }
        return $value;
    }

    private function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function is_serialized($string)
    {
        return ($string == serialize(false) || @unserialize($string) !== false);
    }

    public function getIssuedInternalLinks($postContent, $translateFrom, $translateTo)
    {
        global $tsm;
        if ($translateFrom === $translateTo) {
            return [
                "notTranslated" => [],
                "notPublished" => [],
                "translatable" => []
            ];
        }
        $internalsPostIds = $this->extractAndRetrieveInternalLinks($postContent, $translateFrom, $translateTo);
        $notTranslatedInternalLinks = $notPublishedInternalLinks = [];
        foreach ($internalsPostIds as $urlToReplace => $postIdRelated) {
            $translations = TranslationPost::findTranslationFor($postIdRelated);
            $internalPostIdTranslated = $translations->getTranslation($translateTo);
            if (!$internalPostIdTranslated) {
                $notTranslatedInternalLinks[$urlToReplace] = $postIdRelated;
                continue;
            }
            $titleInternalPostIdTranslated = get_permalink($internalPostIdTranslated);
            if (false !== strpos($titleInternalPostIdTranslated, "p=")) {
                $notPublishedInternalLinks[$urlToReplace] = $postIdRelated;
            }
        }
        $translatable = [];
        foreach ($internalsPostIds as $urlToReplace => $postIdRelated) {
            if (!isset($notTranslatedInternalLinks[$urlToReplace]) && !isset($notPublishedInternalLinks[$urlToReplace])) {
                $translatable[$urlToReplace] = $postIdRelated;
            }
        }
        return [
            "notTranslated" => $notTranslatedInternalLinks,
            "notPublished" => $notPublishedInternalLinks,
            "translatable" => $translatable
        ];
    }

    public function getLinksTranslatedCount()
    {
        return $this->linksTranslatedCount;
    }
}
