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

use cli\Log;
use DateTime;
use DateInterval;
use Exception;

/**
 * The Bot class helps in running the bot
 */
class Bot {

	/**
	 * The date
	 *
	 * @var DateTime
	 */
	private $dateTime;

	/**
	 * A cache of already done dates.
	 * It's an array of booleans like [year][month] = true.
	 *
	 * @var array
	 */
	private $cache = [];

	/**
	 * Construct
	 *
	 * @param $date DateTime
	 */
	public function __construct( DateTime $date = null ) {
		if( ! $date ) {
			$date = new DateTime();
		}
		$this->setDate( $date );
	}

	/**
	 * Static construct
	 *
	 * @param $date string
	 */
	public static function createFromString( $date = 'now' ) {
		return new self( new DateTime( $date ) );
	}

	/**
	 * Static construct
	 *
	 * @param $y int Year
	 * @param $m int Month 1-12
	 * @param $d int Day 1-31
	 */
	public static function createFromYearMonthDay( $y, $m, $d ) {
		return new self( DateTime::createFromFormat( "Y m d", "$y $m $d") );
	}

	/**
	 * Get the date
	 *
	 * @return DateTime
	 */
	public function getDate() {
		return $this->dateTime;
	}

	/**
	 * Set the date
	 *
	 * @param $date DateTime
	 * @return self
	 */
	public function setDate( DateTime $date ) {
		$this->dateTime = $date;
	}

	/**
	 * Add a day
	 *
	 * @return self
	 */
	public function nextDay() {
		return $this->addDays( 1 );
	}

	/**
	 * Add a day
	 *
	 * @return self
	 */
	public function previousDay() {
		return $this->subDays( 1 );
	}

	/**
	 * Add a certain number of days
	 *
	 * @param $days int
	 * @return self
	 */
	public function addDays( $days ) {
		$this->getDate()->add( new DateInterval( sprintf(
			'P%dD',
			$days
		) ) );
		return $this;
	}

	/**
	 * Subtract a certain number of days
	 *
	 * @param $days int
	 * @return self
	 */
	public function subDays( $days ) {
		$this->getDate()->sub( new DateInterval( sprintf(
			'P%dD',
			$days
		) ) );
		return $this;
	}

	/**
	 * Fetch the last bot edit on the next page
	 *
	 * @return DateTime|null
	 */
	public function fetchLastedit() {
		$lastedit = null;
		try {
			$lastedit = PageYearMonthDayPDCsCount::createFromDateTime( $this->getDate() )
				->fetchLasteditDate();

		} catch( PDCMissingException $e ) {
			// Unexisting. OK.
			Log::debug( $e->getMessage() );
		} catch( PDCWithoutCreationDateException $e ) {
			// Happened once. Just try to create.
			Log::debug( $e->getMessage() );
		}
		return $lastedit;
	}

	/**
	 * Is the last edit date older than some seconds? (on the next page)
	 *
	 * @param $seconds int
	 * @return bool
	 */
	public function isLasteditOlderThanSeconds( $seconds ) {
		$lastedit = $this->fetchLastedit();
		if( ! $lastedit ) {
			return true; // Unexisting. OK.
		}
		return time() - $lastedit->format( 'U' ) > $seconds;
	}

	/**
	 * Is the last edit date older than some minutes? (on the next page)
	 *
	 * @param $minutes int
	 * @return bool
	 */
	public function isLasteditOlderThanMinutes( $minutes ) {
		return $this->isLasteditOlderThanSeconds( 60 * $minutes );
	}

	/**
	 * Run the bot at the internal date
	 *
	 * @TODO: do not repeat twice the same yearly and montly categories
	 * @return self
	 */
	public function run() {

		$cache = & $this->cache;

		// date initialization
		$date  = $this->getDate();
		$year  = $date->format( 'Y' );
		$month = $date->format( 'n' ); // 1-12
		$day   = $date->format( 'j' );

		// yearly category
		$y_category = new CategoryYear( $year );

		// monthly category
		$m_category = new CategoryYearMonth( $year, $month );

		// create all the PDC types
		$category_types = [];
		foreach( CategoryYearMonthDayTypes::all() as $CategoryType ) {
			$category_types[] = new $CategoryType( $year, $month, $day );
		}

		// all the categories
		$all_categories = $category_types;
		if( ! isset( $cache[ $month ] ) ) {
			$all_categories[] = $m_category;
		}
		if( ! isset( $cache[ $year ] ) ) {
			$all_categories[] = $y_category;
		}

		// check in bulk if the categories already exist
		Pages::populateWheneverTheyExist( $all_categories );

		// create the yearly category (once)
		if( ! isset( $cache[ $year ] ) ) {
			$cache[ $year ] = [];
			$y_category->saveIfNotExists();
		}

		// create the monthly category (once)
		$cache = & $cache[ $year ];
		if( ! isset( $cache[ $month ] ) ) {
			$cache[ $month ] = true;
			$m_category->saveIfNotExists();
		}

		Log::info( "work on $year/$month/$day" );

		// PDCs indexed by page ID
		$pdcs = [];

		// handle every PDC type
		foreach( $category_types as $category_type ) {

			// fetch PDCs from this type
			$category_type_pdcs = $category_type->fetchPDCs();

			// save the specific daily category type only if it's not empty
			// ...or only if it's the main category (that can be without pages)
			if( $category_type_pdcs || get_class( $category_type ) === CategoryYearMonthDay::class ) {
				$category_type->saveIfNotExists();
			}

			// merge the same PDCs into one
			foreach( $category_type_pdcs as $category_type_pdc ) {
				$id = $category_type_pdc->getID();
				if( isset( $pdcs[ $id ] ) ) {
					$pdcs[ $id ]->merge( $category_type_pdc );
				} else {
					$pdcs[ $id ] = $category_type_pdc;
				}
			}
		}

		// select only the PDCs that belong to this date
		$pdcs = PDCs::filterByDate( $pdcs, $date );

		Log::info( sprintf( sprintf(
			"found %d PDCs",
			count( $pdcs )
		) ) );

		// populate missing informations
		PDCs::populateMissingInformations( $pdcs );

		// sort by creation date
		PDCs::sortByCreationDate( $pdcs );

		// index then by their PDC_TYPE
		$pdcs_by_type = PDCs::indexByType( $pdcs );

		// save the counting page
		PageYearMonthDayPDCsCount::createFromDateTimePDCs( $this->getDate(), $pdcs_by_type )
			->save();

		// save the log page
		PageYearMonthDayPDCsLog::createFromDateTimePDCs( $this->getDate(), $pdcs_by_type )
			->save();

		return $this;
	}
}
