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

    $('#submitSignUp').on('click', function () {
    	var signupData = {}
        signupData.email = $('#emailField').val();
        signupData.password = $('#passwordField').val();
        signupData.accountInfo = {};

        if (!$('#accountToggle').is(':checked')) {
        	signupData.accountInfo.accountType = 'wedding';
        	signupData.accountInfo.weddingName = $('#weddingNameField').val();
        	signupData.accountInfo.weddingDate = $('#weddingDateField').val();
        	signupData.accountInfo.weddingAddress= $('#weddingAddressField').val();
        }
        else {
        	signupData.accountInfo.accountType = 'attendee';
        	signupData.accountInfo.attendeeName = $('#attendeeNameField').val();
        }
        console.log(signupData)

        $.ajax({
		    type:     "POST",
		    url:      "http://localhost/api/signup",
		  	data:     signupData,
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

    $('#accountToggle').on('click', function () {
    	if ($('#accountToggle').is(':checked')) {
    		$("label[for='wname']").hide();
    		$("#weddingNameField").hide();
    		$("label[for='wdate']").hide();
    		$("#weddingDateField").hide();
    		$("label[for='waddress']").hide();
    		$("#weddingAddressField").hide();
    		$("label[for='aname']").show();
    		$("#attendeeNameField").show();
    	}
    	else {
    		$("label[for='wname']").show();
    		$("#weddingNameField").show();
    		$("label[for='wdate']").show();
    		$("#weddingDateField").show();
    		$("label[for='waddress']").show();
    		$("#weddingAddressField").show();
    		$("label[for='aname']").hide();
    		$("#attendeeNameField").hide();
    	}
    })

})(window, jQuery);



