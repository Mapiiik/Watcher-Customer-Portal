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

function formatDate ($date)
{
	if (is_string($date))
	{
		return date("d.m.Y", strtotime($date));
	}
	else
	{
		return $date;
	}
}

function formatDateTime ($date)
{
	if (is_string($date))
	{
		return date("d.m.Y H:i", strtotime($date));
	}
	else if (is_integer($date))
        {
                return date("d.m.Y H:i", $date);
        }
        else
	{
		return $date;
	}
}

function formatPrice ($price, $red = false)
{
	if ($red)
		return "<span class=\"red\">" . number_format($price, 2, ',', ' ') . "</span> Kč";
	else
		return number_format($price, 2, ',', ' ') . " Kč";
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

?>