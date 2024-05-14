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
            $latLong = calculateLatAndLong($data['features'][0]['geometry']['x'], $data['features'][0]['geometry']['y']);
            return $latLong;
        } else {
            error_log("Can't Find: " . $building . ", " . $road . ", " . $block);
        }
    } catch (Exception $error) {
        error_log('Error: ' . $error->getMessage() . " X: ". $data['features'][0]['geometry']['x']. " Y: ". $data['features'][0]['geometry']['y']);
    }

}

// Function to calculate latitude and longitude
function calculateLatAndLong($x, $y)
{
    global $proj4;

    // $fromProjection = '+proj=utm +zone=39 +lat_0=38 +lon_0=127.5 +k=0.9996 +x_0=1000000 +y_0=2000000 +ellps=GRS80 +units=m +no_defs';

    // // Define the target projection
    // $toProjection = '+proj=longlat +ellps=GOOGLE +datum=GOOGLE +no_defs';

    $fromProjection = new Proj('EPSG:32639', $proj4); // UTM Zone 39N
    $toProjection = new Proj('EPSG:4326', $proj4); // WGS84

    // Create points for conversion
    $point = new Point($x, $y, $fromProjection);

    // Perform s
    $transformedPoint = $proj4->transform($toProjection, $point);

    $transformedPoint->x += -0.000410857;
    $transformedPoint->y += 0.000146322;

    return array("Lat" => $transformedPoint->y, "Long" => $transformedPoint->x);
}
