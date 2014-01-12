# php-http-s3auth

HTTP S3 Authentication library for PHP.

This was written to compare the mechanics/semantics of S3 Auth
against HTTP Signature Authentication.

## Usage

### Client

```php
require_once('http-s3auth.php');

HTTPS3Auth::sign('GET', '/', $headers, array(
	'access_key' => 'AKIAIOSFODNN7EXAMPLE'
	'secret_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'
));

// It's funny how much PHP sucks at HTTP things
$h = array();
foreach ($headers as $k => $v) {
	$h[] = "$k: $v";
}

$ch = curl_init('https://s3.amazonaws.com/');
curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
curl_exec($ch);
curl_close($ch);
```

### Server

Maybe one day...
