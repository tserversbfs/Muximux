var boxshadowprop, tabColor, isMobile, overrideMobile, hasDrawer, color, themeColor, tabs, activeTitle, splashNavBtnColor, rss, rssUrl, secret;
var debugDD = false;
jQuery(document).ready(function($) {
    hasDrawer = ($('#autohide-data').attr('data') === 'true');
    showSplash = ($('#splashscreen-data').attr('data') === 'true');
    authentication = $('#authentication-data').attr("data");
    tabColor = ($("#tabcolor-data").attr('data') === 'true');
    themeColor = $("#color-data").attr('data');
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
    $('.sliderInput').bootstrapSlider({
        formatter: function(value) {
            return 'Current value: ' + value;
        },
        tooltip: 'show'
    });

    splashNavBtnColor = $('.splashNavBtn').css('background');
    overrideMobile = false;
    tabs = $('.cd-tabs');
    splashBtn = $('.splashBtn');
    activeTitle = $('li .selected').attr("data-title");
    $('.logo').find('path').css('fill', themeColor);
    if (tabColor) {
        $('.colorDiv').show();
    } else {
        $('.colorDiv').hide();
    }
    if (showSplash) {
        $('.rssGroup').show();
        if (!authentication === 'login') {
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
    $('.inputdiv').css('display', (authentication !== 'off' ? 'block' : 'none'));
    setTitle(activeTitle);
    //get appropriate CSS3 box-shadow property
    boxshadowprop = getsupportedprop(['boxShadow', 'MozBoxShadow', 'WebkitBoxShadow'])
    //Hide the nav to start
    $('.drop-nav').toggleClass('hide-nav');

    // Listeners should go here:

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

    $(".main-nav").find("li").click(function (e) {
        $(this).css("background-color", e.type === "mouseenter" ? themeColor : 'transparent')
    })

    $('.tooltip').click(function() {
        console.log("Clicked");
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

    $("#splashLogout","#logout").click(function () {
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
    $('.selector-button').click(function() {
        var popup = $('this').siblings('.selector-popup');
        if (popup.css("display") !== "none") console.log("It's showing again.");
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
            ["#ef9a9a","#e57373","#ef5350","#f44336","#e53935","#d32f2f","#c62828","#b71c1c"],
            ["#f48fb1","#f06292","#ec407a","#e91e63","#d81b60","#c2185b","#ad1457","#880e4f"],
            ["#ce93d8","#ba68c8","#ab47bc","#9c27b0","#8e24aa","#7b1fa2","#6a1b9a","#4a148c"],
            ["#b39ddb","#9575cd","#7e57c2","#673ab7","#5e35b1","#512da8","#4527a0","#311b92"],
            ["#9fa8da","#7986cb","#5c6bc0","#3f51b5","#3949ab","#303f9f","#283593","#1a237e"],
            ["#90caf9","#64b5f6","#42a5f5","#2196f3","#1e88e5","#1976d2","#1565c0","#0d47a1"],
            ["#81d4fa","#4fc3f7","#29b6f6","#03a9f4","#039be5","#0288d1","#0277bd","#01579b"],
            ["#80deea","#4dd0e1","#26c6da","#00bcd4","#00acc1","#0097a7","#00838f","#006064"],
            ["#80cbc4","#4db6ac","#26a69a","#009688","#00897b","#00796b","#00695c","#004d40"],
            ["#a5d6a7","#81c784","#66bb6a","#4caf50","#43a047","#388e3c","#2e7d32","#1b5e20"],
            ["#c5e1a5","#aed581","#9ccc65","#8bc34a","#7cb342","#689f38","#558b2f","#33691e"],
            ["#e6ee9c","#dce775","#d4e157","#cddc39","#c0ca33","#afb42b","#9e9d24","#827717"],
            ["#fff59d","#fff176","#ffee58","#ffeb3b","#fdd835","#fbc02d","#f9a825","#f57f17"],
            ["#ffe082","#ffd54f","#ffca28","#ffc107","#ffb300","#ffa000","#ff8f00","#ff6f00"],
            ["#ffcc80","#ffb74d","#ffa726","#ff9800","#fb8c00","#f57c00","#ef6c00","#e65100"],
            ["#ffab91","#ff8a65","#ff7043","#ff5722","#f4511e","#e64a19","#d84315","#bf360c"],
            ["#bcaaa4","#a1887f","#8d6e63","#795548","#6d4c41","#5d4037","#4e342e","#3e2723"],
            ["#eeeeee","#e0e0e0","#bdbdbd","#9e9e9e","#757575","#616161","#424242","#212121"],
            ["#b0bec5","#90a4ae","#78909c","#607d8b","#546e7a","#455a64","#37474f","#263238"]
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
                if (srcUrl !== undefined && srcUrl !== "") {
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
            selectedItem.dblclick(function () {
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
        if ($(this).hasClass("settingInput") && loaded) {
            id = $(this).attr('id');
            var section = $(this).data('section');
            var id = $(this).data('attribute');
            var value;
            if (($(this).attr('type') === 'checkbox') || (($(this).attr('type') === 'radio') && id !== 'authentication')) {
                value = $(this).is(':checked');
            } else {
                value = $(this).val();
                if ($(this).attr('type') === 'radio') {
                    value = $("input[name='auths']:checked").attr('id');
                }
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
            if (id === 'default') {
                value = section;
                section = 'general';
            }
            if ((id === 'color') && (section === 'general')) {
                console.log("General effing color...");
                themeColor = value;
                setSelectedColor();
            }
            if (id === 'authentication') {
                authentication = value;
                if (authentication !== 'off') {
                    $('a#logout').parent('li').removeClass('hidden');
                } else {
                    $('a#logout').parent('li').addClass('hidden');
                }

            }
            console.log("Section: " + section + ", ID: " + id + ", Value: " + value);
            $.get('muximux.php?secret=' + secret, {section: section, id: id, value: value}, function () {

            });
            updateElements(section, id,value);
        }
    });

    var radioButtons = $('.btn-rdo');
    radioButtons.click(function() {
        radioButtons.removeClass('active');
        $(this).addClass('active');
        $('.defaultInput').checked = false;
        $(this).children('radio').checked = true;
    })

    $('.sliderInput').on('slideStop',function() {
        console.log("Sliding stopped;")
        id = $(this).attr('id');
        var section = $(this).data('section');
        var id = $(this).data('attribute');
        console.log("Section: " + section + ", ID: " + id);
        var value;
        value = $(this).val();
        $.get('muximux.php?secret=' + secret, {section: section, id: id, value: value}, function () {

        });
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
            var listWidth = $(".main-nav").width();
            var navSpace = barWidth - listWidth;
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
    var selected = getSelected();
    selected.children("span").css("color", "");
    selected.css("color", "");
    selected.css("Box-Shadow", "");
}

// Add relevant color value to tabs
function setSelectedColor() {
    var selectedTab = getSelected();
    color = ((tabColor) && (selectedTab.length === 0) ? selectedTab.data("color") : themeColor);
    $('.droidtheme').replaceWith('<meta name="theme-color" class="droidtheme" content="' + color + '" />');
    $('.mstheme').replaceWith('<meta name="msapplication-navbutton-color" class="mstheme" content="' + color + '" />');
    $('.iostheme').replaceWith('<meta name="apple-mobile-web-app-status-bar-style" class="iostheme" content="' + color + '" />');

    console.log("Theme: " + themeColor + ", tabColor: " + tabColor);
    $('.logo path').css('fill',themeColor + ' !important');
    $('.splashNav .btn').css('border-color',themeColor + ' !important');
    $('.card').css('border','1px solid ' + themeColor + ' !important');

    // Quit here if there's no selected item.
    if (selectedTab.length === 0) return;
    var isddItem = selectedTab.parents('ul.drop-nav').length;

    if ((isMobile && !overrideMobile) || isddItem) {
        console.log("Should be setting a dd item color: "+ color);
        $(".cd-tabs-bar").removeClass("drawer drawerItem");
        $('.cd-tab').removeClass('drawerItem');
        $('.navbtn').removeClass('drawerItem');
        selectedTab.children("span").css("color", "" + color + "");
        selectedTab.css('color', color);
    } else {
        console.log("Should be setting a nav item color: " + color);
        var colString = "inset 0 5px 0 " + color + " !important";
        var colString2 = "Box-Shadow: inset 0 5px 0 " + color + " !important";
        var selected = getSelected();
        selected.attr("style",colString2);
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
        var drawerItem = $('.drawerItem');
        drawerItem.mouseleave(function() {
            drawerItem.removeClass('full');
        });
        drawerItem.mouseenter(function() {
            $('.drawerItem').addClass('full');
        });
    }
}
