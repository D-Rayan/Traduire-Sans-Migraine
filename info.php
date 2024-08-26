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
        "changelog" => "<h4>Mise à jour du 26 Août 2024</h4><ul>
            <li>Meilleure gestion de Polylang (Installation, activation et configuration au sein de Traduire Sans Migraine)</li>
            <li>Ajout de la posibilité de traduire les médias lors de la traduction de vos contenus</li>
            <li>Ajout de la requête cible pour Yoast</li>
            <li>Gestion du dictionnaire disponible au sein de la page de configuration</li>
            <li>Gestion de la formalité au sein de la page de configuration</li>
            <li>Correction de la traduction des liens internes pour Elementor</li>
            <li>Amélioration de l'UX lors de la première installation</li>
            <li>Ajout d'un message lors de la désactivation du plugin</li>
            <li>Modification de la gestion des méta données des contenus</li>
            <li>Mise à jour de vos traductions en 1 clic</li>
        </ul>",
    ]
);

header( 'Content-Type: application/json' );
echo json_encode( $update );