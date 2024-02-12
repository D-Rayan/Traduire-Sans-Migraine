<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Languages\LanguageManager;

class LinkManager {
    public function splitAllQueryAndAnchor($url) {
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

    public function combineQueryAndAnchor($url, $query, $anchor) {
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

    public function formatUrlToAbsolute($url, $withSlash = true) {
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

    public function cleanMultiplesSlash($urlWithoutExtra) {
        $urlWithoutExtra = str_replace("//", "/", $urlWithoutExtra);
        $urlWithoutExtra = str_replace("https:/", "https://", $urlWithoutExtra);
        $urlWithoutExtra = str_replace("http:/", "http://", $urlWithoutExtra);
        return $urlWithoutExtra;
    }

    public function addOrRemoveSlash($url, $withSlash = true) {
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
    public function extractAndRetrieveInternalLinks($postContent, $translateFrom, $getErrors = false) {
        $homeUrl = str_replace("/", "\/", home_url());
        $regexAbsoluteUrl = '/'.$homeUrl.'\/('.$translateFrom.'\/)?([a-z0-9-_\/\=\?#]+)(\/)*(#[a-z0-9-_\/\=\%]+)?/i';
        $regexRelativeUrl = '/"\/('.$translateFrom.'\/)?([a-z0-9-_\/\=\?#]+)(\/)*(#[a-z0-9-_\/\=\%]+)?"/i';
        return array_merge(
            $this->regexOnContent($regexAbsoluteUrl, $postContent, $getErrors),
            $this->regexOnContent($regexRelativeUrl, $postContent, $getErrors)
        );
    }

    private function isRestrictedURL($absoluteURI) {
        return false !== strpos($absoluteURI, "/wp-content/") || false !== strpos($absoluteURI, "/wp-includes/");
    }

    private function regexOnContent($regex, $postContent, $getErrors = false)  {
        preg_match_all($regex, $postContent, $matches);
        $extractedUrls = $matches[0];
        $internalsPostsId = [];
        foreach ($extractedUrls as $extractedUrl) {
            $internalUrl = str_replace('"', '', $extractedUrl);
            $completeInternalUrl = $this->formatUrlToAbsolute($internalUrl);
            if ($this->isRestrictedURL($completeInternalUrl)) {
                continue;
            }
            $internalPostId = url_to_postid($completeInternalUrl);
            if (!$internalPostId) {
                if ($getErrors) {
                    $internalsPostsId[$internalUrl] = "notFound";
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

    public function translateInternalLinks($postContent, $translateFrom, $translateTo, $postId) {
        $internalsPostIds = $this->extractAndRetrieveInternalLinks($postContent, $translateFrom, $postId);
        $newContent = $postContent;
        $languageManager = new LanguageManager();
        foreach ($internalsPostIds as $urlToReplace => $postIdRelated) {
            $internalPostIdTranslated = $languageManager->getLanguageManager()->getTranslationPost($postIdRelated, $translateTo);
            if ($internalPostIdTranslated) {
                $titleInternalPostIdTranslated = get_permalink($internalPostIdTranslated);
                if ($titleInternalPostIdTranslated) {
                    $newContent = str_replace($urlToReplace, $this->formatUrlToAbsolute($titleInternalPostIdTranslated), $newContent);
                }
            }
        }

        return $newContent;
    }

    public function getIssuedInternalLinks($postContent, $translateFrom, $translateTo) {
        if ($translateFrom === $translateTo) {
            return [
                "notTranslated" => [],
                "notPublished" => []
            ];
        }
        $internalsPostIds = $this->extractAndRetrieveInternalLinks($postContent, $translateFrom);
        $languageManager = new LanguageManager();
        $notTranslatedInternalLinks = $notPublishedInternalLinks = [];
        foreach ($internalsPostIds as $urlToReplace => $postIdRelated) {
            $internalPostIdTranslated = $languageManager->getLanguageManager()->getTranslationPost($postIdRelated, $translateTo);
            if (!$internalPostIdTranslated) {
                $notTranslatedInternalLinks[$urlToReplace] = $postIdRelated;
                continue;
            }
            $titleInternalPostIdTranslated = get_permalink($internalPostIdTranslated);
            if (false !== strpos($titleInternalPostIdTranslated, "p=")) {
                $notPublishedInternalLinks[$urlToReplace] = $postIdRelated;
            }
        }
        return [
            "notTranslated" => $notTranslatedInternalLinks,
            "notPublished" => $notPublishedInternalLinks
        ];
    }
}