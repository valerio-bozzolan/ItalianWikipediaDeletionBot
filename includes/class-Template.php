<?php
# Copyright (C) 2018, 2019, 2020, 2021 Valerio Bozzolan
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

/**
 * Handle templates of text
 *
 * Template files are 'something.template' into the /template directory
 */
class Template {

	/**
	 * Placeholder used to mark where the template content starts
	 */
	const START_PLACEHOLDER = "<!-- START TEMPLATE -->\n";

	/**
	 * Get the content of a template passing its arguments
	 *
	 * @param $name string Template name
	 * @param $args array Template arguments
	 * @return string Template content
	 */
	static function get( $name, $args = [] ) {

		// remove spaces
		$name = str_replace( ' ', '_', $name );

		// ../templates/$name.template
		$path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name . '.tpl';
		if( ! file_exists( $path ) ) {
			throw new \InvalidArgumentException( "unexisting template $name" );
		}

		// template content with also documentation
		$content = file_get_contents( $path );

		// stripping documentation etc.
		$pos = strpos( $content, self::START_PLACEHOLDER );
		if( false === $pos ) {
			throw new \Exception( 'missing header in template' );
		}
		$pos += strlen( self::START_PLACEHOLDER );
		$content = substr( $content, $pos );

		// text-editors usually put an unuseful newline before the EOF
		$content = rtrim( $content );

		// pass arguments
		$s = vsprintf( $content, $args );

		if( false === $s ) {
			throw new \Exception( "wrong number of arguments for template $name" );
		}

		return $s;
	}
}
