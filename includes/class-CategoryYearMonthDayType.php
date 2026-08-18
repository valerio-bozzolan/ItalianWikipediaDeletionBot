<?php
# it.wiki deletion bot in PHP
# Copyright (C) 2013 Mauro742, MauroBot
# 	https://it.wikipedia.org/wiki/Utente:Mauro742
# 	https://it.wikipedia.org/wiki/Utente:MauroBot
# 	Originally under Creative Commons BY SA 3.0 International
# 	Utente:MauroBot/BotCancellazioni/category.js
# 	https://creativecommons.org/licenses/by-sa/3.0/
#   https://wikimediafoundation.org/wiki/Special:MyLanguage/Terms_of_Use/it
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

/**
 * Abstraction of a daily category with a specified PDC type
 */
abstract class CategoryYearMonthDayType extends CategoryYearMonthDay {

	/**
	 * Template name of this category
	 *
	 * @override CategoryTemplated::TEMPLATE_NAME
	 */
	const TEMPLATE_NAME = 'CATEGORY_DAY_PDCTYPE';

	/**
	 * Title format
	 *
	 * Used to describe both the plain text title and its matching pattern.
	 *
	 * Don't use placeholders different from '%s'.
	 *
	 * Arguments:
	 *    1: PDC type
	 * 	4: day
	 * 	3: human month
	 * 	2: year
	 *
	 * @override CategoryYearMonthDay::TITLE_FORMAT
	 */
	const TITLE_FORMAT = 'Categoria:Cancellazioni %s del %s %s %s';

	/**
	 * Title format arguments
	 *
	 * Arguments that can create this page title, when filling the title format.
	 *
	 * @return array Arguments for the TITLE_FORMAT
	 * @override CategoryYearMonthDay::getTitleFormatArguments()
	 */
	protected function getTitleFormatArguments() {
		return [
			static::PDC_TYPE,      // 1: PDC type
			$this->getDay(),       // 2: day
			$this->getMonthName(), // 3: month name
			$this->getYear(),      // 4: year
		];
	}

	/**
	 * Title format regex groups
	 *
	 * Regex groups that can create a regex for this page title, when filling the title format.
	 *
	 * @return array Array of regex groups for the TITLE_FORMAT
	 * @override CategoryYearMonthDay::titleFormatGroups()
	 */
	protected static function titleFormatGroups() {
		return [
			preg_quote( static::PDC_TYPE ), // 1: PDC type (it's correct that is not grouped)
			'([0-9]{1,2})',                 // 2: day
			'([a-z]+)',                     // 3: month name
			'([0-9]{4})',                   // 4: year
		];
	}

	/**
	 * Get template arguments
	 *
	 * 1: category title
	 * 2: year
	 * 3: month  1-12
	 * 4: month name
	 * 5: day 1-31
	 * 6: PDC type
	 *
	 * @override CategoryTemplated::getTemplateArguments()
	 */
	public function getTemplateArguments() {
		$parent_arguments = parent::getTemplateArguments();
		$parent_arguments[] = static::PDC_TYPE;
		return $parent_arguments;
	}

	#[Override]
	public function getShouldBeCreatedEvenIfEmpty(): bool
	{
		// All specific categories like 'Categoria:Cancellazioni %s del %s %s %s'
		// should not be created if are empty.
		return false;
	}
}
