<?php
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

require __DIR__ . '/../functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
	<title>BotCancellazioni</title>
	<meta name="robots" value="noindex" />
	<style>
	textarea {
		width: 90%;
		min-height:300px;
	}
	</style>
</head>
<body>
	<h1>BotCancellazioni</h1>
	<p>In seguito le ultime 200 righe di log di output dal <a href="https://it.wikipedia.org/wiki/Utente:BotCancellazioni">BotCancellazioni</a> che gira in Wikimedia Toolforge.</p>
	<p>Ogni esecuzione che si conclude con successo è circondata da <code>start</code> ed <code>end</code>. Fra ogni esecuzione vi dovrebbe essere massimo qualche minuto.</p>
	<textarea readonly><?php echo
		"[...]\n" .
		htmlentities( tail( '/data/project/itwiki-bot-cancellazioni/itwiki-deletionbot.out', 200 ) )
	?></textarea>
</body>
</html>
