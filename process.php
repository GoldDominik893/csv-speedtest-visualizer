<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"]["tmp_name"];

    if (($handle = fopen($file, "r")) !== FALSE) {
        $downloadSpeeds = array();
        $uploadSpeeds = array();
        $labels = array();
        $downloadSum = 0;
        $uploadSum = 0;
        $count = 0;
        $locations = array();

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $download = intval($data[5]) / 1000; // Convert download speed from bits to Mbps
            $upload = intval($data[7]) / 1000;   // Convert upload speed from bits to Mbps
            $downloadSpeeds[] = $download;
            $uploadSpeeds[] = $upload;
            $downloadSum += $download;
            $uploadSum += $upload;
            $count++;

            // Labeling using date/time (1st column)
            $labels[] = $data[0];

            // Collecting location data
            $lat = floatval($data[3]);
            $lon = floatval($data[4]);
            if ($lat != 0 && $lon != 0) {
                $locations[] = array("lat" => $lat, "lon" => $lon, "date" => $data[0], "download" => $download, "upload" => $upload);
            }
        }
        fclose($handle);

        $averageDownload = ($count > 0) ? ($downloadSum / $count) : 0;
        $averageUpload = ($count > 0) ? ($uploadSum / $count) : 0;

        // HTML output
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Speed Test Results</title>
            <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
            <link rel="stylesheet" href="main.css">
        </head>
        <body>';

        // Output average speeds
        echo "<h3>Average Download Speed: " . number_format($averageDownload, 2) . " Mbps</h3>";
        echo "<h3>Average Upload Speed: " . number_format($averageUpload, 2) . " Mbps</h3>";

        // Generate a simple line graph using the data
        echo "<h3>Graph</h3>";
        echo "<canvas id='speedChart' width='400' height='200'></canvas>";

        // JavaScript to plot the graph using Chart.js library
        echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
        echo "<script>";
        echo "var ctx = document.getElementById('speedChart').getContext('2d');";
        echo "var myChart = new Chart(ctx, {";
        echo "    type: 'line',";
        echo "    data: {";
        echo "        labels: " . json_encode($labels) . ",";
        echo "        datasets: [{";
        echo "            label: 'Download Speed (Mbps)',";
        echo "            data: " . json_encode($downloadSpeeds) . ",";
        echo "            borderColor: 'rgb(75, 192, 192)',";
        echo "            tension: 0.1";
        echo "        }, {";
        echo "            label: 'Upload Speed (Mbps)',";
        echo "            data: " . json_encode($uploadSpeeds) . ",";
        echo "            borderColor: 'rgb(192, 75, 192)',";
        echo "            tension: 0.1";
        echo "        }]";
        echo "    },";
        echo "    options: {";
        echo "        tooltips: {";
        echo "            callbacks: {";
        echo "                label: function(tooltipItem, data) {";
        echo "                    var label = data.datasets[tooltipItem.datasetIndex].label || '';";
        echo "                    if (label) {";
        echo "                        label += ': ';";
        echo "                    }";
        echo "                    label += tooltipItem.yLabel + ' Mbps';";
        echo "                    return label;";
        echo "                }";
        echo "            }";
        echo "        }";
        echo "    }";
        echo "});";
        echo "</script>";


        // Map container
        echo "<div id='map'></div><br>";

        // Generate the map with Leaflet.js
        echo "<script src='https://unpkg.com/leaflet/dist/leaflet.js'></script>";
        echo "<script>";
        echo "var map = L.map('map').setView([0, 0], 2);"; // Set default view
        echo "L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {";
        echo "    maxZoom: 19,";
        echo "}).addTo(map);";

        // Add markers to the map
        echo "var bounds = new L.LatLngBounds();";
        foreach ($locations as $location) {
            echo "var marker = L.marker([" . $location['lat'] . ", " . $location['lon'] . "]).addTo(map);";
            echo "marker.bindPopup('<b>Date:</b> " . $location['date'] . "<br><b>Download:</b> " . $location['download'] . " Mbps<br><b>Upload:</b> " . $location['upload'] . " Mbps');";
            echo "bounds.extend(marker.getLatLng());";
        }
        echo "map.fitBounds(bounds);"; // Fit the map to show all markers
        echo "</script>";

        echo "</body></html>";
    } else {
        echo "Failed to open the uploaded file.";
    }
}
