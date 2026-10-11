<?php

function printInvoicesTable ($invoices)
{
	$total_sum = 0;
	$total_debit = 0;
	echo "<h1>" . INVOICES_LIST . "</h1>\n";
	echo "<p>" . INVOICES_DOWNLOAD_TEXT . "</p>\n";
	echo "<table>\n";
	echo "<tr class='title'>";
	echo "<td>" . INVOICE_NUMBER . "</td>";
	echo "<td>" . VARSYM . "</td>";
	echo "<td>" . DATE . "</td>";
	echo "<td>" . MATURITY . "</td>";
	echo "<td>" . PAYMENT_DATE . "</td>";
	echo "<td>" . TEXT . "</td>";
	echo "<td>" . SUM . "</td>";
	echo "<td>" . DEBIT . "</td>";
	echo "</tr>\n";
        
	foreach ($invoices as $invoice)
	{
            echo "<tr onMouseOver='this.style.backgroundColor=\"" . COLOR_POINTED . "\";' onMouseOut='this.style.backgroundColor = \"\";'>";
		echo '<td>' . '<a href="../reports/invoices.php?invoice=' . rawurlencode($invoice['id']) . '">' . htmlspecialchars((string)$invoice['number']) . '</a></td>';
		echo '<td>' . $invoice['variable_symbol'] . '</td>';
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