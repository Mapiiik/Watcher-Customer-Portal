<?php

function formatName ($customer, $with_company = true)
{
        $name = '';
	
        if ($with_company && isset($customer["company"])) $name .= "[" . $customer["company"] . "]";
	if (isset($customer["title"])) {
            if ($name <> '') $name .= " ";
            $name .= $customer["title"];
        }
        if (isset($customer["first_name"])) {
            if ($name <> '') $name .= " ";
            $name .= $customer["first_name"];
        }
        if (isset($customer["last_name"])) {
            if ($name <> '') $name .= " ";
            $name .= $customer["last_name"];
        }
	if (isset($customer["suffix"])) {
            if ($name <> '') $name .= " ";
            $name .= $customer["suffix"];
        }
	
	return $name;
}

// The headers of a page: HTML with the customer's data, which no cache keeps
function pageHeaders ()
{
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: no-store');
}

// A date as the locale writes it, or what was given when it is no date
function formatDate ($date)
{
	$time = is_string($date) && $date !== '' ? strtotime($date) : false;
	if ($time === false) return $date;

	return (new IntlDateFormatter(LOCALE, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE))->format($time);
}

// A date and time as the locale writes them, from text or a timestamp
function formatDateTime ($date)
{
	$time = is_integer($date) ? $date : (is_string($date) && $date !== '' ? strtotime($date) : false);
	if ($time === false) return $date;

	return (new IntlDateFormatter(LOCALE, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT))->format($time);
}

// An amount in the operator's currency, as the locale writes it
function formatPrice ($price, $red = false)
{
	$text = (new NumberFormatter(LOCALE, NumberFormatter::CURRENCY))->formatCurrency((float)$price, CURRENCY);

	return $red ? '<span class="red">' . $text . '</span>' : $text;
}

// The address of a file in the profile's assets/
function brandingUrl ($file)
{
	return PREFIX . '/branding.php?file=' . rawurlencode($file);
}

// An image of the profile, or nothing when the profile has none
function profileImage ($image)
{
	if (!is_array($image) || empty($image['file'])) return '';

	$width = empty($image['width']) ? '' : ' width="' . (int)$image['width'] . '"';
	return '<img src="' . htmlspecialchars(brandingUrl($image['file'])) . '" alt="Logo"' . $width . ' />';
}

// The links to the icons of the profile, for the head of a page
function profileIcons ($icons)
{
	$links = '';
	foreach (is_array($icons) ? $icons : [] as $icon)
	{
		if (empty($icon['file'])) continue;

		$type = strtolower(pathinfo($icon['file'], PATHINFO_EXTENSION)) === 'png' ? ' type="image/png"' : '';
		$sizes = empty($icon['sizes']) ? '' : ' sizes="' . htmlspecialchars($icon['sizes']) . '"';
		$links .= "\t" . '<link rel="' . htmlspecialchars($icon['rel'] ?? 'icon') . '" href="' . htmlspecialchars(brandingUrl($icon['file'])) . '"' . $type . $sizes . ' />' . "\n";
	}
	return $links;
}

?>