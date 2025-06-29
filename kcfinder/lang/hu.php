<?php

/**
 * Hungarian localization file for KCFinder.
 *
 * @author Dubravszky József <joe@chilicreative.hu>
 * @copyright None. Use free as per GPL.
 * @since 08/19/2010
 * @version 1.0
 */
$lang = [

    '_locale' => 'hu_HU.UTF-8',  // UNIX localization code
    '_charset' => 'utf-8',       // Browser charset

    // Date time formats. See http://www.php.net/manual/en/function.strftime.php
    '_dateTimeFull' => '%A, %e.%B.%Y %H:%M',
    '_dateTimeMid' => '%a %e %b %Y %H:%M',
    '_dateTimeSmall' => '%d/%m/%Y %H:%M',

    "You don't have permissions to upload files." => 'Nincs jogosultsága fájlokat feltölteni.',

    "You don't have permissions to browse server." => 'Nincs jogosultsága a kiszolgálón böngészni.',

    'Cannot move uploaded file to target folder.' => 'Nem lehet áthelyezni a feltöltött fájlt a célkönyvtárba.',

    'Unknown error.' => 'Ismeretlen hiba.',

    'The uploaded file exceeds {size} bytes.' => 'A feltöltött fájl mérete meghaladja a {size} bájtot.',

    'The uploaded file was only partially uploaded.' => 'A feltöltendő fájl csak részben sikerült feltölteni.',

    'No file was uploaded.' => 'Nem történt fájlfeltöltés.',

    'Missing a temporary folder.' => 'Hiányzik az ideiglenes könyvtár.',

    'Failed to write file.' => 'Nem sikerült a fájl írása.',

    'Denied file extension.' => 'Tiltott fájlkiterjesztés.',

    'Unknown image format/encoding.' => 'Ismeretlen képformátum vagy kódolás.',

    'The image is too big and/or cannot be resized.' => 'A kép mérete túl nagy és/vagy nem lehet átméretezni.',

    'Cannot create {dir} folder.' => 'Nem lehet létrehozni a {dir} könyvtárat.',

    'Cannot write to upload folder.' => 'Nem lehet írni a feltöltési könyvtárba.',

    'Cannot read .htaccess' => 'Nem lehet olvasni a .htaccess fájlt',

    'Incorrect .htaccess file. Cannot rewrite it!' => 'Hibás .htaccess fájl. Nem lehet írni.',

    'Cannot read upload folder.' => 'Nem lehet olvasni a feltöltési könyvtárat.',

    'Cannot access or create thumbnails folder.' => 'Nem lehet elérni vagy létrehozni a bélyegképek könyvtárat.',

    'Cannot access or write to upload folder.' => 'Nem lehet elérni vagy létrehozni a feltöltési könyvtárat.',

    'Please enter new folder name.' => 'Kérem, adja meg az új könyvtár nevét.',

    'Unallowable characters in folder name.' => 'Meg nem engedett karakter(ek) a könyvtár nevében.',

    "Folder name shouldn't begins with '.'" => "Könyvtárnév nem kezdődhet '.'-tal",

    'Please enter new file name.' => 'Kérem adja meg az új fájl nevét.',

    'Unallowable characters in file name.' => 'Meg nem engedett karakter(ek) a fájl nevében.',

    "File name shouldn't begins with '.'" => "Fájlnév nem kezdődhet '.'-tal",

    'Are you sure you want to delete this file?' => 'Biztos benne, hogy törölni kívánja ezt a fájlt?',

    'Are you sure you want to delete this folder and all its content?' => 'Biztos benne hogy törölni kívánja ezt a könyvtárat és minden tartalmát?',

    'Inexistant or inaccessible folder.' => 'Nem létező vagy elérhetetlen könyvtár.',

    'Undefined MIME types.' => 'Meghatározatlan MIME típusok.',

    'Fileinfo PECL extension is missing.' => 'Hiányzó PECL Fileinfo PHP kiegészítés.',

    'Opening fileinfo database failed.' => 'Nem sikerült megnyitni a Fileinfo adatbázist.',

    "You can't upload such files." => 'Nem tölthet fel ilyen fájlokat.',

    "The file '{file}' does not exist." => "A '{file}' fájl nem létezik.",

    "Cannot read '{file}'." => "A '{file}' fájlt nem lehet olvasni.",

    "Cannot copy '{file}'." => "A '{file}' fájlt nem lehet másolni.",

    "Cannot move '{file}'." => "A '{file}' fájlt nem lehet áthelyezni.",

    "Cannot delete '{file}'." => "A '{file}' fájlt nem lehet törölni.",

    'Click to remove from the Clipboard' => 'kattintson ide, hogy eltávolítsa a vágólapról',

    'This file is already added to the Clipboard.' => 'Ezt a fájlt már hozzáadta a vágólaphoz.',

    'Copy files here' => 'Fájlok másolása ide',

    'Move files here' => 'Fájlok áthelyezése ide',

    'Delete files' => 'Fájlok törlése',

    'Clear the Clipboard' => 'Vágólap ürítése',

    'Are you sure you want to delete all files in the Clipboard?' => 'Biztosan törölni kívánja a vágólapon lévő összes fájlt?',

    'Copy {count} files' => '{count} fájl másolása',

    'Move {count} files' => '{count} fájl áthelyezése',

    'Add to Clipboard' => 'Hozzáadás vágólaphoz',

    'New folder name:' => 'Új könyvtár neve:',

    'New file name:' => 'Új fájl neve:',

    'Upload' => 'Feltöltés',
    'Refresh' => 'Frissítés',
    'Settings' => 'Beállítások',
    'Maximize' => 'Maximalizálás',
    'About' => 'Névjegy',
    'files' => 'fájlok',
    'View:' => 'Nézet:',
    'Show:' => 'Mutat:',
    'Order by:' => 'Rendezés:',
    'Thumbnails' => 'Bélyegképek',
    'List' => 'Lista',
    'Name' => 'Név',
    'Size' => 'Méret',
    'Date' => 'Dátum',
    'Descending' => 'Csökkenő',
    'Uploading file...' => 'Fájl feltöltése...',
    'Loading image...' => 'Képek betöltése...',
    'Loading folders...' => 'Könyvtárak betöltése...',
    'Loading files...' => 'Fájlok betöltése...',
    'New Subfolder...' => 'Új alkönyvtár...',
    'Rename...' => 'Átnevezés...',
    'Delete' => 'Törlés',
    'OK' => 'OK',
    'Cancel' => 'Mégse',
    'Select' => 'Kiválaszt',
    'Select Thumbnail' => 'Bélyegkép kiválasztása',
    'View' => 'Nézet',
    'Download' => 'Letöltés',
    'Clipboard' => 'Vágólap',

    // VERSION 2 NEW LABELS

    'Cannot rename the folder.' => 'A könyvtárat nem lehet átnevezni.',

    'Non-existing directory type.' => 'Nem létező könyvtártípus.',

    'Cannot delete the folder.' => 'Nem lehet törölni a könyvtárat.',

    'The files in the Clipboard are not readable.' => 'A vágólapon lévő fájlok nem olvashatók.',

    '{count} files in the Clipboard are not readable. Do you want to copy the rest?' => '{count} fájl a vágólapon nem olvasható. Akarja másolni a többit?',

    'The files in the Clipboard are not movable.' => 'A vágólapon lévő fájlokat nem lehet áthelyezni.',

    '{count} files in the Clipboard are not movable. Do you want to move the rest?' => '{count} fájlt a vágólapon nem lehet áthelyezni. Akarja áthelyezni a többit?',

    'The files in the Clipboard are not removable.' => 'A vágólapon lévő fájlokat nem lehet eltávolítani.',

    '{count} files in the Clipboard are not removable. Do you want to delete the rest?' => '{count} fájlt a vágólapon nem lehet eltávolítani. Akarja törölni a többit?',

    'The selected files are not removable.' => 'A kiválasztott fájlokat nem lehet eltávolítani.',

    '{count} selected files are not removable. Do you want to delete the rest?' => '{count} kiválasztott fájlt nem lehet eltávolítani. Akarja törölni a többit?',

    'Are you sure you want to delete all selected files?' => 'Biztosan törölni kíván minden kijelölt fájlt?',

    'Failed to delete {count} files/folders.' => 'Nem sikerült törölni {count} fájlt.',

    'A file or folder with that name already exists.' => 'Egy fájl vagy könyvtár már létezik ugyan ezzel a névvel.',

    'selected files' => 'kiválasztott fájlok',

    'Type' => 'Típus',

    'Select Thumbnails' => 'Bélyegképek kiválasztása',

    'Download files' => 'Fájlok letöltése',
];
