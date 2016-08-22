<?php
/*
 * Copyright (c) 2014 David Gwynne <david@gwynne.id.au>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

class HttpS3AuthError extends Exception { };
class ExpiredS3RequestError extends HttpS3AuthError { };
class InvalidS3HeaderError extends HttpS3AuthError { };
class InvalidS3ParamsError extends HttpS3AuthError { };
class MissingS3HeaderError extends HttpS3AuthError { };

class HTTPS3Auth {
	static protected $provider = 's3.amazonaws.com';

	static function urlencode($path) {
		return implode('/', array_map('rawurlencode', explode('/', $path)));
	}

	static function sign($method, $uri, &$headers = array(), array $options = array())
	{
		$provider = isset($options['provider']) ?
		    $options['provider'] : self::$provider;

		if (is_null($headers)) {
			$headers = array(
				'Host' => $provider,
				'Date' => date(DATE_RFC1123)
			);
		} elseif (!is_array($headers)) {
			throw new Exception('headers is not an array');
		}

		if (!is_string($options['access_key'])) {
			throw new Exception('access_key is not a string');
		}
		if (!is_string($options['secret_key'])) {
			throw new Exception('secret_key is not a string');
		}

		$hdrs = array();
		foreach ($headers as $k => $v) {
			$hdrs[strtolower($k)] = $v;
		}
		ksort($hdrs);

		$sign = array($method);
		$sign[] = isset($hdrs['content-md5']) ?
		    $hdrs['content-md5'] : '';
		$sign[] = isset($hdrs['content-type']) ?
		    $hdrs['content-type'] : '';

		if (isset($hdrs['x-amz-date'])) {
			$sign[] = '';
		} elseif (isset($hdrs['date'])) {
			$sign[] = $hdrs['date'];
		} else {
			$sign[] = $headers['Date'] = date(DATE_RFC1123);
		}

		foreach ($hdrs as $k => $v) {
			if (strncmp($k, 'x-amz-', 6) != 0) {
				continue;
			}
			if (is_array($v)) {
				$v = join(',', $v);
			}
			$sign[] = "$k:$v";
		}

		if (is_array($uri)) {
			$url = $uri;
		} else {
			$url = parse_url($uri);
			if ($url === FALSE) {
				throw new InvalidS3HeaderError("uri is invalid");
			}
		}

		$resource = '';
		if (isset($hdrs['host'])) {
			/*
			 * sigh, i have to fake a lot of a url to pull the
			 * host apart from the port
			 */
			$host = parse_url("http://" . $hdrs['host'] . $url['path'], PHP_URL_HOST);

			if (strcasecmp($host, $provider) != 0 &&
			    preg_match("/^(.*)(\Q.$provider\E)\$/i", $host, $m)) {
				$resource .= "/" . $m[1];
			}
		}

		if (!isset($url['path'])) {
			throw new MissingS3HeaderError("uri doesn't contain a path");
		}
		$resource .= $url['path'];

		if (isset($url['query'])) {
			parse_str($url['query'], $query);
			$subs = array('acl', 'lifecycle', 'location', 'logging', 'notification', 'partNumber', 'policy', 'requestPayment', 'torrent', 'uploadId', 'uploads', 'versionId', 'versioning', 'versions', 'website',
			    'response-content-type', 'response-content-language', 'response-expires', 'response-cache-control', 'response-content-disposition', 'response-content-encoding');

			$q = array();
			ksort($query);
			foreach ($subs as $k) {
				if (!isset($query[$k])) {
					continue;
				}

				$s = "$k";
				if (strlen($query[$k])) {
					$s .= "=" . $query[$k];
				}
				$q[] = $s;
			}

			if (sizeof($q)) {
				$resource .= '?' . implode('&', $q);
			}
		}
		$sign[] = $resource;

		$signature = hash_hmac('sha1', implode("\n", $sign),
		    $options['secret_key'], true);

		$headers['Authorization'] = sprintf("AWS %s:%s",
		    $options['access_key'], base64_encode($signature));
	}
}
