#!/usr/bin/php
<?php
# Copyright (C) 2018-2024 Valerio Bozzolan, contributors
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace itwikidelbot;

// autoload classes
require __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'autoload.php';

use cli\Log;
use \Throwable;

// Load credentials.
// If the file does not exist, a wizard starts.
config_wizard( __DIR__ . DIRECTORY_SEPARATOR . 'config.php' );

// the bot must be in sync with the italian community
date_default_timezone_set( Page::COMMUNITY_TIMEZONE );

// allowed only from command line interface
if( ! isset( $argv[ 0 ] ) ) {
	exit( 1 );
}

// command line arguments
$opts = getopt( 'h', [
	'ask',
	'days:',
	'from:',
	'help',
	'minutes-ago:',
	'verbose',
] );

// help message
if( isset( $opts[ 'help' ] ) || isset( $opts[ 'h' ] ) ) {
	printf( "usage: %s [OPTIONS]\n", $argv[ 0 ] );
	echo "    --days=DAYS        how many days to be processed (default: 1)\n";
	echo "    --from=YYYY-MM-DD  starting date (default: today)\n";
	echo "    --minutes-ago=N    quits if the last edit was below N minutes ago (default: 5)\n";
	echo "    --ask              ask before saving\n";
	echo "    --verbose          verbose mode\n";
	echo " -h --help             show this help and exit\n";
	exit( 0 );
}

// days to be processed from now to the past
$DAYS = isset( $opts[ 'days' ] )
	? (int) $opts[ 'days' ]
	: 1;

// starting date formatted as 'YYYY-MM-DD'
$DATE = isset( $opts[ 'from' ] )
	? $opts[ 'from' ]
	: 'now';

// minimum last edit age
$MINUTES = isset( $opts[ 'minutes-ago' ] )
	? (int) $opts[ 'minutes-ago' ]
	: 5;

// ask for every edit
if( isset( $opts[ 'ask' ] ) ) {
	Page::$ASK_BEFORE_SAVING = true;
}

// verbose mode
if( isset( $opts[ 'verbose' ] ) ) {
	Log::$DEBUG = true;
}

Log::info( 'start' );

try {
	$bot = Bot::createFromString( $DATE );
	if( $MINUTES < 1 || $bot->isLasteditOlderThanMinutes( $MINUTES ) ) {
		for( $i = 0; $i < $DAYS; $i++ ) {
			$bot->run()->previousDay();
		}
	} else {
		// do not insist
		Log::info( sprintf(
			'skip: someone running less than %d minutes ago',
			$MINUTES
		) );
	}
} catch( Throwable $e ) {
	// Log the exact time of this crash and throw it as-is
	Log::error( 'unexpected crash:' );
	throw $e;
}

Log::info( 'end' );
