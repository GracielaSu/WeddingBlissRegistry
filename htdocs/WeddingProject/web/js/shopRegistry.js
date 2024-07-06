(function (window, $) {
	'use strict';

	// Cache document for fast access.
	var document = window.document;
	var currentUrl = new URL(window.location);
	var urlParams = new URLSearchParams(window.location.search);

	function getCookie(name) {
	  const value = `; ${document.cookie}`;
	  const parts = value.split(`; ${name}=`);
	  if (parts.length === 2) return parts.pop().split(';').shift();
	}

	function getStars(score) {
	  if (score >= 4.5) {
	  	return "&starf;&starf;&starf;&starf;&starf;"
	  }
	  else if (score >= 3.5) {
	  	return "&starf;&starf;&starf;&starf;&star;"
	  }
	  else if (score >= 2.5) {
	  	return "&starf;&starf;&starf;&star;&star;"
	  }
	  else if (score >= 1.5) {
	  	return "&starf;&starf;&star;&star;&star;"
	  }
	  else if (score >= 0.5) {
	  	return "&starf;&star;&star;&star;&star;"
	  }
	  else {
	  	return "&star;&star;&star;&star;&star;"
	  }
	}
	
	if (getCookie('userType') != 'attendee') {
		window.location.href = "/";
	}

	//Set page from URL params
	if (urlParams.get('searchString')) {
		$("#itemSearch").val(urlParams.get('searchString'));
	}

	if (urlParams.get('categories')) {
		var categories = urlParams.get('categories').split(',');
		categories.forEach((category) => {
			$("#" + category + "-checkbox").prop( "checked", true );
		})
	}

	if (urlParams.get('sortBy')) {
		var sortOptions = $("#searchOrder>option").map(function() { return $(this).val(); }).toArray();

		//Reset to review_scores if the search sort param does not exist
		if (!sortOptions.includes(urlParams.get('sortBy'))) {
			currentUrl.searchParams.set("sortBy", "review_scores");
			window.location.href = currentUrl.href;
		}
		else {
			$("#searchOrder").val(urlParams.get('sortBy')).change();
		}
	}

	if (!urlParams.get('weddingID')) {
		window.location.href = "/";
	}

	if (getCookie('userType') == 'attendee') {
		$.getJSON("http://localhost/api/itemsRegistry/getItems" + location.search, function (data) {
	        data.itemsList.forEach((item) => {
	        	$(".product-content").append([
	        		$('<a/>', { "href": "/itemsRegistry.html?id="+item.item_id+"&weddingID="+urlParams.get('weddingID')+"&registryID="+item.registry_id }).append([
	        			$('<div/>', { "class": "product-item" }).append([
	        				$('<div/>', { "class": "product-thumb" }).append(
	        					$('<img/>', { "src": item.item_images })
	        				),
	        				$('<div/>', { "class": "product-title" }).append(
	        					item.item_name
	        				),
	        				$('<div/>', { "class": "product-price" }).append(
	        					"$" + item.price
	        				),
	        				$('<div/>', { "class": "product-score" }).append(
	        					getStars(item.review_scores)
	        				),
	        				$('<button/>', { "class": "product-button" }).append(
	        					" See Details > "
	        				)
	        			])
	        		])
	        	]);
	        });

	        //Add page numbers after getting list of items
        	var pagesToAdd = [];

        	var newPageUrl = new URL(window.location);

        	for (let i = 0; i < data.numPages; i++){
        		newPageUrl.searchParams.set("page", i + 1);
			    var pageObject = $('<a/>', { "href": newPageUrl.href }).append(i + 1);
			    if (i + 1 == urlParams.get('page') || !urlParams.get('page') && i == 0) {
			    	pageObject.addClass("disabled")
			    }
			    pagesToAdd.push(pageObject);
			}

        	$(".product-content").parent().after(
        		$('<div/>', { "class": "product-content" }).append(
        			$('<div/>', { "class": "product-pages-container" }).append(pagesToAdd)
        		)
        	)
	    });
	}

	$('#searchSubmit').on('click', function () {
		currentUrl.searchParams.set("searchString", $("#itemSearch").val());

		let checkedCategories = (function() {
		    let a = [];
		    $(".search-item-checkbox input:checked").each(function() {
		        a.push(this.id.replace('-checkbox',''));
		    });
		    return a;
		})()
		currentUrl.searchParams.set("categories", checkedCategories.join(","))

	    currentUrl.searchParams.set("sortBy", $("#searchOrder option:selected").val());
	    currentUrl.searchParams.delete("page");
	    
	    window.location.href = currentUrl.href;
	});

})(window, jQuery);
