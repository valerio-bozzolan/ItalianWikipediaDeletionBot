<?php
# Copyright (C) 2018-2026 Valerio Bozzolan
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

/**
 * Refresh every minute
 */
function please_refresh( $seconds = 20 ) {
	header( "Refresh: $seconds" );
}

/**
 * Return the latest $max_lines from a $filename
 *
 * @param $filename string File name path
 * @param $max_lines int Maximum number of lines to be read
 */
function tail( $filename, $max_lines ) {
	$fp = fopen( $filename, 'r' );
	fseek( $fp, -1, SEEK_END );
	$s = '';
	$n = 0;
	$pos = ftell( $fp );
	while( $pos > 0 ) {
		$c = fgetc( $fp );
		if( "\n" === $c && $n++ > $max_lines ) {
			break;
		}
		$s = $c . $s;
		fseek( $fp, $pos-- );
	}
	fclose( $fp );
	return rtrim( $s );
}
