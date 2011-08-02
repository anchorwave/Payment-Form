jQuery( document ).ready( function( $ ) {

	function PaymentForm() {
	
		/*
		* Tally up the subtotals into a total
		*/
	
		PaymentForm.prototype.getTotal = function() {
			var total = 0;
			var thisobj = this;
			$( '[name^=payment_form_product]' ).each( function( index, elem ) {

				var price = thisobj.getPrice( this );
				var quantity = $(this).val();
				var amount = thisobj.getAmount( price, quantity );

				amount = Number( amount );
				if ( isNaN( amount ) ) amount = 0;
				
				total += amount;
			} );
			return total;
		}
		
		/*
		* Methods to calulate an subtotals
		*/
		
		PaymentForm.prototype.getAmount = function( price, quantity ) {
			var amount;

			if ( price == 0 ) {
				amount = quantity;
			} else {
				amount = price * quantity;
			}
			return amount;
		}
		
		/*
		* Methods to get the price of a product
		*/
		
		PaymentForm.prototype.getPrice = function( elem ) {
			var inputname = $( elem ).attr( 'name' );
			var product_id = this.getProductIdFromInputName( inputname );	
			var price = this.getPriceByProductId( product_id );
			return price;
		}
		
		PaymentForm.prototype.getPriceByProductId = function( product_id ) {
			var selector = '[name="product_price\['+product_id+'\]"]';
			var price = $( selector ).val();
			return price;
		}
		
		PaymentForm.prototype.getProductIdFromInputName = function( inputname ) {
			var pattern = /\[(.*?)\]/;
			var product_id = inputname.match( pattern );
			product_id = product_id[1];
			return product_id;
		}
	
	}

	/*
	* Calculate the total
	*/
	$( '[name^=payment_form_product]' ).bind( 'click change keyup', function() {
		var payment_form = new PaymentForm();
		var total = payment_form.getTotal();
		$( '#payment_form_total' ).html( 'USD ' + total.toFixed( 2 ) );
	} );
	
} );