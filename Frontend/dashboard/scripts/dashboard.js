/////////////////////////////
// Aurellia Industries     //
// 6/24/2021               //
// Main page controller    //
/////////////////////////////

/////////////////////////////
// Config

const MIN_WIDTH = 700
const ANIMATION_DURATION = 500
const LINE_WIDTH = 100 // width of the line used for the progress circle
const CANVAS_SIZE = 2000

/////////////////////////////
// Globals

var widthViolated = false
var currentFocus = "main"
var transitioning = false

/////////////////////////////
// Main

function pageLoad() {
    managePageSize() // Call once to make sure it meets minimums
    onPageResize( () => { // Call everytime dimensions change
        managePageSize()
        setRightBarWidth()
    })
    generateCircles()
    hookPageContent()
}

/////////////////////////////
// External functions

// cool copy function -- from https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript
function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    
    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
  
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
  
    try {
      var successful = document.execCommand('copy');
      var msg = successful ? 'successful' : 'unsuccessful';
      console.log('Fallback: Copying text command was ' + msg);
    } catch (err) {
      console.error('Fallback: Oops, unable to copy', err);
    }
  
    document.body.removeChild(textArea);
  }
  function copyTextToClipboard(text) {
    if (!navigator.clipboard) {
      fallbackCopyTextToClipboard(text);
      return;
    }
    navigator.clipboard.writeText(text).then(function() {
      console.log('Async: Copying to clipboard was successful!');
    }, function(err) {
      console.error('Async: Could not copy text: ', err);
    });
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

function generateCircles() {
    $(".investment").each(function() {
        // fill in basic h2
        var investment = $( this )
        var amount = $( investment ).find("ol.metadata li.amount").text()
        investment.find("h1").text(amount)

        // generate circle
        var percent = $( investment ).find("ol.metadata li.percent").text()
        var days = $( investment ).find("ol.metadata li.days").text()
        generateCircle( $( this ).find(".progress"), amount, percent, days)
    })
}

function generateCircle(parent, amount, percent, days) {
    var headsup = parent
    var potentialHeight = headsup.height() * 1
    var potentialWidth = headsup.width() * 1
    var final
    if(potentialHeight > potentialWidth) {
        $( parent ).find(".displayCircle").height(potentialWidth)
        $( parent ).find(".displayCircle").width(potentialWidth)
        final = potentialWidth  
    } else {
        $( parent ).find(".displayCircle").height(potentialHeight)
        $( parent ).find(".displayCircle").width(potentialHeight)
        final = potentialHeight
    }
    $( parent ).find('.displayCircle').circleProgress({
        value: percent,
        size: CANVAS_SIZE,
        fill: {
          gradient: ['#488286', '#293241'],
        },
        thickness: LINE_WIDTH,
        lineCap: "round",
        emptyFill: 'rgba(0, 0, 0, .0)',
    })
    var line_width_transformed = final * (LINE_WIDTH / CANVAS_SIZE)
    $( parent ).find("canvas").width(final).height(final)
    $( parent ).find(".circleCenter").width(Math.ceil(final - (2 * line_width_transformed + 20)))
    $( parent ).find(".circleCenter").height(Math.ceil(final - (2 * line_width_transformed + 20)))
    $( parent ).find(".circleCenter").find("h2").text(Math.round(percent*100) + "%")
    $( parent ).find(".circleCenter").find("h3").text(days + " days left")
}

function setRightBarWidth() {
    $(".rightSideSkeleton").width($(".rightSideSkeleton").outerWidth(false))
}

function hookPageContent() {
    $("#tid, #bid").on("mouseup", function() {
        fallbackCopyTextToClipboard($ ( this ).text() )
    })
}

/////////////////////////////
// Start

$(document).ready( () => {
    pageLoad()
})