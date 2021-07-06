<!-- START DOCUMENTATION -->
This is the template for a section of a daily counting page.

This is the section for running PDCs.

Placeholders:
	%1$d:   year
	%2$d:   month  1-12
	%2$02d: month 01-12
	%3$s:   month name
	%4$d:   day    1-31
	%4$02d: day   01-31
	%5$s:   PDC rows

<!-- START TEMPLATE -->
{{Conteggio cancellazioni/In corso/Start|data=%1$d %3$s %4$d}}
%5$s
{{Conteggio cancellazioni/In corso/Stop}}
