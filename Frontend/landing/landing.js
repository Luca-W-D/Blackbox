/////////////////////////////
// Aurellia Industries     //
// 6/24/2021               //
// Main page controller    //
/////////////////////////////

/////////////////////////////
// Config

const MIN_WIDTH = 700
const RANDOM_MATERIAL_ENDPOINT = "https://l-uca.com/ZarchiveZ/api/web/home/tip.php"
const ANIMATION_DURATION = 500

/////////////////////////////
// Globals

var widthViolated = false
var currentFocus = "main"
var transitioning = false

/////////////////////////////
// Main

function pageLoad() {
    managePageSize() // Call once to make sure it meets minimums
    placeRightColumn();
    onPageResize( () => { // Call everytime dimensions change
        managePageSize()
        placeRightColumn()
    })
    hookPageContentGeneration()
    hookTransitions()
}

/////////////////////////////
// Functions

function onPageResize(toRun) {
    $( window ).resize( () => {toRun()} )
}

function managePageSize() {
    if($( document ).width() <= MIN_WIDTH) {
        widthViolated = true
        showRestricted("Please view with a width of at least 700px.")
    }
    if(widthViolated && $( document ).width() > MIN_WIDTH) {
        widthViolated = false
        hideRestricted()
    }
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

function placeRightColumn() {
    var rT = $(".recentTransactions")
    var l = $( rT ).find(".list")
    var t = $( rT ).find(".label h2")
    l.height( 0.85 * $(".right").height() - t.outerHeight(true) )
    rT.height( l.outerHeight(true) + t.outerHeight(true))
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
                console.log(r)
                var r = r["responseJSON"]
                var html = $.parseHTML(`<h3>${r["material"]} ${(r["type"] == "buy") ? "going" : "selling" } for ${r["price"]}`)
                $(".list").prepend($( html ))
                $( html ).hide()
                        .css('opacity',0.0)
                        .slideDown('slow')
                        .animate({opacity: 1.0})
            }
          })
    }
    run()
    setInterval( run, 10000)
}

function hookTransitions() {
    $(".buttons div.cs").mouseup( () => {
        fromMainTo("cs")
    })
    $(".buttons div.faq").mouseup( () => {
        fromMainTo("faq")
    })
    $(".buttons div.products").mouseup( () => {
        fromMainTo("products")
    })
    $(".info .textWrapper .return").mouseup( () => {
        fromNonuniqueToMain()
    })
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