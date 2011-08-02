<h3>Billing</h3>

<p>
	<label for="companyname">Company/organization Name</label>
	<input type="text" name="company" value="%company%"/>
	<br/>
</p>
<p>
	<label for="contactname">First Name</label>
	<input type="text" name="firstname" value="%firstname%"/>
	<br/>
</p>
<p>
	<label for="contactname">Last Name</label>
	<input type="text" name="lastname" value="%lastname%"/>
	<br/>
</p>
<p>
	<label for="address">Address</label>
	<input type="text" name="address1" value="%address1%"/>
	<br/>
</p>
<p>
	<label for="address">Address Line 2</label>
	<input type="text" name="address2" value="%address2%"/>
	<br/>
</p>
<p>
	<label for="city">City</label>
	<input type="text" name="city" value="%city%"/>
	<br/>
</p>
<p>
	<label for="state">State</label>
	<input type="text" name="state" value="%state%"/>
	<br/>
</p>
<p>
	<label for="zip">Zip Code</label>
	<input type="text" name="zipcode" value="%zipcode%"/>
	<br/>
</p>
<p>
	<label for="phonenumber">Phone Number</label>
	<input type="text" name="phone" value="%phone%"/>
	<br/>
</p>
<p>
	<label for="email">Email</label>
	<input type="text" name="email" value="%email%"/>
	<br/>
</p>
<p>
	<label for="creditcard">Credit Card</label>
	<input type="text" name="card_number"/>
	<br/>
</p>
<p>
	<label>Expiration Date</label>
	<select name="card_expiration_month">
		<option value="0">month</option>
		<option value="1">January</option>
		<option value="2">February</option>
		<option value="3">March</option>
		<option value="4">April</option>
		<option value="5">May</option>
		<option value="6">June</option>
		<option value="7">July</option>
		<option value="8">August</option>
		<option value="9">September</option>
		<option value="10">October</option>
		<option value="11">November</option>
		<option value="12">December</option>
	</select>
	&nbsp;
	<select name="card_expiration_year">
		<option value="0">year</option>
		%year_options%
	</select>
	<br/>
</p>
<p>
	<label>Card Verification Code</label>
	<input type="text" name="card_code"/>
	<br/>
</p>