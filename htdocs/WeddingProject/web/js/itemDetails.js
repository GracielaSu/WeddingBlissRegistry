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

	if (getCookie('userType') != 'wedding') {
		window.location.href = "/";
	}

    $.getJSON("http://localhost/api/items/getItemDetails" + location.search, function (data) {
    	if (data == "Item was not found") {
    		window.location.href = "/notfound.html";
    	}

        $( ".product-detail-title" ).html( data.item_name );
        $( ".product-price" ).html( data.price );
        $( ".product-detail-description" ).html( data.item_description );
        $( ".product-detail-category" ).html( data.item_category );
        $('.product-image img').attr('src', data.item_images);
    });

    $.getJSON("http://localhost/api/registry/checkItem" + location.search + '&wedding_id=' + getCookie('userID'), function (data) {
    	if (data.response) {
    		$('#addToRegistry').hide();
    		$('#removeFromRegistry').show();
    	}
    	else {
    		$('#addToRegistry').show();
    		$('#removeFromRegistry').hide();
    	}
    });

	$('#addToRegistry').on('click', function () {
	    $.ajax({
		    type:     "POST",
		    url:      "http://localhost/api/registry/addItem",
		  	data:     {"item_id": urlParams.get("id")},
		  	success: function (data) {
	    		alert("This item has been added to your registry")
	    		$('#addToRegistry').hide();
    			$('#removeFromRegistry').show();
		   	},
		  	error:   function(jqXHR, textStatus, errorThrown) {
			    alert("Error, status = " + textStatus + ", " +
			    "error thrown: " + jqXHR.responseJSON.response
			    );
			}
		});
	});

	$('#removeFromRegistry').on('click', function () {
	    $.ajax({
		    type:     "POST",
		    url:      "http://localhost/api/registry/removeItem",
		  	data:     {"item_id": urlParams.get("id")},
		  	success: function (data) {
	    		alert("This item has been removed from your registry");
	    		$('#addToRegistry').show();
    			$('#removeFromRegistry').hide();
		   	},
		  	error:   function(jqXHR, textStatus, errorThrown) {
			    alert("Error, status = " + textStatus + ", " +
			    "error thrown: " + jqXHR.responseJSON.response
			    );
			}
		});
	});

})(window, jQuery);
