var boxshadowprop, tabColor, isMobile, overrideMobile, colorData, hasDrawer, color, themeColor, tabs, activeTitle, splashNavBtnColor, rss, rssUrl, secret;
var debugDD = false;
jQuery(document).ready(function($) {
    hasDrawer = ($('#autohide-data').attr('data') === 'true');
    showSplash = ($('#splashscreen-data').attr('data') === 'true');
    authentication = ($('#authentication-data').attr("data") === 'true');
    tabColor = ($("#tabcolor-data").attr('data') === 'true');
    secret = $('#secret-data').attr("data");
    rss = ($('#rss-data').attr("data") === 'true');
    $('.rssUrlGroup').css('display', (rss ? 'block' : 'none'));
    dropDown = true;

    $('#pleaseWaitDialog').animate({
        opacity: .25,
        left: "+=300px"
    }, 1200, function () {
        $('#pleaseWaitDialog').hide();
    });
    $('.cd-tabs-bar').show();
    // Custom function to do case-insensitive selector matching
    $.extend($.expr[":"], {
        "containsInsensitive": function (elem, i, match, array) {
            return (elem.textContent || elem.innerText || "").toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
        }
    });

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

    var labelSide = "right";
    console.log("initializing sliders");
    $('.sliderInput').bootstrapSlider({
        formatter: function(value) {
            return 'Current value: ' + value;
        }
    });

    splashNavBtnColor = $('.splashNavBtn').css('background');
    overrideMobile = false;
    tabs = $('.cd-tabs');
    splashBtn = $('.splashBtn');
    activeTitle = $('li .selected').attr("data-title");
    $('.logo').find('path').css('fill', themeColor);
    console.log("Tabcolors are " + (tabColor ? "enabled" : "disabled"));
    if (tabColor) {
        $('.colorDiv').show();
    } else {
        $('.colorDiv').hide();
    }
    if (showSplash) {
        $('.rssGroup').show();
        if (!authentication) {
            $('#splashLogout').addClass('hidden');
        }
        $('#splashModal').modal('show').addClass("in");
        if (rss) {
            rssUrl = $('#rssUrl-data').attr("data");
            //setupFeed(rssUrl, isMobile);
            $('.rssUrlGroup').show();
        } else {
            $('.rssUrlGroup').hide();
        }
    } else {
        $('.rssGroup').hide();
    }

    $('#override').css('display', (isMobile ? 'block' : 'none'));
    $('.inputdiv').css('display', (authentication ? 'block' : 'none'));
    setTitle(activeTitle);
    //get appropriate CSS3 box-shadow property
    boxshadowprop = getsupportedprop(['boxShadow', 'MozBoxShadow', 'WebkitBoxShadow'])
    //Hide the nav to start
    $('.drop-nav').toggleClass('hide-nav');
    splashBtn.on('click', function (event) {
        var selectedBtn = $(this).data('content');
        var selectedBtnTab = $('.cd-tabs-bar').find('a[data-content="' + selectedBtn + '"]');
        selectedBtnTab.click();
        if (isMobile) {
            $('.drop-nav').toggleClass('hide-nav').toggleClass('show-nav');
        }
        $('#splashModal').modal('hide');
    });

    $('li.dd').on('click', function () {
        toggleClasses();
    });

    $('#reload').on('click', function () {
        $('.muximux-refresh').addClass('fa-spin');
        setTimeout(function () {
            $('.muximux-refresh').removeClass('fa-spin');
        }, 3900);
        console.log("Refreshing from reload button.");
        var selectedFrame = $('.cd-tabs-content').find('.selected').children('iframe');
        selectedFrame.attr('src', selectedFrame.attr('src'));
    });
    // Detect click on override button, fire resize
    $('#override').on('click', function () {
        overrideMobile = !overrideMobile;
        muximuxMobileResize();
        if (overrideMobile && isMobile) {
            $('#override').addClass('or-active');
        } else {
            $('#override').removeClass('or-active');
        }
    });

    $("#authenticationCheckbox").click(function () {
        if ($(this).is(":checked")) // "this" refers to the element that fired the event
        {
            $('.inputdiv').slideDown('fast');
        } else {
            $('.inputdiv').slideUp('fast');
        }
    });
    $("#splashscreenCheckbox").click(function () {
        if ($(this).is(":checked")) // "this" refers to the element that fired the event
        {
            $('.rssGroup').slideDown('fast');
        } else {
            $('#rssCheckbox').attr('checked', false);
            $('.rssGroup').slideUp('fast');
            $('.rssUrlGroup').slideUp('fast');

        }
    });

    $("#rssCheckbox").click(function () {
        if ($(this).is(":checked")) // "this" refers to the element that fired the event
        {
            $('.rssUrlGroup').slideDown('fast');
        } else {
            $('.rssUrlGroup').slideUp('fast');
        }
    });

    $(".main-nav").find("li").click(function (e) {
        $(this).css("background-color", e.type === "mouseenter" ? themeColor : 'transparent')
    })

    $(".splashNavBtn").hover(function (e) {
        $(this).css("background-color", e.type === "mouseenter" ? themeColor : splashNavBtnColor)
    })
    $("#splashLog").click(function () {
        $('#logModal').modal('show');
    });

    $('#splashModal').on('show.bs.modal', function () {
        rss = ($('#rss-data').attr("data") === 'true');
        if (rss) {
            rssUrl = $('#rssUrl-data').attr("data");
            $('.tickercontainer').wrap('<div id="feed"/>').contents().unwrap();
            $('#feed').empty();
            //setupFeed(rssUrl, isMobile);
        }
    });


    $("#splashSettings").click(function () {
        setTimeout(function () {
            $('#settingsModal').modal('show');
        }, 100);
    });
    $("#splashLogout").click(function () {
        window.location.href = '?logout';
    });
    $("#logout").click(function () {
        window.location.href = '?logout';
    });
    // When settings modal is open, set title to "Settings"
    $('#settingsModal').on('show.bs.modal', function () {
        setTitle("Settings");
    });
    $('#logModal').on('show.bs.modal', function () {
        setTitle("Log");
        refresh_log();

    });
    $('#refreshLog').on('click', function () {
        $('#logContainer').slideToggle();
        refresh_log();

    });
    // When settings modal closes, set title to the previous title used
    $('.modal').on('hidden.bs.modal', function () {
        var activeTitle = $('.cd-tabs-content').find('.selected').children('iframe').attr("data-title");
        setTitle(activeTitle);
    });
    $(window).on('resize', function () {
        tabs.each(function () {
            var tab = $(this);
            checkScrolling(tab.find('nav'));
            tab.find('.cd-tabs-content').css('height', 'auto');
        });
        resizeIframe(hasDrawer, isMobile); // Resize iframes when window is resized.
        scaleFrames(); // Scale frames when window is resized.
    });
    $('.dd').click(function () {
        dropDownFixPosition($('.dd'), $('.drop-nav'));
    });
    $('#autohideCheckbox').click(function () {
        $('#mobileoverrideCheckbox').prop('checked', false);
    });
    $('#mobileoverrideCheckbox').click(function () {
        $('#autohideCheckbox').prop('checked', false);
    });
    // This triggers a menu close when mouse has left the drop nav.
    $('.dd').mouseleave(function () {
        if (!debugDD) {
            setTimeout(function () {
                if (!($('.drop-nav:hover').length !== 0 || $('.dd:hover').length !== 0)) {

                    $('.drop-nav').addClass('hide-nav');
                    $('.drop-nav').removeClass('show-nav');
                }
            }, 250);
        }
    });

    // Move items to the dropdown on mobile devices
    settingsEventHandlers();
    scaleFrames();
    resizeIframe(hasDrawer, isMobile); // Call resizeIframe when document is ready
    // Load the menu item that is set in URL, for example http://site.com/#plexpy
    if ($(location).attr('hash')) {
        var bookmarkHash = $(location).attr('hash').substr(1).replace("%20", " ").replace("_", " ");
        var menuItem = $(document).find('a:containsInsensitive("' + bookmarkHash + '")');
        menuItem.trigger("click");
    }

    $('#settingsLogo').click(function () {
        window.open('https://github.com/mescon/Muximux', '_blank');
    });
    $(".appsColor").spectrum({
        showInput: true,
        showPalette: true,
        preferredFormat: "hex",
        palette: [
            ["#000", "#444", "#666", "#999", "#ccc", "#eee", "#f3f3f3", "#fff"],
            ["#f00", "#f90", "#ff0", "#0f0", "#0ff", "#00f", "#90f", "#f0f"],
            ["#f4cccc", "#fce5cd", "#fff2cc", "#d9ead3", "#d0e0e3", "#cfe2f3", "#d9d2e9", "#ead1dc"],
            ["#ea9999", "#f9cb9c", "#ffe599", "#b6d7a8", "#a2c4c9", "#9fc5e8", "#b4a7d6", "#d5a6bd"],
            ["#e06666", "#f6b26b", "#ffd966", "#93c47d", "#76a5af", "#6fa8dc", "#8e7cc3", "#c27ba0"],
            ["#c00", "#e69138", "#f1c232", "#6aa84f", "#45818e", "#3d85c6", "#674ea7", "#a64d79"],
            ["#900", "#b45f06", "#bf9000", "#38761d", "#134f5c", "#0b5394", "#351c75", "#741b47"],
            ["#600", "#783f04", "#7f6000", "#274e13", "#0c343d", "#073763", "#20124d", "#4c1130"]
        ]
    });
    $('.sp-replacer').addClass('form-control').addClass('form-control-sm');


    if ($('#branch-changed').attr('data') == 'true') {
        $('#updateContainer').html("<button type='button' id='updateDismiss' class='close pull-right'>&times;</button>" +
            "<span>Branch changed detected. Would you like to install the latest version now?" +
            "<div id='downloadModal'><code>Click here</code></div> to install.</span>").fadeIn("slow");
        $('#downloadModal').click(function () {
            branch = $("#branch-data").attr('data');
            downloadUpdate(branch);
        });
        $('#updateDismiss').click(function () {
            $('#updateContainer').fadeOut("slow");
        });
    }

    $.getJSON('muximux.php?secret=' + secret + '&colors', function (data) {
        console.log("DATA: ", data);
        colorData = data;
    });

    tabs.each(function () {
        var tab = $(this),
            tabItems = tab.find('ul.cd-tabs-navigation, .main-nav'),
            tabContentWrapper = tab.children('ul.cd-tabs-content'),
            tabNavigation = tab.find('nav');


        tabItems.on('click', 'a:not(#reload, #hamburger, #override, #logout, #logModalBtn, #showSplash)', function (event) {
            // Set up menu for desktip view
            var tab = $(this);

            if (!isMobile) {
                $('.drop-nav').addClass('hide-nav').removeClass('show-nav');
            }
            resizeIframe(hasDrawer, isMobile); // Call resizeIframe when document is ready
            event.preventDefault();
            var selectedItem = $(this);
            console.log("CLICK");
            if (!selectedItem.hasClass('selected')) {
                var selectedTab = selectedItem.data('content'),
                    selectedContent = tabContentWrapper.find('li[data-content="' + selectedTab + '"]'),
                    selectedContentHeight = selectedContent.innerHeight();
                var srcUrl = selectedContent.children('iframe').data('src');
                console.log("URL: "+srcUrl);
                if (srcUrl !== undefined || srcUrl !== "") {
                    console.log("Made it to thunderdome.");
                    var protocol = ('https:' === document.location.protocol ? 'https' : 'http');
                    console.log("Protocol is " + protocol);
                    if ((protocol === 'https') && (srcUrl.search(protocol) === -1)) {
                        console.log("Should be replacing with https.");
                        srcUrl = srcUrl.replace("http","https");
                    }
                    console.log("URL: "+srcUrl);
                    if (selectedContent.children('iframe').attr('src') === undefined) {
                        selectedContent.children('iframe').attr('src', srcUrl);
                    }
                }
                // Fix issue with color not resetting on settings close
                if (!(selectedItem.attr("data-title") === "Settings")) {
                    clearColors();
                    tabItems.find('a.selected').removeClass('selected').attr("style", "");
                    selectedItem.addClass('selected');
                    setSelectedColor();
                    // Change window title after class "selected" has been added to item
                    var activeTitle = selectedItem.attr("data-title");
                    setTitle(activeTitle);
                    selectedContent.addClass('selected').siblings('li').removeClass('selected').attr("style", "");
                    // animate tabContentWrapper height when content changes
                    tabContentWrapper.animate({
                        'height': selectedContentHeight
                    }, 200);
                }
            }
            selectedItem.ondblclick(function () {
                selectedContent.children('iframe').attr('src', selectedContent.children('iframe').attr('src'));
            });
        });
        // hide the .cd-tabs::after element when tabbed navigation has scrolled to the end (mobile version)
        checkScrolling(tabNavigation);
        tabNavigation.on('scroll', function () {
            checkScrolling($(this));
        });
    });


    $("input").change(function() {
        var id;
        if ($(this).hasClass("settingInput") && loaded) {
            id = $(this).attr('id');
            var value;
            if (($(this).attr('type') === 'checkbox') || ($(this).attr('type') === 'radio')) {
                value = $(this).is(':checked');
            } else {
                value = $(this).val();
            }
            if (id.search("_-_url" >= 0)) {
                var section = $(this).data("section");
                console.log("Section: "+ section);
                $('li[data-content="' + section + '"]').find('iframe').src(value).data(value);
            }
            if (id === 'authentication') {
                $('.inputdiv').css('display', (value ? 'block' : 'none'));
            }
            if (id === 'rss') {
                $('.rssUrlGroup').css('display', (value ? 'block' : 'none'));
            }
            if (id === 'autohide') {
                hasDrawer = value;
                setSelectedColor();
                resizeIframe(hasDrawer, isMobile);
            }
            if (id === 'mobileoverride') {
                overrideMobile = value;
                setSelectedColor();
                resizeIframe(hasDrawer, isMobile);
            }
            console.log("Sending param: " + id + " value: " + value);

            $.get('muximux.php?secret=' + secret, {id: id, value: value}, function () {

            });
            updateElement(id,value);
        }
    });
    muximuxMobileResize();

});

// $(window).load(function() {
//     if ($('#popupdate').attr('data') == 'true') {
//         var updateCheck = setInterval(updateBox(false), 1000 * 60 * 10);
//     }
// });
// Close modal on escape key
$("html").on("keyup", function(e) {
    if(e.keyCode === 27) {
		$('.keyModal').modal('hide');
	}
});


$(window).resize(muximuxMobileResize);

function muximuxMobileResize() {
	isMobile = ($(window).width() < 800);
	rss = ($('#rss-data').attr("data") === 'true');
	if (rss) {
			$('.tickercontainer').wrap('<div id="feed"/>').contents().unwrap();
			$('#feed').empty();
			rssUrl = $('#rssUrl-data').attr("data");
				//ExsetupFeed(rssUrl, isMobile);
		}
	
    $('#override').css('display', (isMobile ? 'block' : 'none'));
	if (isMobile && !overrideMobile) {
        $('.cd-tabs-navigation nav').children().appendTo(".drop-nav");
        var menuHeight = $(window).height() * .80;
        $('.drop-nav').css('max-height', menuHeight + 'px');
		
    } else {
        if (dropDown) {
            $(".drop-nav").children('.cd-tab').appendTo('.cd-tabs-navigation nav');
            $(".cd-tabs-navigation nav").children('.navbtn').appendTo('.main-nav');
            $('.drop-nav').css('max-height', '');
            var barWidth = $('nav').width();
            console.log("Total space is " + barWidth);
            var listWidth = $(".main-nav").width();
            console.log("Nav width should be " + listWidth);
            var navSpace = barWidth - listWidth;
            console.log("Leaving..." + (navSpace));
            var barFull = false;
			var barArray = getSorted('.cd-tab','data-index');
			barArray.each(function() {
				var myIndex = $(this).attr('data-index');
                var myWidth = $(this).width();
				
                if ((myWidth + listWidth > navSpace) || barFull) {
					barFull = true;
			        $(".drop-nav").insertAt(myIndex,this);
                } else {
            		$('.cd-tabs-navigation nav').insertAt(myIndex,this);
					listWidth = listWidth + $(this).width();
			    }
			});
        }
    }
    clearColors();
    setSelectedColor();
}

function getSorted(selector, attrName) {
    return $($(selector).toArray().sort(function(a, b){
        var aVal = parseInt(a.getAttribute(attrName)),
            bVal = parseInt(b.getAttribute(attrName));
        return aVal - bVal;
    }));
}

jQuery.fn.insertAt = function(index, element) {
    var lastIndex = this.children().length;
    if (index < 0) {
        index = Math.max(0, lastIndex + 1 + index);
    }
    this.append(element);
    if (index < lastIndex) {
        this.children().eq(index).before(this.children().last());
    }
    return this;
}

// Simple method to toggle show/hide classes in navigation
function toggleClasses() {
    $('.drop-nav').toggleClass('hide-nav');
    $('.drop-nav').toggleClass('show-nav');
}
// Clear color values from tabs
function clearColors() {
    var selected = $(".selected");
    selected.children("span").css("color", "");
    selected.css("color", "");
    selected.css("Box-Shadow", "");
}

// Add relevant color value to tabs
function setSelectedColor() {
    color = (tabColor ? $('.selected').attr("data-color") : themeColor);
    var isddItem = $('.selected').parents('ul.drop-nav').length;
    $('.droidtheme').replaceWith('<meta name="theme-color" class="droidtheme" content="' + color + '" />');
    $('.mstheme').replaceWith('<meta name="msapplication-navbutton-color" class="mstheme" content="' + color + '" />');
    $('.iostheme').replaceWith('<meta name="apple-mobile-web-app-status-bar-style" class="iostheme" content="' + color + '" />');

    if ((isMobile && !overrideMobile) || isddItem) {
        console.log("Should be setting a dd item color: "+ color);
        $(".cd-tabs-bar").removeClass("drawer");
        $('.cd-tab').removeClass('drawerItem');
        $('.navbtn').removeClass('drawerItem');
        $('.cd-tabs-bar').removeClass('drawerItem');
        $(".selected").children("span").css("color", "" + color + "");
        $(".selected").css('color', color);
    } else {
        console.log("Should be setting a nav item color: " + color);
        var colString = "inset 0 5px 0 " + color + " !important";
        var colString2 = "Box-Shadow: inset 0 5px 0 " + color + " !important";
        //reStyle('.selected','Box-Shadow',colString);
        $(".selected").attr("style",colString2);
        console.log("BS Property: " + colString);
        // Super hacky, but we're refrencing a placeholder div to quickly see if we have a drawer
        if (hasDrawer) {
            $('.cd-tab').addClass('drawerItem');
            $('.navbtn').addClass('drawerItem');
            $('.cd-tabs-bar').addClass('drawerItem');
        } else {
            $('.cd-tab').removeClass('drawerItem');
            $('.navbtn').removeClass('drawerItem');
            $('.cd-tabs-bar').removeClass('drawerItem');
        }
		jQuery.fn.reverse = [].reverse;
    $('.drawerItem').mouseleave(function() {
		$('.drawerItem').removeClass('full');
    });
    $('.drawerItem').mouseenter(function() {
		$('.drawerItem').addClass('full');
    });
    }
}
