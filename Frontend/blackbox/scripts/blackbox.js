/////////////////////////////
// Aurellia Industries     //
// 6/24/2021               //
// Main page controller    //
/////////////////////////////

/////////////////////////////
// Config

const MIN_WIDTH = 700
const MIN_HEIGHT = 650
const RANDOM_MATERIAL_ENDPOINT = "https://l-uca.com/ZarchiveZ/api/web/home/tip.php"
const ANIMATION_DURATION = 150
const REDIRECT_ENDPOINT = ""
const PURCHASING_ENDPOINT = ""

/////////////////////////////
// Globals

var widthViolated = false
var paying = false
var GET
var a = false

/////////////////////////////
// Main

function pageLoad() {
    getGet() // :)
    managePageSize() // Call once to make sure it meets minimums
    onPageResize( () => { // Call everytime dimensions change
        managePageSize()
    })
    hookPageContentGeneration() // start materials check
    hookPagePurchasing()
    hookSubscribing()
    setTimeout( () => {
        hideRows()
        hookScroll()
    }, 150)
}

/////////////////////////////
// Functions

function displayResponse(fail, msg) {
    var x = $(".purchaseResponse")
    if(!fail) x.css("background-color", "#5aff79").find("h3").css("color", "black")
    else x.css("background-color", "#EE6C4D").find("h3").css("color", "white")
    x.find("h2").text(msg)
    x.fadeIn().delay(2500).fadeOut()
    a = false
}

function hookSubscribing() {
    var s = $("#subscribe")
    s.mouseup( () => {
        if(a) return
        a = true
        $.ajax({
            method: "POST",
            url: PURCHASING_ENDPOINT,
            data: JSON.stringify({"user_id": $("#discord_id").text(), "token":$("#purchase_token").text()}),
            complete: r => {
                if(!("responseJSON" in r)) {
                    console.log("Malformed response:", r)
                    return
                }
                d = r["responseJSON"]
                if(!("status" in d) || !("code" in d)) {
                    console.log("ERROR: unknown response format", d)
                    return
                }
                if(d["status"].toLowerCase() == "success") displayResponse(false, "Sucessfully subscribed!")
                else displayResponse(true, "Hm... something went wrong!\n\"" + d["code"] + "\"")
            }
        })
    })
}

function getGet() {
    GET = {};
    if(document.location.toString().indexOf('?') !== -1) {
        var query = document.location
                    .toString()
                    // get the query string
                    .replace(/^.*?\?/, '')
                    // and remove any existing hash string (thanks, @vrijdenker)
                    .replace(/#.*$/, '')
                    .split('&');

        for(var i=0, l=query.length; i<l; i++) {
        var aux = decodeURIComponent(query[i]).split('=');
        GET[aux[0]] = aux[1];
        }
    }
}

function hasSecureToken() {
    if($("#purchase_token").text() == "XXX") return false
    else return true
}

function redirect(u) {
    window.location.href = u;
}

function hookPagePurchasing() {
    var pOv = $(".purchaseOverlay")
    pOv.find(".monthly_cost").text($("#monthly_cost").text())
    paying = ("paying" in GET) ? (GET["paying"] == "true") ? true : false : false
    var onPay
    (onPay = () => {
        if(paying) {
            var pOv = $(".purchaseOverlay")
            $(pOv).fadeIn(ANIMATION_DURATION)
            $("#exit").fadeIn(ANIMATION_DURATION)
        }
    })()
    $(".rent").mouseup(() => {
        if(!hasSecureToken()) redirect(REDIRECT_ENDPOINT)
        if(!paying) {
            paying = true
            onPay()
        }
    })
    $("#exit").mouseup(() => {
        if(paying) {
            paying = false
            var pOv = $(".purchaseOverlay")
            $(pOv).fadeOut(ANIMATION_DURATION)
            $("#exit").fadeOut(ANIMATION_DURATION)
        }
    })
}

function hideRows() {
    var x = 0
    $(".additional > *").each( function() {
        $(this).css("visibility", "hidden").addClass("hidden")
    })
}

function hookScroll() {
    $("body").scroll(function(){
        var scrollPos = $(".siteContainer").scrollTop();
        handleScroll(scrollPos)
    });
}


function handleScroll(pos) {
    var top
    $(".additional > *:not(#clone)").each( function() {
        if(isInViewport($(this)[0])) top = $(this)
    })
    if(top == undefined) {
        $("#clone").fadeOut(ANIMATION_DURATION)
        setTimeout(() => {$("#clone").remove()}, ANIMATION_DURATION)
    }
    $(".additional > *:not(#clone)").each( function() {
        if($(this).is($(top))) {
            if($(this).hasClass("hidden")) {
                $(this).removeClass("hidden")
                $("#clone").fadeOut(ANIMATION_DURATION)
                setTimeout( () => {
                    $("#clone").remove()
                    $(this).clone().prependTo($(this).parent()).attr("id", "clone").toggle().css("visibility", "revert").fadeIn(ANIMATION_DURATION)
                }, ANIMATION_DURATION)

            }
        } else {
            if(!($(this).hasClass("hidden"))) {
                $(this).addClass("hidden")

            }
        }
    })
}

// https://www.javascripttutorial.net/dom/css/check-if-an-element-is-visibile-in-the-viewport/

function isInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)

    );
}

function hookPageContentGeneration() {
    var run = () => {
        $.ajax({
            method: "POST",
            url: RANDOM_MATERIAL_ENDPOINT,
            data: JSON.stringify({
                key: "VrrX8QobqFhi68T8DZAbCVTPl-y7VhVGpOLHApoXZ7FZ-bFJYzCjfrkeawosjeIPnklWyF7-1ThmCNf3M8GUGagXZdAG61Frk_Xag4ipi1k7qsIS_nW2REOPr7ZRDUDE8wZ5V7_dzCbiLq9vc0IvZFObrDcvl0S9K1QZqdIGeLI"
            }),
            complete: r => {
                var r = r["responseJSON"]
                var html = $.parseHTML(`<h3>${r["material"]} ${(r["type"] == "buy") ? "going" : "selling" } for ${r["price"]}</h3>`)
                $("#clone.recentTransactions #marketData").prepend($( html ))
                $( html ).hide()
                        .css('opacity',0.0)
                        .slideDown('slow')
                        .animate({opacity: 1.0})
                var html2 = $.parseHTML(`<h3>${r["material"]} ${(r["type"] == "buy") ? "going" : "selling" } for ${r["price"]}</h3>`)
                $(".recentTransactions:not(#clone) #marketData").prepend($( html2 ))
                $( html2 ).hide()
                        .css('opacity',0.0)
                        .slideDown('slow')
                        .animate({opacity: 1.0})
            }
          })
    }
    run()
    setInterval( run, 10000)
}

function onPageResize(toRun) {
    $( window ).resize( () => {toRun()} )
}

function managePageSize() {
    fixJumbo()
    if($( document ).width() <= MIN_WIDTH || $( "html" ).height() <= MIN_HEIGHT) {
        widthViolated = true
        showRestricted(`Please view with a width of at least ${MIN_WIDTH}px and a height of ${MIN_HEIGHT}px.`)
    } else
    if(widthViolated && $( document ).width() > MIN_WIDTH && $( "html" ).height() > MIN_HEIGHT) {
        widthViolated = false
        hideRestricted()
    }
}

function fixJumbo() {
    jumbo = $(".main")
    jumbo.height($("html").innerHeight() - $(".nav").outerHeight())
}

function showRestricted(warning) {
    var curtains = $(".curtains")
    var content = $(".curtains .content h2")
    content.text(warning)
    curtains.fadeIn()
}

function hideRestricted() {
    var curtains = $(".curtains")
    curtains.fadeOut()
}

function fromMainTo(c) {
    if(transitioning) return
    transitioning = true
    $(".right > *").fadeOut( ANIMATION_DURATION, function() {
        $(this).appendTo(".parking").toggle()
        $(".parking").find(`.${c}`).toggle().appendTo(".right")
        $(".right > *").delay(100).fadeIn( ANIMATION_DURATION, () => {
            setTimeout(() => {transitioning = false}, ANIMATION_DURATION)
        })
    })
}

/////////////////////////////
// Start

$(document).ready( () => {
    pageLoad()
})