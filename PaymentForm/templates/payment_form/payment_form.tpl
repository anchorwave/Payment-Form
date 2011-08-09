<div id="payment_form_wrapper">
	%header%
	%error%
	%title%
	<form method="POST" action="">
		%extra_header%
		%products%
		%total%
		%billing%
		%extra_footer%
		<input type="hidden" name="form_id" value="%form_id%"/>
		<input type="submit" name="submit" value="Submit Payment"/>
	</form>
	%footer%
</div>