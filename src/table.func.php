<?php

function printInvoicesTable ($invoices)
{
	$total_sum = 0;
	$total_debit = 0;
	echo "<h1>" . __('Invoices') . "</h1>\n";
	echo "<p>" . __('Click an invoice number to show the invoice') . "</p>\n";
	echo "<table class='pointed'>\n";
	echo "<tr class='title'>";
	echo "<td>" . __('Invoice number') . "</td>";
	echo "<td>" . __('Variable symbol') . "</td>";
	echo "<td>" . __('Date') . "</td>";
	echo "<td>" . __('Due date') . "</td>";
	echo "<td>" . __('Paid') . "</td>";
	echo "<td>" . __('Text') . "</td>";
	echo "<td>" . __('Total') . "</td>";
	echo "<td>" . __('Debt') . "</td>";
	echo "</tr>\n";
        
	foreach ($invoices as $invoice)
	{
            echo "<tr>";
		echo '<td>' . '<a href="../reports/invoices.php?invoice=' . rawurlencode($invoice['id']) . '">' . htmlspecialchars((string)$invoice['number']) . '</a></td>';
		echo '<td>' . htmlspecialchars((string)$invoice['variable_symbol']) . '</td>';
		echo '<td>' . formatDate($invoice['creation_date']) . '</td>';
		echo '<td>' . formatDate($invoice['due_date']) . '</td>';
		echo '<td>' . formatDate($invoice['payment_date']) . '</td>';
		echo '<td>' . htmlspecialchars((string)$invoice['text']) . '</td>';
		echo '<td class="price">' . FormatPrice($invoice['total'], false) . '</td>';
		$total_sum += $invoice['total'];
		if ($invoice['debt'] > 0)
			echo '<td class="fred price">' . FormatPrice($invoice['debt'], false) . '</td>';
		else
			echo '<td class="price">' . FormatPrice($invoice['debt'], false) . '</td>';
		$total_debit += $invoice['debt'];
		echo "</tr>\n";
	}
	echo "<tr class='title'>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo '<td class="price">' . FormatPrice($total_debit, false) . "</td>";
	echo "</tr>\n";
	echo "</table>\n";
}
?>