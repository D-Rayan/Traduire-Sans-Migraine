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
        "changelog" => "<h4>Mise à jour du 20 mai 2024</h4><ul>
            <li>Amélioration de l'utilisation de Polylang lors de la première installation avec l'ajout de messages explicatifs.</li>
            <li>Notification ajoutée pour informer que la traduction est en cours et que l'utilisateur peut quitter</li>
            <li>Suppression du bouton 'traduire plus tard' jugé non essentiel</li>
            <li>Ajout d'un bouton permettant de désactiver l'affichage automatique des traductions</li>
            <li>Vérification et correction de l'affichage des messages d'erreur</li>
            <li>Optimisation de la gestion des quotas</li>
            <li>Ajout d'une vidéo tutorielle dans la page paramètres</li>
            <li>[TEST] Intégration d'Elementor</li>
            <li>Ajout d'un message pour guider l'utilisateur après une traduction</li>
            <li>Ajout d'un système pour traduire plusieurs articles !</li>
        </ul>",
    ]
);

header( 'Content-Type: application/json' );
echo json_encode( $update );