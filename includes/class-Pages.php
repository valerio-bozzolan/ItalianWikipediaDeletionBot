<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2018 Valerio Bozzolan
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

use mw\API\PageMatcher;

/**
 * Handle multiple pages
 */
class Pages {

	/**
	 * Get page titles from pages
	 *
	 * @param $pages array
	 * @return array
	 */
	public static function titles( $pages ) {
		return array_map( function ( $page ) {
			return $page->getTitle();
		}, $pages );
	}

	/*
	 * Check if some pages exist
	 *
	 * This method is intended to do less API requests as possible.
	 *
	 * @param $pages array
	 */
	public static function populateWheneverTheyExist( $pages ) {

		// API query to check if these pages exist
		$query = Page::api()->createQuery( [
			'action' => 'query',
			'prop'   => 'info',
			'titles' => self::titles( $pages ),
		] );

		// callback to retrieve the page title
		$page_title_callback = function ( $page ) {
			return $page->getTitle();
		};

		// callback fired for every match between response pages and my pages
		$matching_callback = function ( $response_page, $my_page ) {
			$my_page->setIfExists(
				! isset( $response_page->missing )
			);
		};

		// query continuation
		foreach( $query->getGenerator() as $response ) {
			// match response pages with my pages
			( new PageMatcher( $response, $pages ) )
				->matchByTitle( $matching_callback, $page_title_callback );
		}
	}

}
