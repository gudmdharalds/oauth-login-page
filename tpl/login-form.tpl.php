<form action="/" method="POST">

<h1>%h1_caption%</h1>

<img src="%image_page%">

<p id="error_msg">%error_msg%</p>

<!-- Scope information -->
<div>

The site at <b>%client_uri%</b> is requesting:

<ul>
%scope_list%
</ul>

Log in to grant access to the above.

</div>

<!-- End scope information -->
<!-- Login fields -->
<p>
Username:	<input type="text" name="username" value="">
</p>

<p>
Password:	<input type="password" name="password" value="">
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

