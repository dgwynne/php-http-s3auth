# php-http-s3auth

HTTP S3 Authentication library for PHP.

This was written to compare the mechanics/semantics of S3 Auth
against HTTP Signature Authentication.

## Usage

### `HTTPS3Auth::sign($method, $url, &$headers, array $options = [])`

`sign()` signs a URL for a future request. The signature generated
is added to the `$headers` array.

The method the URL is to be requested with is specified via the
`$method` argument. Examples of methods are `GET`, `HEAD`, and
`POST`.

The URL to be signed is specified via `$url`. The URL must contain
at least the path component of the URL. Other components, such as
the scheme and host are optional. The URL may be passed as a string,
or as an array like what is returned by `parse_url()`.

The set of headers for the current request are specified with the
`$headers` argument. The `Authorization` header containing the
generated signature is added to this array for use in subsequent
HTTP requests.

Parameters for the signature are specified using the `$options`
array. The following parameters are mandatory:

- The Access Key ID is specified with `$options['access_key']`.
- The Secret Access Key is specified with `$options['secret_key']`.

The following parameters are optional:

- The provider may be specified with `$options['provider']`. The default provider is `s3.amazonaws.com`.

### `HTTPS3Auth::urlencode($path)`

This function is provided to correctly encode the path component
of URLs for use in subsequent `sign()` calls and HTTP requests.

### Client

```php
require_once('http-s3auth.php');

HTTPS3Auth::sign('GET', '/', $headers, array(
	'access_key' => 'AKIAIOSFODNN7EXAMPLE',
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
