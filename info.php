<?php
include 'env.php';

$update = array(
    "name" => TSM__NAME,
    "slug" => TSM__SLUG,
    "author" => "<a href='https://www.seo-sans-migraine.fr/'>SEO Sans Migraine</a>",
    "author_profile" => "https://profiles.wordpress.org/seo-sans-migraine",
    "version" => TSM__VERSION,
    "download_url" => TSM__URL_DOMAIN . "/wp-content/uploads/products/traduire-sans-migraine/traduire-sans-migraine.zip",
    "requires" => TSM__WORDPRESS_REQUIREMENT,
    "tested" => TSM__WORDPRESS_TESTED,
    "requires_php" => TSM__PHP_REQUIREMENT,
    "last_updated" => date("Y-m-d H:i:s", filemtime(__FILE__)),
    "sections" => [
        "description" => "Traduire Sans Migraine va vous aider à traduire votre contenu tout en gardant les bonnes pratiques SEO.",
        "installation" => "On installe, on suit les instructions et en moins de 2 minutes c'est parti !",
        "changelog" => "<h4>Mise à jour du 16 Octobre 2024</h4><ul>
        <li>Nouvelle interface</li>
        <li>Amélioration des options wordpress pour ne plus ralentir votre back office</li>
        <li>Gestion avancée des langues intégrés à l'outil</li>
        <li>Mise à jour de certains fonctionnement pour la compatibilité avec WordFence</li>
        <li>Préparation aux nouvelles features</li>
        <li>Une nouvelle loutre !</li>
        </ul>",
    ]
);

header('Content-Type: application/json');
echo json_encode($update);