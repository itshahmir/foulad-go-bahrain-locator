<?php

require foulad_go_path . 'vendor/autoload.php'; // Include Composer autoload

use Proj4php\Proj;
use proj4php\Proj4php;
use proj4php\Point;

$proj4 = new Proj4php();
// Function to fetch location data
function getLocation($building, $road, $block)
{
    global $proj4;
    try {
        $fromProjection = new Proj('EPSG:32639', $proj4); // UTM Zone 39N
        $toProjection = new Proj('EPSG:4326', $proj4); // WGS84

        $url = 'https://www.locatorservices.gov.bh/locatorproxy/proxy.ashx?https%3a%2f%2fwww.locatorservices.gov.bh%2fArcGIS%2frest%2fservices%2fLocatorServices%2fBahrainLocator_Query%2fMapServer%2f2%2fquery%3freturnGeometry%3dtrue%26spatialRel%3desriSpatialRelIntersects%26where%3dBLOCK_NO%253d' . $block . '%2bAND%2bROAD_NO%253d' . $road . '%2bAND%2bBUILDING_NO%253d' . $building . '%26outFields%3dBLOCK_NO%252cROAD_NO%252cBUILDING_NO%26f%3djson%26';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);

        if ($data && isset($data['features']) && count($data['features']) > 0) {
            $latLong = calculateLatAndLong($fromProjection, $toProjection, $data['features'][0]['geometry']['x'], $data['features'][0]['geometry']['y']);
            return $latLong;
        } else {
            echo "Can't Find: " . $building . ", " . $road . ", " . $block;
        }
    } catch (Exception $error) {
        echo 'Error: ' . $error->getMessage();
    }
}

// Function to calculate latitude and longitude
function calculateLatAndLong($fromProjection, $toProjection, $x, $y)
{
    global $proj4;
    // Create points for conversion
    $point = new Point($x, $y, $fromProjection);

    // Perform s
    $transformedPoint = $proj4->transform($toProjection, $point);

    return array("Lat" => $transformedPoint->y, "Long" => $transformedPoint->x);
}
