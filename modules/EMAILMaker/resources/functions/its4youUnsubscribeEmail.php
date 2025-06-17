<?php

if (!function_exists('its4you_unsubscribeemail')) {
    function its4you_unsubscribeemail($accounts_crmid, $contacts_crmid, $url_address, $label, $leads_crmid = "")
    {
        global $site_URL;

        $url = $site_URL;
        $link = "";
        $u = "";
        if ($accounts_crmid != "" && $accounts_crmid != "0" && is_numeric($accounts_crmid)) {
            $u = $accounts_crmid;
        } elseif ($contacts_crmid != "" && $contacts_crmid != "0" && is_numeric($contacts_crmid)) {
            $u = $contacts_crmid;
        } elseif ($leads_crmid != "" && $leads_crmid != "0" && is_numeric($leads_crmid)) {
            $u = $leads_crmid;
        }

        $code = md5($url);
        $small_code = substr($code, 5, 6);

        if ($u != "") {
            $link = "<a href='" . $url_address . "?u=" . $u . "&c=" . $small_code . "'>" . $label . "</a>";
        }

        return $link;
    }
}
?>
