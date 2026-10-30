<?php

/**
 * The API contract Gluo's SPA relies on, checked against any of the builds:
 *
 *   php frameworks/api/contract-test.php http://localhost:9081 http://localhost:7081
 *
 * The first argument is the API, the second the origin of its SPA (for CORS).
 * It logs in with the seed users, reads, creates, updates and deletes a product, and
 * exits with 1 when a check fails. The product it creates is left soft-deleted.
 */

[$baseUrl, $origin] = [rtrim($argv[1] ?? 'http://localhost:8080', '/'), $argv[2] ?? 'http://localhost:7080'];
$failures = 0;

function call(string $method, string $path, ?array $body = null, ?string $token = null, array $headers = []): array
{
    global $baseUrl;
    $headers = array_merge($headers, $body !== null ? ['Content-Type: application/json'] : []);
    if ($token !== null) {
        $headers[] = "Authorization: Bearer $token";
    }
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $body !== null ? json_encode($body) : '',
        'ignore_errors' => true,
        'follow_location' => 0,
    ]]);
    $raw = file_get_contents($baseUrl . $path, false, $context);
    preg_match('/^HTTP\/\S+ (\d+)/', $http_response_header[0] ?? '', $match);
    $responseHeaders = [];
    foreach (array_slice($http_response_header ?? [], 1) as $line) {
        [$name, $value] = array_pad(explode(':', $line, 2), 2, '');
        $responseHeaders[strtolower(trim($name))] = trim($value);
    }

    return ['status' => (int)($match[1] ?? 0), 'body' => json_decode((string)$raw, true), 'headers' => $responseHeaders];
}

function check(string $what, bool $ok, mixed $detail = null): void
{
    global $failures;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $what . ($ok || $detail === null ? '' : ' -- ' . json_encode($detail)) . "\n";
    $failures += $ok ? 0 : 1;
}

/** The payload of a JWT, or null when the token is not one */
function jwtPayload(mixed $token): ?array
{
    $parts = is_string($token) ? explode('.', $token) : [];
    $payload = count($parts) === 3 ? json_decode((string)base64_decode(strtr($parts[1], '-_', '+/')), true) : null;

    return is_array($payload) ? $payload : null;
}

function login(string $user): array
{
    return call('POST', '/login', ['username' => $user, 'password' => '!P4ssw0rdstr!']);
}

echo "Contract of $baseUrl\n";

// Login: a JWT the SPA reads exp from, and the user
$response = login('nobody@example.com');
check('a wrong login answers 401', $response['status'] === 401, $response);
$response = login('admin@example.com');
$admin = $response['body']['token'] ?? null;
$payload = jwtPayload($admin);
check('login answers 200 with a token', $response['status'] === 200 && is_string($admin), $response);
check('the token is a JWT whose exp is in the future', isset($payload['exp']) && $payload['exp'] > time(), $payload);
check('login answers the user: name and role', ($response['body']['data']['name'] ?? '') !== '' && ($response['body']['data']['role'] ?? '') === 'admin', $response['body']['data'] ?? null);
$user = login('user@example.com')['body']['token'] ?? null;
check('the regular user logs in', is_string($user));

// The list: a plain array, paging from 0, ordering
$response = call('GET', '/product');
check('the list without a token answers 401', $response['status'] === 401, $response['status']);
$names = fn (array $response) => array_column(is_array($response['body']) ? $response['body'] : [], 'name');
$response = call('GET', '/product?page=0&size=2&orderBy=name,id', null, $admin);
check('the list is a plain array, ordered and paged', $response['status'] === 200 && $names($response) === ['Headset', 'Keyboard'], $response['body']);
$product = $response['body'][0] ?? [];
check('a product has id, name, price, createdAt, updatedAt, deletedAt', array_keys($product) == ['id', 'name', 'price', 'createdAt', 'updatedAt', 'deletedAt'], array_keys($product));
check('the price is a number', is_int($product['price'] ?? null) || is_float($product['price'] ?? null), $product['price'] ?? null);
check('the dates are Y-m-d H:i:s', (bool)preg_match('/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', (string)($product['createdAt'] ?? '')), $product['createdAt'] ?? null);
$response = call('GET', '/product?page=1&size=2&orderBy=name,id', null, $admin);
check('the second page', $names($response) === ['Monitor', 'Mouse'], $names($response));
$response = call('GET', '/product?page=0&size=3&orderBy=' . rawurlencode('name desc,id'), null, $admin);
check('a descending order', $names($response) === ['Webcam', 'Mouse', 'Monitor'], $names($response));

// Create, read, update, delete
$response = call('POST', '/product', ['name' => 'Contract Test', 'price' => 12.5], $admin);
$id = $response['body']['id'] ?? null;
check('the admin creates a product, answered with its id', $response['status'] >= 200 && $response['status'] < 300 && $id !== null, $response);
$response = call('GET', "/product/$id", null, $admin);
check('the product reads back', $response['status'] === 200 && ($response['body']['name'] ?? '') === 'Contract Test' && (float)($response['body']['price'] ?? 0) === 12.5, $response);
foreach ([['name' => '', 'price' => 1], ['name' => 'No price'], ['name' => 'Negative', 'price' => -1], ['name' => str_repeat('x', 101), 'price' => 1]] as $invalid) {
    $response = call('POST', '/product', $invalid, $admin);
    check('an invalid product is refused with a message: ' . json_encode($invalid), in_array($response['status'], [400, 422], true) && (($response['body']['error']['message'] ?? $response['body']['message'] ?? '') !== ''), $response);
}
$response = call('PUT', "/product/$id", ['name' => 'Contract Test 2', 'price' => 13], $admin);
check('the admin updates it', $response['status'] >= 200 && $response['status'] < 300, $response);
check('the update reads back', (call('GET', "/product/$id", null, $admin)['body']['name'] ?? '') === 'Contract Test 2');
foreach ([['POST', '/product', ['name' => 'Not allowed', 'price' => 1]], ['PUT', "/product/$id", ['name' => 'Not allowed', 'price' => 1]], ['DELETE', "/product/$id", null]] as [$method, $path, $body]) {
    $response = call($method, $path, $body, $user);
    check("a regular user gets 403 on $method", $response['status'] === 403, $response['status']);
}
$response = call('DELETE', "/product/$id", null, $admin);
check('the admin deletes it', $response['status'] >= 200 && $response['status'] < 300, $response);
check('a deleted product answers 404', call('GET', "/product/$id", null, $admin)['status'] === 404);
check('a deleted product leaves the list', !in_array('Contract Test 2', $names(call('GET', '/product?page=0&size=100&orderBy=id', null, $admin)), true));

// Refresh: a new token, or 401 for an API that only refreshes in the last minutes (Gluo: 5)
$response = call('POST', '/refreshtoken', null, $admin);
$refreshed = jwtPayload($response['body']['token'] ?? null);
check('refreshtoken answers a new JWT, or 401 before the last minutes', ($response['status'] === 200 && isset($refreshed['exp']) && $refreshed['exp'] > time()) || $response['status'] === 401, $response);

// CORS: the browser asks before sending the Authorization header from the SPA's origin
$response = call('OPTIONS', '/product', null, null, ["Origin: $origin", 'Access-Control-Request-Method: GET', 'Access-Control-Request-Headers: authorization']);
$allowed = $response['headers']['access-control-allow-origin'] ?? '';
check("CORS allows $origin", in_array($allowed, [$origin, '*'], true), $response['headers']);

echo $failures === 0 ? "All checks passed\n" : "$failures checks failed\n";
exit($failures === 0 ? 0 : 1);
