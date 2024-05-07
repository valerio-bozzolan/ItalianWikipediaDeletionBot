<?php
# Copyright (C) 2018-2024 Valerio Bozzolan
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

// autoload local classes
spl_autoload_register( function( $name ) {
	$prefix = substr( $name, 0, 13 );
	if( 'itwikidelbot\\' === $prefix ) {
		$name = substr( $name, 13 );
		$path = __DIR__ . DIRECTORY_SEPARATOR . "class-$name.php";
		if( ! file_exists( $path ) ) {
			throw new Exception( "missing class $name" );
		}
		require __DIR__ . DIRECTORY_SEPARATOR . "class-$name.php";
	}
} );

// autoload boz-mw classes
require __DIR__ . DIRECTORY_SEPARATOR . 'boz-mw' . DIRECTORY_SEPARATOR . 'autoload-with-laser-cannon.php';
