<?php

namespace TraduireSansMigraine\Wordpress;

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
        if (!str_contains($url, 'http://') && !str_contains($url, 'https://')) {
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
    public function extractAndRetrieveInternalLinks($postContent, $translateFrom) {
        $homeUrl = str_replace("/", "\/", home_url());
        preg_match_all('/"'.$homeUrl.'/('.$translateFrom.'\/)?([a-z0-9-_\\=\?#]+)(\/)*(#[a-z0-9-_\\=\%]+)?"/i', $postContent, $matches);
        $extractedUrls = $matches[0];
        $internalsPostsId = [];
        foreach ($extractedUrls as $extractedUrl) {
            $internalUrl = str_replace('"', '', $extractedUrl);
            $internalPostId = url_to_postid($this->formatUrlToAbsolute($internalUrl));
            if (!$internalPostId) {
                continue;
            }
            $internalsPostsId[$internalUrl] = $internalPostId;
        }

        return $internalsPostsId;
    }

    public function translateInternalLinks($postsContent, $translateFrom, $translateTo) {
        $internalsPostIds = $this->extractAndRetrieveInternalLinks($postsContent, $translateFrom);
        $newContent = $postsContent;
        $languageManager = new \TraduireSansMigraine\Languages\LanguageManager();
        foreach ($internalsPostIds as $urlToReplace => $postIdRelated) {
            $internalPostIdTranslated = $languageManager->getLanguageManager()->getTranslationPost($postIdRelated, $translateTo);
            if ($internalPostIdTranslated) {
                $titleInternalPostIdTranslated = $this->formatUrlToAbsolute(get_permalink($internalPostIdTranslated));

                if (strstr($titleInternalPostIdTranslated, "p=")) {
                    $postObject = get_post($internalPostIdTranslated);
                    if (!$postObject || !is_object($postObject)) {
                        continue;
                    }
                    $titleInternalPostIdTranslated = preg_replace("/\?p=[0-9]+/", $postObject->post_name, $titleInternalPostIdTranslated);
                }
                $newContent = str_replace($urlToReplace, $titleInternalPostIdTranslated, $newContent);
            }
        }

        return $newContent;
    }
}