<div id="payment_form_wrapper">
	%header%
	%error%
	%title%
	<form method="POST" action="">
		%products%
		%total%
		%billing%
		%nonce%
		<input type="hidden" name="form_id" value="%form_id%"/>
		<input type="submit" name="submit" value="Submit Payment"/>
	</form>
	%footer%
</div>