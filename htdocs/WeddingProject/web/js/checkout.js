(function (window, $) {
	'use strict';

	// Cache document for fast access.
	var document = window.document;
	var urlParams = new URLSearchParams(window.location.search);

	function getCookie(name) {
	  const value = `; ${document.cookie}`;
	  const parts = value.split(`; ${name}=`);
	  if (parts.length === 2) return parts.pop().split(';').shift();
	}

	$("#checkout-price").html("Price: $"+urlParams.get('price'))

    $('#submitCheckout').on('click', function () {
    	var paymentDetails = {}
        paymentDetails.cardName = $('#cardNameField').val();
        paymentDetails.cardAddress = $('#cardAddressField').val();
        paymentDetails.cardNumber = $('#cardNumberField').val();
        paymentDetails.cardExpiration = $('#cardExpirationField').val();

        var itemDetails = {}
        itemDetails.registry_id = urlParams.get('registryID');       
		itemDetails.item_id = urlParams.get('itemID');
		itemDetails.wedding_id = urlParams.get('weddingID');
		itemDetails.price = urlParams.get('price');

        $.ajax({
		    type:     "POST",
		    url:      "http://localhost/api/checkout/checkout",
		  	data:     {"attendeeID": getCookie('userID'), "paymentDetails": paymentDetails, "itemDetails": itemDetails},
		  	success: function (data) {
		  		alert("Item successfully purchased");
    			window.location.href = "/shopRegistry.html?weddingID="+urlParams.get('weddingID');
		   	},
		  	error:   function(jqXHR, textStatus, errorThrown) {
		  		console.log(jqXHR)
			    alert("Error, status = " + textStatus + ", " +
			    "error thrown: " + jqXHR.responseJSON.response
			    );
			}
		});
    });

})(window, jQuery);



