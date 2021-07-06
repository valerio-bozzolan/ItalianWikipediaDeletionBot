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
 * Abstraction of a page related to a year
 */
abstract class PageYear extends PageTemplated {

	/**
	 * Year
	 *
	 * @var int
	 */
	private $year;

	/**
	 * Constructor
	 *
	 * @param $year int
	 * @see PageTemplated::__construct()
	 */
	public function __construct( $year ) {
		$this->year = (int) $year;
		parent::__construct();
	}

	/**
	 * Get the year
	 *
	 * @return int
	 */
	public function getYear() {
		return $this->year;
	}

	/**
	 * Template arguments
	 *
	 * @override PageTemplated::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		return [ $this->getYear() ];
	}

}
