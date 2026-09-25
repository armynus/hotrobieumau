<?php
require 'D:/hotrobieumau/vendor/autoload.php';
$request = Illuminate\Http\Request::capture();
$generator = new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), $request);
if (isset($_GET['redirect'])) {
    header('Location: '.$generator->to('/login_admin'), true, 302);
    exit;
}
header('Content-Type: application/json');
echo json_encode(['host' => $request->getHost(), 'origin' => $request->getSchemeAndHttpHost(), 'url' => $generator->to('/login_admin'), 'query' => $request->query('q'), 'method' => $request->method(), 'post' => $request->input('sample')]);