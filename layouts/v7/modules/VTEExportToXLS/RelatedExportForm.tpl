{if $SOURCE_MODULE == 'Campaigns' && ($RELATED_MODULE == 'Contacts' || $RELATED_MODULE == 'Leads')}
    <button style="margin-left: 10px;" type="button" class="btn btn-default openModalExportToExcelButton">Export To Excel</button>
{else}
    <form style="float: right;" id="exportForm" method="post" action="index.php">
        <input type="hidden" name="module" value="{$MODULE}">
        <input type="hidden" name="action" value="ExportRelatedList">
        <input type="hidden" name="source_module" value="{$SOURCE_MODULE}">
        <input type="hidden" name="related_module" value="{$RELATED_MODULE}">
        <input type="hidden" name="record" value="{$RECORD}">
        <button style="margin-left: 10px;" type="submit" class="btn btn-default exportToExcelButton" name="exportToExcelButton">Export To Excel</button>
    </form>
{/if}