<?php
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

use DateTime;
use mw\API\PageMatcher;

/**
 * Handle some PDCs
 */
class PDCs {

	/**
	 * Sort some PDCs by creation date
	 *
	 * @param $pdcs array
	 */
	public static function sortByCreationDate( & $pdcs ) {
		// sort PDCs by start date
		usort( $pdcs, function ( $a, $b ) {
			return $a->getCreationDate() > $b->getCreationDate();
		} );
	}

	/**
	 * Index some PDCs by their type
	 *
	 * @param $pdcs array
	 * @return array
	 */
	public static function indexByType( $pdcs ) {
		$pdcs_by_type = [];
		foreach( CategoryYearMonthDayTypes::all() as $Type ) {
			$pdcs_by_type[ $Type::PDC_TYPE ] = [];
		}
		foreach( $pdcs as $pdc ) {
			$type = $pdc->getType();
			$pdcs_by_type[ $type ][] = $pdc;
		}
		return $pdcs_by_type;
	}

	/**
	 * Discard all the PDCs that do not belong to a certain date
	 *
	 * @param $pdcs array
	 * @param $date DateTime
	 * @return array
	 */
	public static function filterByDate( $pdcs, DateTime $date ) {
		$y_m_d = $date->format( 'Y-m-d' );
		return array_filter( $pdcs, function ( $pdc ) use ( $y_m_d ) {
			return $pdc->getStartDate()->format( 'Y-m-d' ) === $y_m_d;
		} );
	}

	/**
	 * Discard all the PDCs that are not multiple
	 *
	 * @param $pdcs array
	 * @return $pdcs
	 */
	public static function filterNotMultiple( $pdcs ) {
		return array_filter( $pdcs, function ( $pdc ) {
			return ! $pdc->isMultiple();
		} );
	}

	/**
	 * Get all the title subjects of the specified PDCs
	 *
	 * @param $pdcs array
	 * @return array
	 */
	public static function titleSubjects( $pdcs ) {
		return array_map( function ( $pdc ) {
			return $pdc->getTitleSubject();
		}, $pdcs );
	}

	/**
	 * Populate the specified PDCs that miss informations
	 *
	 * This method is intended to do less API requests as possible.
	 *
	 * @param $pdcs array
	 * @return array
	 */
	public static function populateMissingInformations( $pdcs ) {
		self::populateMissingInformationsFromLastRevision(  $pdcs );
	}

	/**
	 * Populate the specified PDCs with missing informations that can
	 * be obtained fetching the last revision.
	 *
	 * This method is intended to do less API requests as possible.
	 *
	 * @param $pdcs
	 */
	private static function populateMissingInformationsFromLastRevision( $pdcs ) {

		if( ! $pdcs ) {
			return;
		}

		/*
		 * fetch the last revision from all the PDCs
		 *
		 * @see https://it.wikipedia.org/w/api.php?action=help&modules=query%2Brevisions
		 */
		$query = Page::api()->createQuery( [
			'action'    => 'query',
			'titles'    => self::titleSubjects( $pdcs ),
			'prop'      => 'revisions',
			'rvprop'    => [
				'content',
				'timestamp',
			],
			'rvslots'   => 'main',
			'rvsection' => '0',
		] );

		// callback fired for every match between response pages and PDCs
		$matching_callback = function ( $page, $pdc ) {
			if( isset( $page->revisions[ 0 ] ) ) {
				// this is the only one
				$revision = $page->revisions[ 0 ];

				// populate the lastedit date
				$lastedit_date = Page::createDateTimeFromString( $revision->timestamp );
				$pdc->setLasteditDate( $lastedit_date );

				// populate the subject themes
				if( isset( $revision->slots->main ) ) {
					$pdc->setSubjectThemesScrapingSubjectWikitext( $revision->slots->main->{ '*' } );
				}
			}
		};

		// callback that returns the PDC page title
		$pdc_page_title_callback = function ( $pdc ) {
			return $pdc->getTitleSubject();
		};

		// query continuation
		foreach( $query->getGenerator() as $response ) {
			// match page results and PDCs by title
			( new PageMatcher( $response, $pdcs ) )
				->matchByTitle( $matching_callback, $pdc_page_title_callback );
		}
	}

}
