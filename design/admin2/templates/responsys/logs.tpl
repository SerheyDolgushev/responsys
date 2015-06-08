{ezcss_load( array( 'bootstrap.css', 'responsys.css' ) )}
{ezscript_load( array( 'collapse.js' ) )}

<div class="bootstrap-wrapper">
    <h2 class="h3 u-margin-t-m">{'Responsys Logs'|i18n( 'extension/responsys' )} ({$total_count})</h2>

    <form class="panel panel-primary" action="{'responsys/logs'|ezurl( 'no' )}" method="get">
        <div class="panel-heading">
            <h3 class="panel-title">{'Filter logs'|i18n( 'extension/responsys' )}</h3>
        </div>
        <div class="panel-body">
            <div class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-lg-2">{'Error'|i18n( 'extension/responsys' )}:</label>
                    <div class="col-lg-10">
                        <select class="form-control" name="filter[error]">
                            <option value="">{'- Not selected -'|i18n( 'extension/responsys' )}</option>
                            <option value="1"{if eq( $filter.error, '1' )} selected="selected"{/if}>{'Yes'|i18n( 'extension/responsys' )}</option>
                            <option value="0"{if eq( $filter.error, '0' )} selected="selected"{/if}>{'No'|i18n( 'extension/responsys' )}</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-2">{'Request filter'|i18n( 'extension/responsys' )}:</label>
                    <div class="col-lg-10">
                        <input class="form-control" type="text" value="{$filter.request}" name="filter[request]">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-2">{'Response filter'|i18n( 'extension/responsys' )}:</label>
                    <div class="col-lg-10">
                        <input class="form-control" type="text" value="{$filter.response}" name="filter[response]">
                    </div>
                </div>
                <div class="form-group u-margin-b-n">
                    <div class="col-lg-10 col-lg-offset-2">
                        <input class="btn btn-primary" type="submit" value="{'Filter'|i18n( 'extension/responsys' )}" />
                    </div>
                </div>
            </div>

        </div>
    </form>

    {include
        uri='design:navigator/google.tpl'
        page_uri='responsys/logs'
        item_count=$total_count
        view_parameters=hash( 'limit', $limit, 'offset', $offset )
        item_limit=$limit
    }
    {foreach $logs as $log}
        <div>
            <h3>{$log.date|datetime( 'custom', '%d %M %Y %H:%i:%s' )}, {$log.request_uri}, {$log.response_time} {'sec.'|i18n( 'extension/responsys' )}</h3>

            <div class="panel-group" id="accordion-{$log.id}">
                {if $log.request}
                    {include uri='design:responsys/collapse_part.tpl' id=concat( '1-', $log.id ) title='Request' content=concat( $log.request_headers, "\n\n", $log.request )}
                {/if}
                {if or( $log.response_headers, $log.response)}
                    {include uri='design:responsys/collapse_part.tpl' id=concat( '2-', $log.id ) title='Response' content=concat( $log.response_headers, "\n\n", $log.response )}
                {/if}
                {if $log.response_error}
                    {include uri='design:responsys/collapse_part.tpl' css_class='danger' id=concat( '3-', $log.id ) title='Error' content=$log.response_error}
                {/if}
                {include uri='design:responsys/collapse_part.tpl' id=concat( '4-', $log.id ) title='Backtrace' content=$log.backtrace_output}
            </div>
        </div>
        <hr>
    {/foreach}
    {include
        uri='design:navigator/google.tpl'
        page_uri='responsys/logs'
        item_count=$total_count
        view_parameters=hash( 'limit', $limit, 'offset', $offset )
        item_limit=$limit
    }
</div>