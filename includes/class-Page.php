<?php
# it.wiki deletion bot in PHP
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

use DateTime;
use DateTimeZone;
use cli\Input;
use cli\Log;
use wm\WikipediaIt;
use mw\Tokens;

/**
 * Handle a page
 */
class Page {

	/**
	 * Time zone of Italian Wikipedia community.
	 *
	 * @var string
	 */
	const COMMUNITY_TIMEZONE = 'Europe/Rome';

	/**
	 * Enable this flag to ask for every changes
	 *
	 * @var bool
	 */
	public static $ASK_BEFORE_SAVING = false;

	/**
	 * @var string Page title with prefix
	 */
	private $title;

	/**
	 * Cache for the existing status of this page
	 *
	 * @var bool
	 */
	private $exists;

	/**
	 * Construct a Page
	 *
	 * @param $title Page title with its prefix
	 */
	public function __construct( $title ) {
		$this->title = $title;
	}

	/**
	 * Get the page title with its prefix
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Save this page
	 *
	 * @param $content string Page content
	 * @param $summary string Edit summary
	 * @return mixed
	 */
	public function saveByContentSummary( $content, $summary ) {
		$title = $this->getTitle();
		$api = self::api();
		$args = [
			'action'  => 'edit',
			'title'   => $title,
			'text'    => $content,
			'summary' => $summary,
			'token'   => $api->login()->getToken( Tokens::CSRF ),
			'bot'     => 1,
		];

		if( self::$ASK_BEFORE_SAVING ) {
			print_r( $args );
			if( 'y' !== Input::yesNoQuestion( "Save?" ) ) {
				return false;
			}
		}

		Log::info( "writing [[$title]]" );

		return $api->post( $args );
	}

	/**
	 * Save this page if it does not exist
	 *
	 * @param $content string Page content
	 * @param $summary string Edit summary
	 * @return bool|mixed False if not created
	 */
	public function saveByContentSummaryIfNotExists( $content, $summary ) {
		if( ! $this->exists() ) {
			return $this->saveByContentSummary( $content, $summary );
		}
		return false;
	}

	/**
	 * Set internally if this page exists
	 *
	 * @param $exists bool
	 * @return bool|mixed False if not created
	 */
	public function setIfExists( $exists ) {
		$this->exists = $exists;
	}

	/**
	 * Check if this page exists (the result is cached)
	 *
	 * @return bool
	 */
	public function exists() {
		if( null === $this->exists ) {
			$result = self::api()->fetch( [
				'action' => 'query',
				'prop'   => 'info',
				'titles' => $this->getTitle(),
			] );
			foreach( $result->query->pages as $pageid => $page ) {
				$this->exists = ! isset( $page->missing );
			}
		}
		return $this->exists;
	}

	/**
	 * Fetch the first revision date by direction
	 *
	 * @param $dir string direction
	 * @return DateTime
	 */
	public function fetchFirstRevisionDateByDirection( $direction ) {
		$response = self::api()->fetch( [
			'action' => 'query',
			'titles' => $this->getTitle(),
			'prop'   => 'revisions',
			'rvprop' => [
					'timestamp'
			],
			'rvlimit' => 1,
			'rvdir' => $direction,
		] );
		foreach( $response->query->pages as $page ) {
			if( isset( $page->revisions ) ) {
				foreach( $page->revisions as $revision ) {
					return self::createDateTimeFromString( $revision->timestamp );
				}
			}
			if( isset( $page->missing ) ) {
				throw new PDCMissingException( sprintf(
					"The page [[%s]] is missing",
					$this->getTitle()
				) );
			}
		}
		throw new PDCWithoutCreationDateException( sprintf(
			"The page [[%s]] has not a revision with a creation date (using direction %s)",
			$this->title(),
			$direction
		) );
	}

	/**
	 * Fetch the creation date of this page
	 *
	 * @return DateTime
	 */
	public function fetchCreationDate() {
		return $this->fetchFirstRevisionDateByDirection( 'newer' );
	}

	/**
	 * Fetch the creation date of this page
	 *
	 * @return DateTime
	 */
	public function fetchLasteditDate() {
		return $this->fetchFirstRevisionDateByDirection( 'older' );
	}

	/**
	 * Get the API related to this page
	 *
	 * @return mw\API
	 */
	public static function api() {
		return WikipediaIt::instance();
	}

	/**
	 * Create a DateTime object from a MediaWiki formatted date
	 *
	 * MediaWiki dates are formatted following the ISO8601 standard
	 * and you may want to specify your community timezone.
	 *
	 * @param $datetime string
	 * @return DateTime
	 */
	public static function createDateTimeFromString( $datetime ) {
		return DateTime::createFromFormat( DateTime::ISO8601, $datetime )
				->setTimezone( new DateTimeZone( self::COMMUNITY_TIMEZONE ) );
	}
}
