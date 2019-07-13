<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2013 Mauro742, MauroBot
# 	https://it.wikipedia.org/wiki/Utente:Mauro742
# 	https://it.wikipedia.org/wiki/Utente:MauroBot
# 	Originally under Creative Commons BY SA 3.0 International
#	https://it.wikipedia.org/wiki/Utente:MauroBot/BotCancellazioni/core.js
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

use DateTime;

/**
 * Handle a "Procedura di cancellazione"
 *
 * It is a page like "Wikipedia:Pagine da cancellare/ASD"
 */
class PDC extends Page {

	/**
	 * Prefix of every PDC
	 */
	const PREFIX = 'Wikipedia:Pagine da cancellare/';

	/**
	 * Prefix of every multiple PDC
	 */
	const PREFIX_MULTIPLE = 'Wikipedia:Pagine da cancellare/multiple/';

	/**
	 * Name for the category for running PDCs
	 *
	 * N.B. 'multiple' PDCs have also this category.
	 */
	const RUNNING_CAT = 'Categoria:Procedure di cancellazione in corso';

	/**
	 * Day threshold
	 *
	 * This is a number between 0 and 1. The more it is near to 0,
	 * the more the PDC duration will be floored.
	 */
	const DAY_THRESHOLD = 0.7; // 16:47~

	/**
	 * PDC category type
	 *
	 * @var CategoryYearMonthDay
	 */
	private $categoryType;

	/**
	 * Page id
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Title of the PDC subject page (the Wikipedia article title)
	 *
	 * Obtained from {{DEFAULTSORT}} category sortkey
	 *
	 * @var string
	 */
	private $titleSubject;

	/**
	 * Subject themes
	 *
	 * E.g. [ 'software libero', 'LGBT', 'società' ]
	 *
	 * @var array
	 */
	private $subjectThemes = [];

	/**
	 * Page length in bytes
	 *
	 * @var int
	 */
	private $length;

	/**
	 * Creation date
	 *
	 * @var date
	 */
	private $creationDate;

	/**
	 * Last update
	 *
	 * @var date
	 */
	private $lasteditDate;

	/**
	 * Is protected?
	 *
	 * @var bool
	 */
	private $isProtected;

	/**
	 * Is running?
	 *
	 * @var bool
	 */
	private $isRunning;

	/**
	 * Properties that the raw object must have
	 *
	 * @see self::createFromRaw()
	 * @var array
	 */
	private static $RAW_PROPERTIES = [
		'pageid',
		'title',
		'length',
		'touched',
		'protection',
		'categories',
	];

	/**
	 * Properties that a raw category must have
	 *
	 * @see self::createFromRaw()
	 * @var array
	 */
	private static $RAW_CATEGORY_PROPERTIES = [
		'title',
		'sortkeyprefix',
		'timestamp', // TODO: remove as dependency if it's unuseful
	];

	/**
	 * Constructor
	 *
	 * @param $category_type CategoryYearMonthDay PDC category type
	 * @param $id int PDC page id
	 * @param $title string PDC title prefixed
	 * @param $title_subject string Title of the subject page (the Wikipedia article title)
	 * @param $length int PDC length
	 * @param $creation DateTime|null PDC creation date
	 * @param $is_protected bool Is the PDC protected?
	 * @param $is_running bool Is the PDC running?
	 * @see Page::__construct()
	 * @throws PDCException
	 */
	public function __construct( CategoryYearMonthDay $category_type, $id, $title, $title_subject, $length, $creation, $is_protected, $is_running ) {
		$this->id           = $id;
		$this->titleSubject = $title_subject;
		$this->length       = $length;
		$this->isProtected  = $is_protected;
		$this->isRunning    = $is_running;
		$this->setCreationDate( $creation );
		$this->setCategoryType( $category_type );

		parent::__construct( $title );

		// consistence checks
		if( ! $this->isTitlePrefixValid() ) {
			throw new PDCException( 'not a PDC' );
		}
	}

	/**
	 * Static constructor
	 *
	 * @see YearMonthDayTypes::fetchPDCs()
	 * @param $page mixed
	 * @return self
	 * @throws PDCException
	 */
	public static function createFromRaw( $page ) {

		// verify the API response consistence
		foreach( self::$RAW_PROPERTIES as $property ) {
			if( ! isset( $page->$property ) ) {
				throw new PDCException( "missing property $property" );
			}
		}
		foreach( $page->categories as $category_raw ) {
			foreach( self::$RAW_CATEGORY_PROPERTIES as $property ) {
				if( ! isset( $category_raw->$property ) ) {
					throw new PDCException( "missing property $property in categories" );
				}
			}
		}
		if( 0 === count( $page->categories ) ) {
			throw new PDCException( "no category" );
		}

		// get the page title of the subject from the {{DEFAULTSORT:xxx}} value
		$title_subject = null;
		foreach( $page->categories as $category_raw ) {
			$title_subject = $category_raw->sortkeyprefix;
			break;
		}

		// is this PDC running?
		$is_running = false;

		// array of instances of the class CategoryYearMonthDay (and subclasses)
		// they also have the timestamp property
		$categories = [];
		foreach( $page->categories as $category_raw ) {
			if( self::RUNNING_CAT === $category_raw->title ) {
				$is_running = true;
				break;
			}

			// try to recognize this category
			try {
				$category = CategoryYearMonthDayTypes::createParsingTitle( $category_raw->title );
				if( $category ) {
					$category->timestamp = self::createDateTimeFromString( $category_raw->timestamp );
					$categories[] = $category;
				}
			} catch( PDCUnknownCategoryException $e ) {
				\cli\Log::warn( $e->getMessage() );
			}
		}

		// creation date
		$creation = null;
		foreach( $categories as $category ) {
			if( get_class( $category ) === CategoryYearMonthDay::class ) {
				$creation_secure_unprecise = $category->getDateTime();
				$creation_unsecure_precise = $category->timestamp;
				if( $creation_secure_unprecise->format( 'Y-m-d' ) === $creation_unsecure_precise->format( 'Y-m-d' ) ) {
					$creation = $creation_unsecure_precise;
				}
				break;
			}
		}

		if( ! $categories ) {
			throw new PDCException( sprintf(
				"the PDC was in %d categories and/but no one was recognized",
				count( $page->categories )
			) );
		}

		// find the best (the newest) category to be applied
		$best_category = CategoryYearMonthDayTypes::findBestCategory( $categories );

		// read protection status
		$is_protected = false;
		foreach( $page->protection as $protection ) {
			if( 'edit' === $protection->type && 'sysop' === $protection->level ) {
				$is_protected = true;
				break;
			}
		}

		return new self(
			$best_category,
			(int) $page->pageid,
			$page->title,
			$title_subject,
			(int) $page->length,
			$creation,
			$is_protected,
			$is_running
		);
	}

	/**
	 * Get the page id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get the title of the subject page (the Wikipedia article title)
	 *
	 * Obtained from {{DEFAULTSORT}} category sortkey
	 *
	 * @return string
	 */
	public function getTitleSubject() {
		return $this->titleSubject;
	}

	/**
	 * Get the subject themes
	 *
	 * E.g. [ 'software libero', 'LGBT', 'società' ]
	 *
	 * @return array
	 */
	public function getSubjectThemes() {
		return $this->subjectThemes;
	}

	/**
	 * Ad a subject themes
	 *
	 * @param $theme string Subject theme e.g. 'software libero'
	 * @return self
	 */
	public function addSubjectTheme( $theme ) {
		$this->subjectThemes[] = $theme;
		return $this;
	}

	/**
	 * Get the turnover number
	 *
	 * It's the '/2' number in the PDC title.
	 *
	 * @return int|false
	 */
	public function getTurnover() {
		$safe_title = preg_quote( $this->getTitleSubject(), '@' );
		$pattern = "@$safe_title/([0-9]+)\$@";
		$found = preg_match( $pattern, $this->getTitle(), $matches );
		if( 1 === $found ) {
			return (int) $matches[ 1 ];
		}
		return false;
	}

	/**
	 * Get the page length
	 *
	 * @return int
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Get the category type
	 *
	 * @return CategoryYearMonthDay
	 */
	public function getCategoryType() {
		return $this->categoryType;
	}

	/**
	 * Set the category type
	 *
	 * @param $category_type CategoryYearMonthDay
	 * @return self
	 */
	public function setCategoryType( CategoryYearMonthDay $category_type ) {
		$this->categoryType = $category_type;
		return $this;
	}

	/**
	 * Get the start date of this PDC (of this type)
	 *
	 * @return DateTime
	 */
	public function getStartDate() {
		return $this->getCategoryType()->getDateTime();
	}

	/**
	 * It has the creation date cached?
	 *
	 * @return bool
	 */
	public function hasCreationDate() {
		return isset( $this->creationDate );
	}

	/**
	 * Get the creation date
	 *
	 * @return DateTime
	 */
	public function getCreationDate() {
		if( ! $this->hasCreationDate() ) {
			$this->creationDate = $this->fetchCreationDate();
		}
		return $this->creationDate;
	}

	/**
	 * Set the creation date
	 *
	 * @param $creation DateTime
	 * @return self
	 */
	public function setCreationDate( $creation ) {
		$this->creationDate = $creation;
		return $this;
	}

	/**
	 * It has the latest update date cached?
	 *
	 * @return bool
	 */
	public function hasLasteditDate() {
		return isset( $this->lasteditDate );
	}

	/**
	 * Get the latest update date
	 *
	 * Note: this information can't be retrieved by the 'touched' API field
	 * because it can be poisoned by purges.
	 *
	 * @return DateTime
	 */
	public function getLasteditDate() {
		if( ! $this->hasLasteditDate() ) {
			$this->lasteditDate = $this->fetchLasteditDate();
		}
		return $this->lasteditDate;
	}

	/**
	 * Set the lastedit date
	 *
	 * @param $lastedit DateTime
	 * @return self
	 */
	public function setLasteditDate( DateTime $lastedit ) {
		$this->lastedit = $lastedit;
		return $this;
	}

	/**
	 * Get the PDC type class
	 *
	 * @return class
	 */
	public function getTypeClass() {
		return get_class( $this->getCategoryType() );
	}

	/**
	 * Get the PDC type class genericity
	 *
	 * @see CategoryYearMonthDay::genericity()
	 * @return int
	 */
	public function getCategoryGenericity() {
		$class_name = self::getTypeClass();
		return $class_name::genericity();
	}

	/**
	 * Get the PDC type name
	 *
	 * @return string e.g. 'con votazioni'
	 */
	public function getType() {
		$class = $this->getTypeClass();
		return $class::PDC_TYPE;
	}

	/**
	 * Get the PDC human type
	 *
	 * @return string e.g. 'votazione'
	 */
	public function getHumanType() {
		$class = $this->getTypeClass();
		return $class::PDC_TYPE_HUMAN;
	}

	/**
	 * Check if this page is protected
	 *
	 * @return bool
	 */
	public function isProtected() {
		return $this->isProtected;
	}

	/**
	 * Check if this page is running
	 *
	 * @return bool
	 */
	public function isRunning() {
		return $this->isRunning && ! $this->isProtected();
	}

	/**
	 * Check if this PDC has a known prefix
	 *
	 * @return bool
	 */
	private function isTitlePrefixValid() {
		return $this->titlehasPrefix( self::PREFIX );
	}

	/**
	 * Check if this PDC involves multiple PDCs
	 *
	 * @return bool
	 */
	public function isMultiple() {
		return $this->titleHasPrefix( self::PREFIX_MULTIPLE );
	}

	/**
	 * Get the title prefix
	 *
	 * @return string
	 */
	private function getTitlePrefix() {
		return $this->isMultiple()
			? self::PREFIX_MULTIPLE
			: self::PREFIX;
	}

	/**
	 * Get the title unprefixed
	 *
	 * @return string
	 */
	public function getTitleUnprefixed() {
		return substr( $this->getTitle(), strlen( $this->getTitlePrefix() ) );
	}

	/**
	 * Check if the title has a certain prefix
	 *
	 * @param $prefix string
	 * @return bool
	 */
	private function titleHasPrefix( $prefix ) {
		return 0 === strpos( $this->getTitle(), $prefix );
	}

	/**
	 * Check if the title of the subject is consistent with the PDC title
	 *
	 * @return bool
	 */
	public function isTitleSubjectConsistent() {
		// subject from {{DEFAULTSORT}} category prefix
		$subject = $this->getTitleSubject();

		// subject generated unprefixing page title
		$subject_gen = $this->getTitleUnprefixed();

		if( $subject !== $subject_gen ) {
			// Check if the PDC title ends with an enumeration e.g. 'asd/14'
			$status = preg_match( '@(.*)/[0-9]+$@', $subject_gen, $matches );
			if( 1 === $status ) {
				// Get the interesting part e.g. 'asd'
				$subject_gen = $matches[ 1 ];
			}
		}
		return $subject === $subject_gen;
	}

	/**
	 * Get the PDC temperature
	 *
	 * The temperature is a value betweeen 0-100
	 *
	 * @return int
	 */
	public function getTemperature() {
		$slope  = null;
		$offset = null;
		switch( $this->getType() ) {
			case CategoryYearMonthDay::PDC_TYPE;
				$slope  =   0.0365;
				$offset = -24.0;
				break;
			case CategoryYearMonthDayTypeConsensual::PDC_TYPE:
				$slope  =   0.0075;
				$offset = -12.81;
				break;
			case CategoryYearMonthDayTypeProlonged::PDC_TYPE:
				$slope  =  0.0035;
				$offset = -9.76;
				break;
			case CategoryYearMonthDayTypeOrdinary::PDC_TYPE:
			case CategoryYearMonthDayTypeVoting  ::PDC_TYPE:
				$slope  =   0.0025;
				$offset = -16.43;
				break;
			default:
				throw new PDCException( 'unexpected type' );
		}

		$temp = round( $slope * $this->getLength() + $offset );
		if ( $temp > 100 ) {
			$temp = 100;
		} elseif ( $temp < 0 ) {
			$temp = 0;
		}
		return $temp;
	}

	/**
	 * Get the duration of this PDC expressed in days
	 *
	 * When the PDC is running the user basically wants a simple
	 * difference from the creation date to the last edit date,
	 * but when the PDC is protected it means that a sysop touched
	 * this page at ~midnight so the last edit will be at ~midnight.
	 * This is why - when a PDC is protected - the start date time should
	 * be moved forward to ~midnight in order to balance the sysop touch.
	 *
	 * In other words this method gives a "precise duration" when the PDC
	 * is running and "legal duration" when the PDC is ended.
	 *
	 * @see https://it.wikipedia.org/wiki/Wikipedia:Regole_per_la_cancellazione
	 * @return int Duration days
	 */
	public function getDurationDays() {
		$creation = $this->getCreationDate();
		$creation = clone $creation;
		$lastedit = $this->getLasteditDate();

		if( $this->isProtected() ) {
			// Moving forward the creation date to balance the sysop touch
			$creation->setTime( 23, 59, 59 );
		}

		$creation_s = $creation->format( 'U' );
		$lastedit_s = $lastedit->format( 'U' );
		$seconds = $lastedit_s - $creation_s;

		$days_f = $seconds / 86400;
		$days = (int) floor( $days_f );
		$day_mantissa = $days_f - $days;
		if( $day_mantissa > self::DAY_THRESHOLD ) {
			$days++;
		}

		if( $days < 0 ) {
			$days = 0;
		}
		return $days;
	}

	/**
	 * Get an human rappresentation of the duration of this PDC
	 *
	 * @return string
	 */
	public function getHumanDuration() {
		$days = $this->getDurationDays();
		if( 0 === $days ) {
			return Template::get( 'DURATION.hours' );
		}
		if( 1 === $days ) {
			return Template::get( 'DURATION.day' );
		}
		return Template::get( 'DURATION.days', [ $days ] );
	}

	/**
	 * Merge a PDC with in this one
	 *
	 * @param $pdc PDC
	 */
	public function merge( PDC $pdc ) {
		if( $this->getId() !== $pdc->getId() ) {
			throw new InvalidArgumentException( 'PDCs can be merged only if they are of the same type' );
		}
		$this->setCategoryType( CategoryYearMonthDayTypes::findBestCategory( [
			$this->getCategoryType(),
			$pdc ->getCategoryType()
		] ) );
	}

	/**
	 * Set subject themes scraping subject wikitext
	 *
	 * @param $page_content string
	 * @return self
	 */
	public function setSubjectThemesScrapingSubjectWikitext( $page_content ) {
		// Pattern to match spaces, newline, tabulations
		$_ = '[ \t\n]*';

		/*
		 * Complete pattern to match all the PDC arguments
		 *
		 * {{Cancellazione|9|arg=something|arg2=something}}
		 * {{Cancella     |9|arg=something|arg2=something}}
		 * {{cancellazione|argomento = something}}
		 * {{cancellazione|argomento2 = something}}
		 *
		 * This pattern is a bit repetitive because PCRE does not support to
		 * match a group multiple times.
		 * Yes, every group can be repeated, but it will be matched only once.
		 *
		 * @TODO: use a wikitext parser
		 */
		$PATTERN = '/{{' . $_ . '(?:[Cc]ancellazione|[Cc]ancella)' . $_ . '\|(.+?)}}/';

		// catch the templates
		preg_match( $PATTERN, $page_content, $matches );

		if( isset( $matches[1] ) ) {

			// '|argomento=2|arg2=musica|9'
			$arguments = $matches[1];

			// split template arguments
			$args = explode( '|', $arguments );
			foreach( $args as $arg ) {

				// split argument key = value
				$key_value = explode( '=', $arg, 2 );
				if( count( $key_value ) === 2 ) {

					// separate key and value and strip spaces
					list( $key, $value ) = $key_value;
					$key   = trim( $key,   " \t\n" );
					$value = trim( $value, " \t\n" );

					// match 'arg', 'arg1', 'arg2' 'argomento', 'argomento2' etc.
					for( $i = 0; $i < 5; $i++ ) {
						$suffix = $i;
						if( !$suffix ) {
							$suffix = '';
						}
						if( $key === "arg$suffix" || $key === "argomento$suffix" ) {
							$this->addSubjectTheme( $value );
						}
					}
				}
			}
		}
		return $this;
	}
}
