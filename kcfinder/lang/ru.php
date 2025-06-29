<?php

/** Russian localization file for KCFinder
 * author: Dark Preacher
 * E-mail: dark@darklab.ru.
 */
$lang = [

    '_locale' => 'ru_RU.UTF-8',  // UNIX localization code
    '_charset' => 'utf-8',       // Browser charset

    // Date time formats. See http://www.php.net/manual/en/function.strftime.php
    '_dateTimeFull' => '%A, %e %B, %Y %H:%M',
    '_dateTimeMid' => '%a %e %b %Y %H:%M',
    '_dateTimeSmall' => '%d.%m.%Y %H:%M',

    "You don't have permissions to upload files." => 'У вас нет прав для загрузки файлов.',

    "You don't have permissions to browse server." => 'У вас нет прав для просмотра содержимого на сервере.',

    'Cannot move uploaded file to target folder.' => 'Невозможно переместить загруженный файл в папку назначения.',

    'Unknown error.' => 'Неизвестная ошибка.',

    'The uploaded file exceeds {size} bytes.' => 'Загруженный файл превышает размер {size} байт.',

    'The uploaded file was only partially uploaded.' => 'Загруженный файл был загружен только частично.',

    'No file was uploaded.' => 'Файл не был загружен',

    'Missing a temporary folder.' => 'Временная папка не существует.',

    'Failed to write file.' => 'Невозможно записать файл.',

    'Denied file extension.' => 'Файлы этого типа запрещены для загрузки.',

    'Unknown image format/encoding.' => 'Неизвестный формат изображения.',

    'The image is too big and/or cannot be resized.' => 'Изображение слишком большое и/или не может быть уменьшено.',

    'Cannot create {dir} folder.' => 'Невозможно создать папку {dir}.',

    'Cannot write to upload folder.' => 'Невозможно записать в папку загрузки.',

    'Cannot read .htaccess' => 'Невозможно прочитать файл .htaccess',

    'Incorrect .htaccess file. Cannot rewrite it!' => 'Неправильный файл .htaccess. Невозможно перезаписать!',

    'Cannot read upload folder.' => 'Невозможно прочитать папку загрузки.',

    'Cannot access or create thumbnails folder.' => 'Нет доступа или невозможно создать папку миниатюр.',

    'Cannot access or write to upload folder.' => 'Нет доступа или невозможно записать в папку загрузки.',

    'Please enter new folder name.' => 'Укажите имя новой папки.',

    'Unallowable characters in folder name.' => 'Недопустимые символы в имени папки.',

    "Folder name shouldn't begins with '.'" => "Имя папки не может начинаться с '.'",

    'Please enter new file name.' => 'Укажите новое имя файла',

    'Unallowable characters in file name.' => 'Недопустимые символны в имени файла.',

    "File name shouldn't begins with '.'" => "Имя файла не может начинаться с '.'",

    'Are you sure you want to delete this file?' => 'Вы уверены, что хотите удалить этот файл?',

    'Are you sure you want to delete this folder and all its content?' => 'Вы уверены, что хотите удалить эту папку и всё её содержимое?',

    'Non-existing directory type.' => 'Несуществующий тип папки.',

    'Undefined MIME types.' => 'Неопределённые типы MIME.',

    'Fileinfo PECL extension is missing.' => 'Расширение Fileinfo PECL отсутствует.',

    'Opening fileinfo database failed.' => 'Невозможно открыть базу данных fileinfo.',

    "You can't upload such files." => 'Вы не можете загружать файлы этого типа.',

    "The file '{file}' does not exist." => "Файл '{file}' не существует.",

    "Cannot read '{file}'." => "Невозможно прочитать файл '{file}'.",

    "Cannot copy '{file}'." => "Невозможно скопировать файл '{file}'.",

    "Cannot move '{file}'." => "Невозможно переместить файл '{file}'.",

    "Cannot delete '{file}'." => "Невозможно удалить файл '{file}'.",

    'Click to remove from the Clipboard' => 'Нажмите для удаления из буфера обмена',

    'This file is already added to the Clipboard.' => 'Этот файл уже добавлен в буфер обмена.',

    'Copy files here' => 'Скопировать файлы сюда',

    'Move files here' => 'Переместить файлы сюда',

    'Delete files' => 'Удалить файлы',

    'Clear the Clipboard' => 'Очистить буфер обмена',

    'Are you sure you want to delete all files in the Clipboard?' => 'Вы уверены, что хотите удалить все файлы в буфере обмена?',

    'Copy {count} files' => 'Скопировать {count} файл(ов)',

    'Move {count} files' => 'Переместить {count} файл(ов)',

    'Add to Clipboard' => 'Добавить в буфер обмена',

    'New folder name:' => 'Новое имя папки:',
    'New file name:' => 'Новое имя файла:',

    'Upload' => 'Загрузить',
    'Refresh' => 'Обновить',
    'Settings' => 'Установки',
    'Maximize' => 'Развернуть',
    'About' => 'О скрипте',
    'files' => 'файлы',
    'View:' => 'Просмотр:',
    'Show:' => 'Показывать:',
    'Order by:' => 'Упорядочить по:',
    'Thumbnails' => 'Миниатюры',
    'List' => 'Список',
    'Name' => 'Имя',
    'Size' => 'Размер',
    'Date' => 'Дата',
    'Descending' => 'По убыванию',
    'Uploading file...' => 'Загрузка файла...',
    'Loading image...' => 'Загрузка изображения...',
    'Loading folders...' => 'Загрузка папок...',
    'Loading files...' => 'Загрузка файлов...',
    'New Subfolder...' => 'Создать папку...',
    'Rename...' => 'Переименовать...',
    'Delete' => 'Удалить',
    'OK' => 'OK',
    'Cancel' => 'Отмена',
    'Select' => 'Выбрать',
    'Select Thumbnail' => 'Выбрать миниатюру',
    'View' => 'Просмотр',
    'Download' => 'Скачать',
    'Clipboard' => 'Буфер обмена',

    // VERSION 2 NEW LABELS

    'Cannot rename the folder.' => 'Невозможно переименовать папку.',

    'Cannot delete the folder.' => 'Невозможно удалить папку.',

    'The files in the Clipboard are not readable.' => 'Невозможно прочитать файлы в буфере обмена.',

    '{count} files in the Clipboard are not readable. Do you want to copy the rest?' => 'Невозможно прочитать {count} файл(ов) в буфере обмена. Вы хотите скопировать оставшиеся?',

    'The files in the Clipboard are not movable.' => 'Невозможно переместить файлы в буфере обмена.',

    '{count} files in the Clipboard are not movable. Do you want to move the rest?' => 'Невозможно переместить {count} файл(ов) в буфере обмена. Вы хотите переместить оставшиеся?',

    'The files in the Clipboard are not removable.' => 'Невозможно удалить файлы в буфере обмена.',

    '{count} files in the Clipboard are not removable. Do you want to delete the rest?' => 'Невозможно удалить {count} файл(ов) в буфере обмена. Вы хотите удалить оставшиеся?',

    'The selected files are not removable.' => 'Невозможно удалить выбранные файлы.',

    '{count} selected files are not removable. Do you want to delete the rest?' => 'Невозможно удалить выбранный(е) {count} файл(ы). Вы хотите удалить оставшиеся?',

    'Are you sure you want to delete all selected files?' => 'Вы уверены, что хотите удалить все выбранные файлы?',

    'Failed to delete {count} files/folders.' => 'Невозможно удалить {count} файлов/папок.',

    'A file or folder with that name already exists.' => 'Файл или папка с таким именем уже существуют.',

    'Inexistant or inaccessible folder.' => 'Несуществующая или недоступная папка.',

    'selected files' => 'выбранные файлы',
    'Type' => 'Тип',
    'Select Thumbnails' => 'Выбрать миниатюры',
    'Download files' => 'Скачать файлы',
];
