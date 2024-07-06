(function (window, $) {
	'use strict';

	// Cache document for fast access.
	var document = window.document;

	function getCookie(name) {
	  const value = `; ${document.cookie}`;
	  const parts = value.split(`; ${name}=`);
	  if (parts.length === 2) return parts.pop().split(';').shift();
	}

	//Check login status and redirect if already logged in
    if (getCookie('userID')) {
    	window.location.href = "/"   
    }

    $('#submitLogin').on('click', function () {
        var email = $('#emailField').val();
        var password = $('#passwordField').val();

        $.ajax({
		    type:     "POST",
		    url:      "http://localhost/api/login",
		  	data:     {"email": email, "password": password},
		  	success: function (data) {
	    		document.cookie = "userType=" + data.userType + "; path=/";
    			document.cookie = "userID=" + data.userID + "; path=/";
    			document.cookie = "sessionToken=" + data.sessionToken + "; path=/"
    			if (data.userType == 'wedding') {
    				window.location.href = "/shop.html";
    			}
    			else {
    				window.location.href = "/shopRegistry.html";
    			}
		   	},
		  	error:   function(jqXHR, textStatus, errorThrown) {
			    alert("Error, status = " + textStatus + ", " +
			    "error thrown: " + jqXHR.responseJSON.response
			    );
			}
		});
    });

})(window, jQuery);



