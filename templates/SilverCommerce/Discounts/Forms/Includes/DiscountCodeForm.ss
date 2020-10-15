<% if $IncludeFormTag %>
<form $addExtraClass("form-inline").AttributesHTML>
<% end_if %>
	<% if $Message %>
	<p id="{$FormName}_error" class="message $MessageType col-12">$Message</p>
	<% else %>
	<p id="{$FormName}_error" class="message $MessageType col-12" style="display: none"></p>
	<% end_if %>

	<fieldset class="input-group align-items-end">
		<% if $Legend %><legend>$Legend</legend><% end_if %>
		<% loop $Fields %>
			$FieldHolder
		<% end_loop %>
		<% if $Actions %>
			<div class="input-group-append mb-3 mb-sm-0">
				<% loop $Actions %>
					$Field
				<% end_loop %>
			</div>
		<% end_if %>
	</fieldset>

<% if $IncludeFormTag %>
</form>
<% end_if %>
