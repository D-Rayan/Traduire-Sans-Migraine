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
        "changelog" => "<h4>Mise à jour du 13 Décembre 2024</h4><ul>
        <li>Fix : Prise en compte de Yoast SEO en version gratuite</li>
        </ul><h4>Mise à jour du 11 Décembre 2024</h4><ul>
        <li>Fix : Correction d'un problème qui pouvait dupliquer les slugs d'un article</li>
        </ul><h4>Mise à jour du 10 Décembre 2024</h4><ul>
        <li>(Béta) New : Woocommerce est dorénavant compatible.</li>
        <li>Update : Changement complet en interne du foncitonnement des traductions pour avoir un meilleur contrôle</li>
        <li>New : L'outil a été traduit en anglais et en espagnol</li>
        <li>Update : Amélioration de performances sur certaines requêtes</li>
        </ul><h4>Mise à jour du 12 Novembre 2024</h4><ul>
        <li>New : Les modèles elementors n'étaient pas traduisible. Ils le sont dorénavant.</li>
        </ul><h4>Mise à jour du 29 Octobre 2024</h4><ul>
        <li>Fix : Correction d'un problème d'affichage et de filtres dans la recherche des traductions groupés</li>
        </ul>
        <h4>Mise à jour du 24 Octobre 2024</h4><ul>
        <li>New : Vous pouvez voir un coût estimé pour chaque traduction</li>
        <li>Fix : Lors de certains cas la génération du fichier de design d'Elementor ne se faisait pas</li>
        </ul>
        <h4>Mise à jour du 23 Octobre 2024</h4><ul>
        <li>New : Nouvelle fonctionnalité pour la traduction des liens internes</li>
        <li>New : Ajout d'un shortcode pour l'affichage d'un sélecteur de langue</li>
        <li>Update : Elementor est mieux pris en charge, d'ailleurs le coût est réduit par deux environ !</li>
        <li>Fix : Correction d'un soucis liés à la création des médias</li>
        <li>Fix : Divers correctifs</li>
        </ul>",
    ]
);

header('Content-Type: application/json');
echo json_encode($update);
