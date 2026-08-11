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

/**
 * Handle a daily ordinary PDC category
 *
 * e.g. https://it.wikipedia.org/wiki/Categoria:Cancellazioni_ordinarie_del_25_giugno_2012
 *
 * @deprecated This category has not been in use since 2013.
 *             https://it.wikipedia.org/wiki/Speciale:Diff/62875319
 *             https://it.wikipedia.org/wiki/Discussioni_utente:BotCancellazioni#c-Sakretsu-20260810150200-Categorie_delle_cancellazioni_"ordinarie"
 */
class CategoryYearMonthDayTypeOrdinary extends CategoryYearMonthDayType {

	/**
	 * PDC type
	 *
	 * @override CategoryYearMonthDay::PDC_TYPE
	 */
	const PDC_TYPE = 'ordinarie';

	/**
	 * PDC human type
	 *
	 * @override CategoryYearMonthDay::PDC_TYPE_HUMAN
	 */
	const PDC_TYPE_HUMAN = 'ordinaria';
}
