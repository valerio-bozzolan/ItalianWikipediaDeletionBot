<!-- START DOCUMENTATION -->
This is the template for a single running PDC in the daily counting page.

Placeholders:
	%1$s: page title
	%2$d: temperature
	%3$s: '1' if it's multiple
	%4$s: color associated to the PDC e.g. '#fff'
	%5%d: number of the row
	%6$s: PDC type e.g. 'consensuale'
	%7%s: duration e.g. 'un giorno'
	      see the template DURATION_*.tpl
	%8%s: title of the log page
	%9$s: human date
	%10$s: turnover number e.g. 2 when '/2'
	%11$s: subject themes comma separated e.g. 'software libero, Abruzzo'

<!-- START SPECIFIC EXAMPLE -->
{{Conteggio cancellazioni/In corso/Voce|i = 1 |voce = ASD |turno = 2 |tipo = votazione |data = 2018 maggio 3 |multipla = 1|argomenti = Abruzzo }}

<!-- START TEMPLATE -->
{{Conteggio cancellazioni/In corso/Voce|i = %5$d |voce = %1$s |turno = %10$s |tipo = %6$s |data = %9$s |multipla = %3$s |argomenti = %11$s }}
