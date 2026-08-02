<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2013 Mauro742, MauroBot
# 	https://it.wikipedia.org/wiki/Utente:Mauro742
# 	https://it.wikipedia.org/wiki/Utente:MauroBot
# 	Originally under Creative Commons BY SA 3.0 International
# 	Utente:MauroBot/BotCancellazioni/category.js
# 	https://creativecommons.org/licenses/by-sa/3.0/
#   https://wikimediafoundation.org/wiki/Special:MyLanguage/Terms_of_Use/it
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

namespace itwikidelbot;

use cli\Log;

/**
 * Handle a daily category that directly contains semplified ("semplificate") PDC pages
 * It contains other sub-categories.
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_del_19_febbraio_2018
 */
class CategoryYearMonthDay extends PageYearMonthDay {

	/**
	 * Template name
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_DAY';

	/**
	 * PDC type
	 *
	 * The part of the category title that rappresent this type of PDC.
	 */
	const PDC_TYPE = 'semplificate';

	/**
	 * PDC type (in an human form)
	 *
	 * Abbreviation of the PDC_TYPE. It's used in the counting page.
	 *
	 * @var string
	 */
	const PDC_TYPE_HUMAN = 'semplificata';

	/**
	 * Title format
	 *
	 * Used to describe both the plain text title and its matching pattern.
	 *
	 * Don't use placeholders different from '%s'.
	 *
	 * Arguments:
	 * 	1: day
	 * 	2: month name
	 * 	3: year
	 */
	const TITLE_FORMAT = 'Categoria:Cancellazioni del %s %s %s';

	/**
	 * Title format arguments
	 *
	 * Arguments that can create this page title, when filling the title format.
	 *
	 * @return array Arguments for the TITLE_FORMAT
	 */
	protected function getTitleFormatArguments() {
		return [
			$this->getDay(),       // 1: day
			$this->getMonthName(), // 2: month name
			$this->getYear(),      // 3: year
		];
	}

	/**
	 * Title format pattern groups
	 *
	 * Regex groups that can create a regex for this page title, when filling the title format.
	 *
	 * @return array Array of regex groups for the TITLE_FORMAT
	 */
	protected static function titleFormatGroups() {
		return [
			'([0-9]{1,2})', // 1: day
			'([a-z]+)',     // 2: month name
			'([0-9]{4})',   // 3: year
		];
	}

	/**
	 * Title regex
	 *
	 * Complete regex that can match arguments (day, month, etc.) from a page title.
	 *
	 * @return string regex
	 */
	protected static function titleRegex() {
		return '/^' . vsprintf( static::TITLE_FORMAT, static::titleFormatGroups() ) . '$/';
	}

	/**
	 * Get the title of this page
	 *
	 * It's obtained filling the title format with its arguments.
	 *
	 * @override PageTemplated#getTemplatedTitle()
	 * @return string
	 */
	public function getTemplatedTitle() {
		return vsprintf( static::TITLE_FORMAT, $this->getTitleFormatArguments() );
	}

	/**
	 * Static constructor
	 *
	 * Create an CategoryYearMonthDay object extracting informations from a specific page title (if it matches the title format).
	 *
	 * @param $title string Page title to be matched
	 * @return self|false
	 */
	public static function createParsingTitle( $title ) {
		if( 1 === preg_match( static::titleRegex(), $title, $matches ) )  {

			// discard the first match group: it's simply the $title itself
			array_shift( $matches );

			return static::createFromTitleFormatArguments( $matches );
		}
		return false;
	}

	/**
	 * Static constructor
	 *
	 * Create a CategoryYearMonthDay object from its title format arguments
	 *
	 * @see self::createFromTitle()
	 * @see PageYearMonth::__construct()
	 * @param $arguments Arguments for the page title format
	 * @return self
	 */
	protected static function createFromTitleFormatArguments( $arguments ) {
		list( $day, $month_name, $year ) = $arguments;
		return new static(
			(int) $year,
			Months::name2number( $month_name ) + 1,
			(int) $day
		);
	}

	/**
	 * Template arguments:
	 *
	 * 1: category title
	 * 2: year
	 * 3: month  1-12
	 * 4: month name
	 * 5: day 1-31
	 *
	 * @override CategoryYearMonth::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		return array_merge(
			[ ( new CategoryYearMonth( $this->getYear(), $this->getMonth() ) )
				->getTemplatedTitle() ],
			parent::getTemplateArguments()
		);
	}

	/**
	 * Fetch PDCs from this category
	 *
	 * @return array
	 */
	public function fetchPDCs() {
		$api = self::api()->createQuery( [
			'action' => 'query',

			// generator=categorymembers: get pages in category
			// gmtitle=:                  specify the category title
			// gmtype=page:               get sub-pages
			// gmsort=timestamp:          order by insertion date in that category
			// gmdir=asc:                 ascending order
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategorymembers
			// Note: Generator parameter names must be prefixed with a 'g'
			'generator'   => 'categorymembers',
			'gcmtitle'    => $this->getTitle(),
//			'gcmtype'     => 'page', // Note: Ignored when cmsort=timestamp is set.
			'gcmnamespace' => 4, // Wikipedia
			'gcmlimit'    => 100,
			'gcmsort'     => 'timestamp',
			'gcmdir'      => 'asc',
//
			// for each page load infos, categories and latest revision
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Binfo
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategories
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Brevisions
			// Note: probably the revisions parameter is unuseful for last update date (already provided by "touched").
			// Note: revisions can be still useful to know the creation date
			'prop' => [ 'info' , 'categories'/*, 'revisions'*/ ],

			// inprop=protecion: list of the protection level of each page
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Bcategories
			'inprop' => 'protection',

			// clprop=sortkey:   adds the sortkey and sortkey prefix for the category
			// clprop=timestamp: adds the timestamp of when the page was included
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Binfo
			'clprop' => [ 'sortkey', 'timestamp' ],

			// rvprop=timestamp: timestamp of the revision
			// rvlimit=1: only 1 revision
			// rvdir=older: order by oldest
			// https://it.wikipedia.org/w/api.php?action=help&modules=query%2Brevisions
//			'rvprop'  => 'timestamp',
//			'rvdir'   => 'older',
//			'rvlimit' => 1,
		] );

		$all = [];
		while( $api->hasNext() ) {
			$next = $api->fetchNext();

			if( isset( $next->query->pages ) ) {
				foreach( $next->query->pages as $page ) {

					// multiple call will add more infos on the same page
					// https://www.mediawiki.org/wiki/API:Query#Generators_and_continuation
					// TODO: use batchcomplete for better memory usage
					$pageid = $page->pageid;
					if( isset( $all[ $pageid ] ) ) {
						foreach( $page as $property => $value ) {
							if( isset( $all[ $pageid ]->{$property} ) && is_array( $all[ $pageid ]->{$property} ) ) {
								// merge categories
								$all[ $pageid ]->{$property} = array_merge(
									$all[ $pageid ]->{$property},
									$value
								);
							} else {
								$all[ $pageid ]->{$property} = $value;
							}
						}
					} else {
						$all[ $pageid ] = $page;
					}
				}
			}
		}

		$pdcs = [];
		foreach( $all as $page ) {
			try {
				$pdcs[] = PDC::createFromRaw( $page );
			} catch( PDCException $e ) {
				Log::warn( sprintf(
					"exception in PDC '%s': %s",
					$page->title,
					$e->getMessage()
				) );
			}
		}

		return $pdcs;
	}

	/**
	 * Get a "genericity" score of this PDC category type.
	 *
	 * @see CategoryYearMonthDayTypes::genericityFromClass()
	 * @return int
	 */
	public static function genericity() {
		return CategoryYearMonthDayTypes::genericityFromClass( static::class );
	}

}
