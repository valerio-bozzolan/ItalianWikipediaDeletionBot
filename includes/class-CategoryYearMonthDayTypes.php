<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2013 Mauro742, MauroBot
# 	https://it.wikipedia.org/wiki/Utente:Mauro742
# 	https://it.wikipedia.org/wiki/Utente:MauroBot
# 	Originally under Creative Commons BY SA 3.0 International
# 	Utente:MauroBot/BotCancellazioni/category.js
# 	https://creativecommons.org/licenses/by-sa/3.0/
#   https://wikimediafoundation.org/wiki/Special:MyLanguage/Terms_of_Use/it
# Copyright (C) 2018, 2019 Valerio Bozzolan
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

use InvalidArgumentException;

/**
 * Handler of all the PDC type categories
 */
class CategoryYearMonthDayTypes {

	/**
	 * PDC types
	 *
	 * Categories in order of importance (I think)
	 *
	 * @var array
	 */
	public static $TYPES = [
		CategoryYearMonthDayTypeVoting    ::class, // voting
		CategoryYearMonthDayTypeOrdinary  ::class, // ordinary
		CategoryYearMonthDayTypeProlonged ::class, // prolonged
		CategoryYearMonthDayTypeConsensual::class, // consensual
		CategoryYearMonthDay              ::class, // simple
	];

	/**
	 * Other types of categories
	 *
	 * @var array
	 */
	private static $OTHER_KNOWN_CATEGORIES = [
		'Categoria:Procedure di cancellazione protette',
		'Categoria:Procedure di cancellazione multiple in corso',
		'Categoria:Pagine protette - scadute',
		'Categoria:Pagine di servizio protette',
	];

	/**
	 * Get all the types
	 *
	 * @return array
	 */
	public static function all() {
		return self::$TYPES;
	}

	/**
	 * Create the corresponding category object from its title
	 *
	 * @param $title string Page title
	 * @return CategoryYearMonthDay|false
	 * @throw PDCUnkownCategoryException
	 */
	public static function createParsingTitle( $title ) {
		foreach( self::all() as $Type ) {
			$category = $Type::createParsingTitle( $title );
			if( $category ) {
				return $category;
			}
		}
		if( !in_array( $title, self::$OTHER_KNOWN_CATEGORIES, true ) ) {
			throw new PDCUnknownCategoryException( "unexpected PDC category '$title'" );
		}
		return false;
	}

	/**
	 * Get a "genericity" score of a certain PDC category class name.
	 *
	 * Useful to comparate PDC types.
	 *
	 * E.g. a "simple" PDC has an higher score over a "consensual" PDC.
	 *
	 * @param $category string Class name
	 * @return int 0-4
	 */
	public static function genericityFromClass( $category_class_name ) {
		$i = array_search( $category_class_name, self::all(), true );
		if( false === $i ) {
			throw new InvalidArgumentException( "unknown class name $category_class_name" );
		}
		return $i;
	}

	/**
	 * Get a "genericity" score of a certain PDC category instance.
	 *
	 * @see self::genericity()
	 * @param $category CategoryYearMonthDay
	 * @return int 0-4
	 */
	public static function genericityFromObject( CategoryYearMonthDay $category ) {
		return self::genericityFromClass( get_class( $category ) );
	}

	/**
	 * Find the best category (the most recent) from a list
	 *
	 * @param $categories CategoryYearMonthDay[]
	 * @return CategoryYearMonthDay One of the $categories
	 */
	public static function findBestCategory( $categories ) {
		$best = null;
		foreach( $categories as $category ) {
			if( ! $best ) {
				$best = $category;
			} elseif( $category->getDateTime()->format('Y-m-d') === $best->getDateTime()->format('Y-m-d') ) {
				// the day is the same: take the most precise
				if( self::genericityFromObject( $category ) < self::genericityFromObject( $best ) ) {
					$best = $category;
				}
			} elseif( $category->getDateTime() > $best->getDateTime() ) {
				// take the newest
				$best = $category;
			}
		}
		if( ! $best ) {
			throw new PDCException( 'cannot find the best categories' );
		}
		return $best;
	}

	/**
	 *
	 *
	 * @param $year int
	 * @param $month int
	 * @param $day int
	 * @return PDC[]
	 */
	public static function fetchYearMonthDayPDCs( $year, $month, $day ) {
		$pdcs_by_id = [];
	}

}
