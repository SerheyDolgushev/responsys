{default $css_class = 'info'}
<div class="panel panel-{$css_class}">
    <div class="panel-heading">
        <h4 class="panel-title"><a data-toggle="collapse" href="#collapse{$id}" class="collapsed">{$title|i18n( 'extension/responsys' )}</a></h4>
    </div>
    <div class="panel-collapse collapse" id="collapse{$id}">
        <div class="panel-body u-padding-a-n">
            <pre class="no-border u-margin-b-n">{$content|wash}</pre>
        </div>
    </div>
</div>
{/default}