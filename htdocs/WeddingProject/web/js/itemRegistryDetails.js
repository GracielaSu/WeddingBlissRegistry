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

	if (getCookie('userType') != 'attendee') {
		window.location.href = "/";
	}

	var price = 0;

    $.getJSON("http://localhost/api/items/getItemDetails" + location.search, function (data) {
    	if (data == "Item was not found") {
    		window.location.href = "/notfound.html";
    	}

        $( ".product-detail-title" ).html( data.item_name );
        $( ".product-price" ).html( data.price );
        $( ".product-detail-description" ).html( data.item_description );
        $( ".product-detail-category" ).html( data.item_category );
        $('.product-image img').attr('src', data.item_images);
        price = data.price;
    });

    $.getJSON("http://localhost/api/wedding/getWeddingDetails" + location.search, function (data) {
        $('.product-detail-wedding').html("For the wedding of: " + data[0].wedding_name);
    });

    $.getJSON("http://localhost/api/registry/checkIfItemPurchased" + location.search, function (data) {
    	if (data.response) {
    		$('#purchase').hide();
    		$('#alreadyPurchased').show();
    	}
    	else {
    		$('#purchase').show();
    		$('#alreadyPurchased').hide();
    	}
    });

	$('#purchase').on('click', function () {
	    window.location.href = "/checkout.html?registryID="+urlParams.get('registryID')+"&itemID="+urlParams.get('id')+"&weddingID="+urlParams.get('weddingID')+"&price="+price;
	});

})(window, jQuery);
