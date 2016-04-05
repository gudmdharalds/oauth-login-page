<form action="/" method="POST">

<h1 id="lp_h1_caption">%h1_caption%</h1>

<img id="lp_image_page" src="%image_page%">

<p id="lp_error_msg">%error_msg%</p>

<!-- Scope information -->
<div id="lp_scope_info">

The site at <b>%client_uri%</b> is requesting:

<ul id="lp_scope_list">
%scope_list%
</ul>

Log in to grant access to the above.

</div>

<!-- End scope information -->
<!-- Login fields -->
<p>
Username:	<input id="lp_field_password" type="text" name="username" value="">
</p>

<p>
Password:	<input id="lp_field_password" type="password" name="password" value="">
</p>

<!-- End login fields -->



<!-- Hidden login form fields -->
<input type="hidden" name="nonce" value="%nonce%">

<input type="hidden" name="response_type" value="%response_type%">
<input type="hidden" name="client_id" value="%client_id%">
<input type="hidden" name="redirect_uri" value="%redirect_uri%">
<input type="hidden" name="scope" value="%scope%">
<input type="hidden" name="state" value="%state%">
<!-- End hidden login form fields -->

<input type="submit" value="Log in">

</form>

