# Blackbox
**Alpha 1.0**

Blackbox is a framework for facilitating in-game data collection through a network of standalone game clients. By exposing specific API endpoints to the web, the project likely could be expanded to include geniune distributed computing with community participation instead of the current implementation that encourages multiple local computers contributing data; however, in general, the system is prohibitively difficult to run.

**The program is divided into three distinct modules:**
A check mark indicates that the module is provided within this repository; other aspects of the project are still being prepared for publication and are marked with an "X."
 1. In-game bot scripts for collecting data ✅
 1. A dedicated API for persistent data storage ✅
 1. A Discord.js powered Discord bot for community interaction ❌

## In-game bot scripts

The bots are expected to run a dedicated Windows 10 operating systems with minimal configuration. The most difficult step of setup is installing Pytesseract, which the program expects to be extracted to the default location, `C:\Program Files\Tesseract-OCR\tesseract`. All other libraries can be installed with `npm`.

The in-game bot scripts are optimized for a 1920x1080 window resolution; if the bots are operating at a different resolution, you may chooose a different configuration file within the `Blackbox/GameBots/main.py` file under the "config" section. To make a new configuration file, copy the template from `Blackbox/GameBots/config1080p.json` and replace the values with the pixel locations of key regions within the window, using the dictionary terms as references.

## Data API

The PHP-powered API expects POST requests that contain specific field; documentation for each endpoint's requirements, scope, and purpose is provided within the file itself. In later versions, documentation will be provided in a single document, but, until all endpoints are finalized, documentation will continue exist within each file.

The currently provided data API has been stripped down to provide minimal data storage functionality independent from the Discord bot. As more functionality is added to the repository, such as linking Discord accounts to the service, the API will expand to accomodate these changes.

## Discord Bot
*Not yet provided*

The discord bot serves two key functions: (1) the bot listens for community requests and responds with the most recent market data and (2) compares market updates to send near-realtime notifications to paying customers. By comparing the most recent information with historical data, the program is able to identify

1. Top market position gains / losses (changing priority on your order)
1. Fulfilled / relisted orders (quantity changes)
1. Price changes

With this information, the program provides customers with push notifications and analysts with long term trends about the market. Over long periods of time, the program can even  track the approximate amount of items sold over a unit of time, updating users of periods of heightened and diminished trading.
