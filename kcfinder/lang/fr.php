<?php

/** French localization file for KCFinder
 * author: Damien Barrère.
 */
$lang = [

    '_locale' => 'fr_FR.UTF-8',  // UNIX localization code
    '_charset' => 'utf-8',       // Browser charset

    // Date time formats. See http://www.php.net/manual/en/function.strftime.php
    '_dateTimeFull' => '%A, %e %B, %Y %H:%M',
    '_dateTimeMid' => '%a %e %b %Y %H:%M',
    '_dateTimeSmall' => '%d.%m.%Y %H:%M',

    "You don't have permissions to upload files." => "Vous n'avez pas les droits nécessaires pour envoyer des fichiers.",

    "You don't have permissions to browse server." => "Vous n'avez pas les droits nécessaires pour parcourir le serveur.",

    'Cannot move uploaded file to target folder.' => 'Impossible de déplacer le fichier téléchargé vers le répertoire de destination.',

    'Unknown error.' => 'Erreur inconnue.',

    'The uploaded file exceeds {size} bytes.' => 'Le fichier envoyé dépasse la taille maximale de {size} octects.',

    'The uploaded file was only partially uploaded.' => "Le fichier envoyé ne l'a été que partiellement.",

    'No file was uploaded.' => "Aucun fichier n'a été envoyé",

    'Missing a temporary folder.' => 'Il manque un répertoire temporaire.',

    'Failed to write file.' => 'Impossible de créer le fichier.',

    'Denied file extension.' => "Type d'extension de fichier interdit.",

    'Unknown image format/encoding.' => "Format/encodage d'image inconnu.",

    'The image is too big and/or cannot be resized.' => 'Image trop grande et/ou impossible de la redimensionner.',

    'Cannot create {dir} folder.' => 'Impossible de créer le répertoire {dir}.',

    'Cannot write to upload folder.' => "Impossible d'écrire dans le répertoire de destination.",

    'Cannot read .htaccess' => 'Impossible de lire le fichier .htaccess',

    'Incorrect .htaccess file. Cannot rewrite it!' => 'Fichier .htaccess incorrect. Réécriture du fichier impossible!',

    'Cannot read upload folder.' => "Impossible de lire le répertoire d'envoi.",

    'Cannot access or create thumbnails folder.' => "Impossible d'accéder ou de créer le répertoire des miniatures.",

    'Cannot access or write to upload folder.' => "Impossible d'accèder ou d'écrire dans le répertoire d'envoi.",

    'Please enter new folder name.' => "Merci d'entrer le nouveau nom de dossier.",

    'Unallowable characters in folder name.' => 'Caractères non autorisés dans le nom du dossier.',

    "Folder name shouldn't begins with '.'" => "Le nom du dossier ne peut pas commencer par '.'",

    'Please enter new file name.' => "Merci d'entrer le nouveau nom de fichier",

    'Unallowable characters in file name.' => 'Caractères non autorisés dans le nom du fichier.',

    "File name shouldn't begins with '.'" => "Le nom du fichier ne peut pas commencer par '.'",

    'Are you sure you want to delete this file?' => 'Êtes vous sûr du vouloir supprimer ce fichier?',

    'Are you sure you want to delete this folder and all its content?' => "Êtes vous sûr du vouloir supprimer ce répertoire et tous les fichiers qu'il contient?",

    'Non-existing directory type.' => 'Type de répertoire inexistant.',

    'Undefined MIME types.' => 'MIME types non déclarés.',

    'Fileinfo PECL extension is missing.' => "L'extension' Fileinfo PECL est manquante.",

    'Opening fileinfo database failed.' => 'Ouverture de la base de données fileinfo echouée.',

    "You can't upload such files." => 'Vous ne pouvez pas envoyer de tels fichiers.',

    "The file '{file}' does not exist." => "Le fichier '{file}' n'existe pas.",

    "Cannot read '{file}'." => "Impossible de lire le fichier '{file}'.",

    "Cannot copy '{file}'." => "Impossible de copier le fichier '{file}'.",

    "Cannot move '{file}'." => "Impossible de déplacer le fichier '{file}'.",

    "Cannot delete '{file}'." => "Impossible de supprimer le fichier '{file}'.",

    'Click to remove from the Clipboard' => 'Cliquez pour enlever du presse-papier',

    'This file is already added to the Clipboard.' => 'Ce fihier a déja été ajouté au presse-papier.',

    'Copy files here' => 'Copier les fichier ici',

    'Move files here' => 'Déplacer le fichiers ici',

    'Delete files' => 'Supprimer les fichiers',

    'Clear the Clipboard' => 'Vider le presse-papier',

    'Are you sure you want to delete all files in the Clipboard?' => 'Êtes vous sûr de vouloir supprimer tous les fichiers du presse-papier?',

    'Copy {count} files' => 'Copie de {count} fichiers',

    'Move {count} files' => 'Déplacement de {count} fichiers',

    'Add to Clipboard' => 'Ajouter au presse-papier',

    'New folder name:' => 'Nom du nouveau dossier:',
    'New file name:' => 'Nom du nouveau fichier:',

    'Upload' => 'Envoyer',
    'Refresh' => 'Rafraîchir',
    'Settings' => 'Paramètres',
    'Maximize' => 'Agrandir',
    'About' => 'A propos',
    'files' => 'fichiers',
    'View:' => 'Voir:',
    'Show:' => 'Montrer:',
    'Order by:' => 'Trier par:',
    'Thumbnails' => 'Miniatures',
    'List' => 'Liste',
    'Name' => 'Nom',
    'Size' => 'Taille',
    'Date' => 'Date',
    'Descending' => 'Décroissant',
    'Uploading file...' => 'Envoi en cours...',
    'Loading image...' => "Chargement de l'image'...",
    'Loading folders...' => 'Chargement des dossiers...',
    'Loading files...' => 'Chargement des fichiers...',
    'New Subfolder...' => 'Nouveau sous-dossier...',
    'Rename...' => 'Renommer...',
    'Delete' => 'Supprimer',
    'OK' => 'OK',
    'Cancel' => 'Annuler',
    'Select' => 'Sélectionner',
    'Select Thumbnail' => 'Sélectionner la miniature',
    'View' => 'Voir',
    'Download' => 'Télécharger',
    'Clipboard' => 'Presse-papier',

    // VERSION 2 NEW LABELS

    'Cannot rename the folder.' => 'Impossible de renommer le dossier.',

    'Cannot delete the folder.' => 'Impossible de supprimer le dossier.',

    'The files in the Clipboard are not readable.' => 'Les fichiers du presse-papier ne sont pas lisibles.',

    '{count} files in the Clipboard are not readable. Do you want to copy the rest?' => '{count} fichiers dans le presse-papier ne sont pas lisibles. Voulez vous copier le reste?',

    'The files in the Clipboard are not movable.' => 'Les fichiers du presse-papier ne peuvent pas être déplacés.',

    '{count} files in the Clipboard are not movable. Do you want to move the rest?' => '{count} fichiers du presse-papier ne peuvent pas être déplacées. Voulez vous déplacer le reste?',

    'The files in the Clipboard are not removable.' => 'Les fichiers du presse-papier ne peuvent pas être enlevés.',

    '{count} files in the Clipboard are not removable. Do you want to delete the rest?' => '{count} fichiers du presse-papier ne peuvent pas être enlevés. Vouslez vous supprimer le reste?',

    'The selected files are not removable.' => 'Les fichiers sélectionnés ne peuvent pas être enlevés.',

    '{count} selected files are not removable. Do you want to delete the rest?' => '{count} fichier sélectionnés ne peuvent pas être enlevés. Voulez vous supprimer le reste?',

    'Are you sure you want to delete all selected files?' => 'Êtes vous sûr de vouloir supprimer tous les fichiers sélectionnés?',

    'Failed to delete {count} files/folders.' => 'Supression de {count} fichiers/dossiers impossible.',

    'A file or folder with that name already exists.' => 'Un fichier ou dossier ayant ce nom existe déja.',

    'Inexistant or inaccessible folder.' => 'Dossier inexistant ou innacessible.',

    'selected files' => 'fichiers sélectionnés',
    'Type' => 'Type',
    'Select Thumbnails' => 'Sélectionner les miniatures',
    'Download files' => 'Télécharger les fichiers',
];
