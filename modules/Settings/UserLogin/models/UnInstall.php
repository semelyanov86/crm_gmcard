<?php

define("DS", DIRECTORY_SEPARATOR);
class UnInstall
{
    private $db = "";
    private $user = "";
    private $moduleName = "";
    private $links = array();
    private $customQueries = array();
    public function __construct($moduleName)
    {
        $this->db = PearDatabase::getInstance();
        $this->user = Users_Record_Model::getCurrentUserModel();
        $this->moduleName = $moduleName;
    }
    public function setLinks($links)
    {
        $this->links = $links;
    }
    public function getLinks()
    {
        return $this->links;
    }
    public function setCustomQuery($customQueries)
    {
        $this->customQueries = $customQueries;
    }
    public function getCustomQuery()
    {
        return $this->customQueries;
    }
    public function getFilePermissions($filePath, $octal = false)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        $perms = fileperms($filePath);
        $cut = $octal ? 2 : 3;
        return substr(decoct($perms), $cut);
    }
    public function getFileFolderOwner($path)
    {
        $ownerName = "";
        if (function_exists("fileowner") && function_exists("posix_getpwuid")) {
            $ownerid = fileowner($path);
            $ownerInfo = posix_getpwuid($ownerid);
            $ownerName = $ownerInfo["name"];
        }
        return $ownerName;
    }
    public function removeDataFromDB($queries)
    {
        if (!empty($queries)) {
            if (is_string($queries)) {
                $queries = array($queries);
            }
            foreach ($queries as $query) {
                $this->db->pquery($query, array());
            }
        }
    }
    public function getDropTableQueries($tables)
    {
        $queries = array();
        if (!empty($tables)) {
            foreach ($tables as $tableName) {
                $queries[] = "DROP TABLE `" . $tableName . "`;";
            }
        }
        return $queries;
    }
    public function getTabQuery()
    {
        $query = "DELETE FROM `vtiger_tab` WHERE `name` = '" . $this->moduleName . "';";
        return $query;
    }
    public function getWebServiceQuery()
    {
        $query = "DELETE FROM `vtiger_ws_entity` WHERE `name` = '" . $this->moduleName . "';";
        return $query;
    }
    public function getLinkQuery()
    {
        $links = $this->getLinks();
        $queries = array();
        if (!empty($links)) {
            foreach ($links as $link) {
                if (!empty($link)) {
                    $condition = array();
                    foreach ($link as $key => $value) {
                        $condition[] = " `" . $key . "` = '" . $value . "' ";
                    }
                    $queries[] = "DELETE FROM `vtiger_links` WHERE " . implode(" AND ", $condition) . ";";
                }
            }
        }
        return $queries;
    }
    public function getRelatedQuery()
    {
        $sql = "";
        $query = "SELECT * FROM `vtiger_relatedlists`\r\n                    WHERE `vtiger_relatedlists`.related_tabid = ( SELECT `vtiger_tab`.tabid FROM `vtiger_tab` WHERE `vtiger_tab`.name = '" . $this->moduleName . "' );";
        $result = $this->db->pquery($query, array());
        if (0 < $this->db->num_rows($result)) {
            $sql = "DELETE FROM `vtiger_relatedlists`\r\n                    WHERE `vtiger_relatedlists`.related_tabid = ( SELECT `vtiger_tab`.tabid FROM `vtiger_tab` WHERE `vtiger_tab`.name = '" . $this->moduleName . "' );";
        }
        return $sql;
    }
    public function getModuleQueries()
    {
        $queries = array();
        $customQueries = $this->getCustomQuery();
        if (!empty($customQueries)) {
            $queries = $customQueries;
        }
        $relatedQuery = $this->getRelatedQuery();
        if (!empty($relatedQuery)) {
            array_push($queries, $relatedQuery);
        }
        array_push($queries, $this->getWebServiceQuery());
        $linkQueries = $this->getLinkQuery();
        if ($linkQueries) {
            $queries = array_merge($queries, $linkQueries);
        }
        array_push($queries, $this->getTabQuery());
        return $queries;
    }
    public function getQueriesHTML()
    {
        $queries = $this->getModuleQueries();
        $query_html = "<ul class=\"nav nav-tabs nav-stacked\">";
        foreach ($queries as $k => $v) {
            $query_html .= "<li><a href=\"javascript:void(0);\" >" . $v . "</a></li>";
        }
        $query_html .= "</ul>";
        return $query_html;
    }
    public function deleteFile($filePath)
    {
        if (is_writeable($filePath) && is_file($filePath)) {
            checkFileAccessForInclusion($filePath);
            unlink($filePath);
            return true;
        }
        if (!is_writeable($filePath) && is_file($filePath)) {
            checkFileAccessForInclusion($filePath);
            chmod($filePath, 438);
            unlink($filePath);
            return true;
        }
        return false;
    }
    public function getFolderTree($folderPath, $level = 0)
    {
        $tree = array();
        if (!$folderPath || !is_dir($folderPath)) {
            return $tree;
        }
        $permission = $this->getFilePermissions($folderPath, true);
        $tree = array("path" => str_repeat("&nbsp;", $level) . $folderPath, "permission" => $permission, "writeable" => is_writeable($folderPath), "owner" => $this->getFileFolderOwner($folderPath), "type" => "folder", "level" => $level, "child" => array());
        $handle = opendir($folderPath);
        while ($tmp = readdir($handle)) {
            if ($tmp != ".." && $tmp != "." && $tmp != "") {
                $next = $level + 4;
                if (is_file($folderPath . DS . $tmp)) {
                    checkFileAccessForInclusion($folderPath . DS . $tmp);
                    $tree["child"][] = array("path" => str_repeat("&nbsp;", $next) . $folderPath . DS . $tmp, "permission" => $this->getFilePermissions($folderPath . DS . $tmp), "writeable" => is_writeable($folderPath . DS . $tmp), "owner" => $this->getFileFolderOwner($folderPath . DS . $tmp), "type" => "file", "level" => $next);
                }
                if (is_dir($folderPath . DS . $tmp)) {
                    $tree["child"][] = $this->getFolderTree($folderPath . DS . $tmp, $next);
                }
            }
        }
        closedir($handle);
        return $tree;
    }
    public function getFileDetails($filePath, $level = 0)
    {
        return array("path" => str_repeat("&nbsp;", $level) . $filePath, "permission" => $this->getFilePermissions($filePath), "writeable" => is_writeable($filePath), "owner" => $this->getFileFolderOwner($filePath), "type" => "file", "level" => $level);
    }
    public function getModuleStructure()
    {
        global $root_directory;
        $tree = array();
        if (is_dir($root_directory . "modules" . DS . $this->moduleName)) {
            $tree[] = $this->getFolderTree($root_directory . "modules" . DS . $this->moduleName);
        }
        if (is_dir($root_directory . "modules" . DS . "Settings" . DS . $this->moduleName)) {
            $tree[] = $this->getFolderTree($root_directory . "modules" . DS . "Settings" . DS . $this->moduleName);
        }
        if (is_dir($root_directory . "layouts" . DS . "vlayout" . DS . "modules" . DS . $this->moduleName)) {
            $tree[] = $this->getFolderTree($root_directory . "layouts" . DS . "vlayout" . DS . "modules" . DS . $this->moduleName);
        }
        if (is_dir($root_directory . "layouts" . DS . "vlayout" . DS . "modules" . DS . "Settings" . DS . $this->moduleName)) {
            $tree[] = $this->getFolderTree($root_directory . "layouts" . DS . "vlayout" . DS . "modules" . DS . "Settings" . DS . $this->moduleName);
        }
        if (is_dir($root_directory . "layouts" . DS . "v7" . DS . "modules" . DS . $this->moduleName)) {
            $tree[] = $this->getFolderTree($root_directory . "layouts" . DS . "v7" . DS . "modules" . DS . $this->moduleName);
        }
        if (is_dir($root_directory . "layouts" . DS . "v7" . DS . "modules" . DS . "Settings" . DS . $this->moduleName)) {
            $tree[] = $this->getFolderTree($root_directory . "layouts" . DS . "v7" . DS . "modules" . DS . "Settings" . DS . $this->moduleName);
        }
        if (is_file($root_directory . "languages" . DS . "en_us" . DS . $this->moduleName . ".php")) {
            $tree[] = $this->getFileDetails($root_directory . "languages" . DS . "en_us" . DS . $this->moduleName . ".php");
        }
        if (is_file($root_directory . "languages" . DS . "en_us" . DS . "Settings" . DS . $this->moduleName . ".php")) {
            $tree[] = $this->getFileDetails($root_directory . "languages" . DS . "en_us" . DS . "Settings" . DS . $this->moduleName . ".php");
        }
        return $tree;
    }
    public function buildModuleStructureHTML($tree, &$tree_html = "")
    {
        global $root_directory;
        foreach ($tree as $k => $v) {
            $text_color_css = " style=\"color: blue;\" ";
            $text_permission = "&nbsp;&nbsp;&nbsp;&nbsp;Ok";
            if ($v["writeable"] == 0) {
                $text_color_css = " style=\"color: red;\" ";
                $text_permission = "&nbsp;&nbsp;&nbsp;&nbsp;Invalid Permissions";
            }
            $owner = "";
            if ($v["owner"]) {
                $owner = "(" . $v["owner"] . ")";
            }
            $path = str_replace($root_directory, "", $v["path"]);
            $tree_html .= "<li><a href=\"javascript:void(0);\" " . $text_color_css . ">" . $path . "<span class=\"pull-right\">" . $v["permission"] . $owner . $text_permission . "</span></a></li>";
            if ($v["type"] == "folder" && 0 < count($v["child"])) {
                $this->buildModuleStructureHTML($v["child"], $tree_html);
            }
        }
        return $tree_html;
    }
    public function getModuleStructureHTML()
    {
        $tree = $this->getModuleStructure();
        $tree_html = "<ul class=\"nav nav-tabs nav-stacked\">";
        $tree_html .= $this->buildModuleStructureHTML($tree);
        $tree_html .= "</ul>";
        return $tree_html;
    }
    public function deleteFolder($folderPath)
    {
        if (!is_writeable($folderPath) && is_dir($folderPath)) {
            chmod($folderPath, 511);
        }
        $handle = opendir($folderPath);
        while ($tmp = readdir($handle)) {
            if ($tmp != ".." && $tmp != "." && $tmp != "") {
                if (is_writeable($folderPath . DS . $tmp) && is_file($folderPath . DS . $tmp)) {
                    checkFileAccessForInclusion($folderPath . DS . $tmp);
                    unlink($folderPath . DS . $tmp);
                } else {
                    if (!is_writeable($folderPath . DS . $tmp) && is_file($folderPath . DS . $tmp)) {
                        checkFileAccessForInclusion($folderPath . DS . $tmp);
                        chmod($folderPath . DS . $tmp, 438);
                        unlink($folderPath . DS . $tmp);
                    }
                }
                if (is_writeable($folderPath . DS . $tmp) && is_dir($folderPath . DS . $tmp)) {
                    $this->deleteFolder($folderPath . DS . $tmp);
                } else {
                    if (!is_writeable($folderPath . DS . $tmp) && is_dir($folderPath . DS . $tmp)) {
                        chmod($folderPath . DS . $tmp, 511);
                        $this->deleteFolder($folderPath . DS . $tmp);
                    }
                }
            }
        }
        closedir($handle);
        rmdir($folderPath);
        if (!is_dir($folderPath)) {
            return true;
        }
        return false;
    }
}

?>