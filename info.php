<?php
function get_environment()
{
    $dirname = dirname(__FILE__);

    if (strpos($dirname, 'staging') !== false) {
        return 'staging';
    }
    return "www";
}

$update = array(
    "name" => "traduire-sans-migraine",
    "slug" => "traduire-sans-migraine",
    "author" => "<a href='https://www.seo-sans-migraine.fr/'>SEO Sans Migraine</a>",
    "author_profile" => "https://profiles.wordpress.org/seo-sans-migraine",
    "version" => "0.0.1",
    "download_url" => "https://". get_environment() .".seo-sans-migraine.fr/wp-content/uploads/products/traduire-sans-migraine/traduire-sans-migraine.zip",
    "requires" => "3.0",
    "tested" => "5.8",
    "requires_php" => "7.0",
    "last_updated" => "2024-02-07 02:10:00",
    "sections" => [
        "description" => "TraduireSansMigraine is a plugin to help you improve your multilingual SEO. It will help you to translate your content without headache.",
        "installation" => "Just install it and active your account by following the steps.",
        "changelog" => "<h4>1.0 –  1 august 2021</h4><ul><li>Bug fixes.</li><li>Initital release.</li></ul>"
    ]
);

header( 'Content-Type: application/json' );
echo json_encode( $update );