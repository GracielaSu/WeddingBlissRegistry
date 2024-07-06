(function (window, $) {
	'use strict';

	// Cache document for fast access.
	var document = window.document;


	$('a.toggle-menu').click(function(){
        $('ul.menu').fadeToggle("slow");
    });

    var owl = $("#owl-demo");
 
	owl.owlCarousel({
    	items : 3,
    	autoPlay : 5000
	});
 
	// Custom Navigation Events
	$(".next").click(function(){
    	owl.trigger('owl.next');
	})
	$(".prev").click(function(){
    	owl.trigger('owl.prev');
	})

	function getCookie(name) {
	  const value = `; ${document.cookie}`;
	  const parts = value.split(`; ${name}=`);
	  if (parts.length === 2) return parts.pop().split(';').shift();
	}

	//Check login status and display appropriate header
    if (getCookie('sessionToken')) {
        $('#btnLogout').show();
        $('#btnAccountSettings').show();
        $('#btnLogin').hide();
        $('#btnSignUp').hide();
    }
    else {
    	$('#btnLogout').hide();
        $('#btnAccountSettings').hide();
        $('#btnLogin').show();
        $('#btnSignUp').show();
    }

    $('#btnLogin').on('click', function () {
        window.location.href = "/login.html";
    });

	$('#btnSignUp').on('click', function () {
        window.location.href = "/signup.html";
    });

	$('#btnLogout').on('click', function () {
		//Delete the session token and user information
		document.cookie = "sessionToken=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
		document.cookie = "userID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
		document.cookie = "userType=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        window.location.href = "/";
    });

	$('#btnAccountSettings').on('click', function () {
		if (getCookie('userType') == "attendee") {
        	window.location.href = "/attendee";
    	}
    	else if (getCookie('userType') == "wedding") {
    		window.location.href = "/wedding";
    	}
    	else {
    		//Delete the session token and user information on invalid user type
			document.cookie = "sessionToken=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
			document.cookie = "userID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
			document.cookie = "userType=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
	        window.location.href = "/";
    	}
    });

})(window, jQuery);

function getAttendees() {
    document.cookie = "userType=attendee; path=/";
    document.cookie = "userID=1; path=/";
    document.cookie = "sessionToken=test; path=/"; 
    console.log(document.cookie);
    $.get("http://localhost/api/?attendee", function (data) {
        $( ".result" ).html( data );
        alert( "Load was performed." );
    });
}




