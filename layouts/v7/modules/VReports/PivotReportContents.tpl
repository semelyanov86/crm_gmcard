<input type="hidden" class="decimal_separator" value="{$CURRENT_USER->currency_decimal_separator}">
<input type="hidden" class="grouping_separator" value="{$CURRENT_USER->currency_grouping_separator}">
<input type="hidden" class="no_of_currency_decimals" value="{$CURRENT_USER->no_of_currency_decimals}">
<input type="hidden" class="truncate_trailing_zeros" value="{$CURRENT_USER->truncate_trailing_zeros}">
<input type="hidden" class="currency_symbol_placement" value="{$CURRENT_USER->currency_symbol_placement}">
<input type="hidden" class="currency_grouping_pattern" value="{$CURRENT_USER->currency_grouping_pattern}">
<input type="hidden" class="currency_symbol" value="{html_entity_decode($CURRENT_USER->currency_symbol)}">
<script type="text/javascript" src="{$SITE_URL}/layouts/v7/modules/VReports/resources/exportPivot/react-0.12.2.min.js"></script>
<script type="text/javascript" src="{$SITE_URL}/layouts/v7/modules/VReports/resources/exportPivot/orb.js"></script>
{$DATA['export']}

<script src="{$SITE_URL}/layouts/v7/modules/VReports/resources/jbPivot/jbPivot.min.js"></script>
{$DATA['pivot']}
<div class="alert alert-danger message-alert-data-too-big text-center" style="display: none; margin-top:10px">Data is too big !!! you should export to excel for full data...</div>
<div id="reportDetails" style="overflow-x: auto; padding-top:20px">
</div>
<br>
{if $DATA_CHART}
<div class="dashboardWidgetContent" style="background-color: white">
    <div class='border1px filterConditionContainer' style="padding:30px;">
        <div id='chartcontent' name='chartcontent' style="min-height:400px;width: 50%" data-mode='Reports'>
            <input type="hidden" name="datachart" value="{$DATA_CHART_CHECK}"/>
            <canvas id="chart-area" style="margin-left: 50%"></canvas>
            {$DATA_CHART}
        </div>
    </div>
</div>
<br>
{/if}