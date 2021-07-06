<?php
# it.wiki deletion bot in PHP
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
 * Handle a generic page associated to a template
 */
abstract class PageTemplated extends Page {

	/**
	 * Template name of this page
	 *
	 * To be overrided
	 */
	const TEMPLATE_NAME = 'EXAMPLE_NAME';

	/**
	 * Constructor
	 *
	 * @param $args Template arguments
	 */
	public function __construct() {
		parent::__construct( $this->getTemplatedTitle() );
	}

	/**
	 * Get the name of the template
	 *
	 * @return string
	 */
	public static function getTemplateName() {
		return static::TEMPLATE_NAME;
	}

	/**
	 * Get the name of the template for the page title
	 *
	 * @return string
	 */
	public static function getTemplateTitleName() {
		return static::getTemplateName() . '.title';
	}

	/**
	 * Get the name of the template for the summary
	 *
	 * @return string
	 */
	public static function getTemplateSummaryName() {
		return static::getTemplateName() . '.summary';
	}

	/**
	 * Get the name of the template for the content
	 */
	public static function getTemplateContentName() {
		return static::getTemplateName() . '.content';
	}

	/**
	 * Get the template arguments
	 *
	 * @return array
	 */
	public abstract function getTemplateArguments();

	/**
	 * Get the template arguments, plus some more arguments
	 *
	 * @param array $args Array of arguments
	 * @param mixed $more More arguments (one string or an array)
	 * @return array
	 */
	public static function sumArgs( $args, $more ) {

		if( is_array( $more ) ) {
			// append all the fields
			$args = array_merge( $args, $more );
		} else {
			// append just one field
			$args[] = $more;
		}

		return $args;
	}

	/**
	 * Get the edit summary for this page from its template
	 *
	 * @return string
	 */
	public function getTemplatedSummary() {
		return Template::get( static::getTemplateSummaryName(), $this->getTemplateArguments() );
	}

	/**
	 * Get the page title from its template
	 *
	 * @return string
	 */
	public function getTemplatedTitle() {
		return Template::get( static::getTemplateTitleName(), $this->getTemplateArguments() );
	}

	/**
	 * Get the page content for this page from its template
	 *
	 * @return string
	 */
	public function getTemplatedContent() {
		return Template::get( static::getTemplateContentName(), $this->getTemplateArguments() );
	}

	/**
	 * Save this page from the content of its template
	 *
	 * @return mixed
	 */
	public function save() {
		return $this->saveByContentSummary( $this->getTemplatedContent(), $this->getTemplatedSummary() );
	}

	/**
	 * Save this page from the content of its template if it does not exist
	 *
	 * @return mixed
	 */
	public function saveIfNotExists() {
		return $this->saveByContentSummaryIfNotExists( $this->getTemplatedContent(), $this->getTemplatedSummary() );
	}
}
