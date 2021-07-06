# Italian Wikipedia Deletion Bot

This software is an Italian Wikipedia bot. It keeps updated the public deletion logs of Italian Wikipedia pages.

More informations here: https://it.wikipedia.org/wiki/Utente:BotCancellazioni

## Installation

```
sudo apt install git php-cli
git clone --recursive https://gitpull.it/source/ItalianWikipediaDeletionBot.git
```

## Update

```
git pull
git submodule update --init --recursive
```

## Configuration

1. Open the file `config-example.php` with a text editor
2. Fill your bot credentials
3. Save-as `config.php`

## Usage

Run on today:

```
./bot.php
```

Run on the latest 10 days from today:

```
./bot.php --days=10
```

Run on a specific date (year-month-day):

```
./bot.php --from=2018-03-13
```

Run on a specific date and for 10 days before it:

```
./bot.php --days=10 --from=2018-03-13
```

Quit if someone was running the bot just `5` minutes ago (that is the default behaviour):

```
./bot.php --minutes-ago=5
```

Ask before saving and enable verbose mode:

```
./bot.php --ask --verbose
```

Help:

```
./bot.php --help
```

## Update

To fetch the updates:

```
git pull --recurse-submodules
```

## Hacking

Most of the behaviours — as the content of the categories, the pages, their edit summaries, etc. — can be changed simply hacking the content of the files from the [/templates](/templates) directory. Trust me, you are able to do it.

Start becoming familiar with their structure:

```
	<!-- START DOCUMENTATION -->
	This is the place for some documentation, expecially about "placeholders".
	<!-- START SPECIFIC EXAMPLE -->
	This is the place for a specific example
	<!-- START TEMPLATE -->
	This is the place for the most important part of this file.
	This part is what this template will generates.
	It uses stuff like "$1" or "%1%02d" as generic placeholders.
```

To be honest: everything above the `<!-- START TEMPLATE -->` line it's pure documentation sugar. It's written to help you. I've spent some minutes on them. Please RTFM. asd

A non-traumatic template example can be found [here](templates/CATEGORY_YEAR.content.tpl). It describes the generation of the yearly category.

## Framework

You may not want to know this: HTTP connections, MediaWiki APIs (including login and tokens etc.), and other stuff, are handled by the [boz-mw](https://gitpull.it/w/first_steps_with_boz-mw/) framework.

## License

Copyright (C) 2018 [Valerio Bozzolan](https://it.wikipedia.org/wiki/Utente:Valerio_Bozzolan)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.

Original files released under [Creative Commons BY-SA 3.0 International](https://creativecommons.org/licenses/by-sa/3.0/) and [WMF terms](https://wikimediafoundation.org/wiki/Special:MyLanguage/Terms_of_Use/it) by [Mauro742](https://it.wikipedia.org/wiki/Utente:Mauro742)/[MauroBot](https://it.wikipedia.org/wiki/Utente:MauroBot):
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/main.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/bot.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/category.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/dateFunctions.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/core.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/globals.js
* https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/gui.js
