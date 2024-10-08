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
    "last_updated" => date("Y-m-d H:i:s", filemtime( __FILE__ )),
    "sections" => [
        "description" => "Traduire Sans Migraine va vous aider à traduire votre contenu tout en gardant les bonnes pratiques SEO.",
        "installation" => "On installe, on suit les instructions et en moins de 2 minutes c'est parti !",
        "changelog" => "<h4>Mise à jour du 28 Août 2024</h4><ul>
        <li>Ajouter des nouvelles langues plus facilement (en un clic) on s’occupe du paramétrage de A à Z</li>
        <li><b>New</b> Le titre de tes images est désormais traduit, un vrai plus en SEO !</li> 
        <li><b>Bug Fix</b> La requête cible de Yoast est désormais traduite</li>
        <li><b>New</b> Tu peux ajouter un dictionnaire depuis ton espace : ça va te servir si tu veux qu’un mot ne soit pas traduit (par exemple votre nom de marque),</li>
        <li><b>Bug Fix</b> Le bug des liens internes avec Elementor est corrigé, tu peux foncer !</li> 
        </ul>",
    ]
);

header( 'Content-Type: application/json' );
echo json_encode( $update );