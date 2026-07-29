<?php

header('Content-Type: application/json');

echo json_encode([
    "success" => true,
    "api" => "MyFantasy API",
    "version" => "1.0"
]);