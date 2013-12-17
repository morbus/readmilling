<?php

/**
 * @file
 * A set of common routines for Readmill's API and Readmilling.
 */

include_once '../settings.php';
date_default_timezone_set('GMT');

/**
 * Search for a book.
 *
 * @param array $params
 *   An array of parameters for Readmill's 'match' API lookup.
 *
 * @return object
 *   The book record on success, NULL on failure.
 */
function readmill_book_match($params = array()) {
  $result = readmill_request('books/match', $params);
  if (isset($result->status) && $result->status == 200) {
    return $result->book;
  }

  return NULL;
}

/**
 * Search for a user.
 *
 * @param string $user_search
 *   The username to search for and, hopefully, match on exactly.
 *
 * @return object
 *   The user record on success, NULL on failure.
 */
function readmill_user_search($user_search = NULL) {
  $result = readmill_request('users/search', array('query' => $user_search));
  if (isset($result->status) && $result->status == 200) {
    foreach ($result->items as $result) {
      if (strtolower($result->user->username) == strtolower($user_search)) {
        return $result->user;
      }
    }
  }

  return NULL;
}

/**
 * Retrieve all readings for a book.
 *
 * @param int $book_id
 *   The book ID to return readings for.
 * @param bool $reset
 *   Whether to reset the cache or not.
 *
 * @return array
 *   An array of all readings for this book.
 */
function readmill_book_readings($book_id, $reset = FALSE) {
  return readmill_results('book-readings', $book_id, 'books/' . $book_id . '/readings', 'reading', $reset);
}

/**
 * Retrieve all readings for a user.
 *
 * @param int $user_id
 *   The user ID to return readings for.
 * @param bool $reset
 *   Whether to reset the cache or not.
 *
 * @return array
 *   An array of all readings for this user.
 */
function readmill_user_readings($user_id, $reset = FALSE) {
  return readmill_results('user-readings', $user_id, 'users/' . $user_id . '/readings', 'reading', $reset);
}

/**
 * Retrieve all highlights for a reading.
 *
 * @param int $reading_id
 *   The reading ID to return highlights for.
 * @param bool $reset
 *   Whether to reset the cache or not.
 *
 * @return array
 *   An array of all highlights for this reading.
 */
function readmill_reading_highlights($reading_id, $reset = FALSE) {
  return readmill_results('reading-highlights', $reading_id, 'readings/' . $reading_id . '/highlights', 'highlight', $reset);
}

/**
 * Retrieve all comments for a highlight.
 *
 * @param int $highlight_id
 *   The highlight ID to return comments for.
 * @param bool $reset
 *   Whether to reset the cache or not.
 *
 * @return array
 *   An array of all comments for this highlight.
 */
function readmill_highlight_comments($highlight_id, $reset = FALSE) {
  return readmill_results('highlight-comments', $highlight_id, 'highlights/' . $highlight_id . '/comments', 'comment', $reset);
}

/**
 * Retrieve all available data for a particular object.
 *
 * @param string $cache_type
 *   book-readings: Retrieve all readings for a book id.
 *   user-readings: Retrieve all readings for a user id.
 *   reading-highlights: Retrieve all highlights of a reading id.
 *   highlight-comments: Retrieve all comments of a highlight id.
 * @param int $id
 *   The id of the cache type to return data for.
 * @param string $resource
 *   The API endpoint the live data is found at.
 * @param string $resource_key
 *   The JSON key the API results can be found under.
 * @param bool $reset
 *   Boolean; whether to reset the cache or not.
 *
 * @return array
 *   An array of all data for the passed object.
 */
function readmill_results($cache_type, $id, $resource, $resource_key, $reset = FALSE) {
  if (empty($cache_type) || empty($id) || empty($resource) || empty($resource_key)) {
    return array();
  }

  // If our cache is fresh enough, return that instead.
  $cache_file = '../cache/' . $cache_type . '/' . $id . '.json';
  if (!$reset && file_exists($cache_file) && filemtime($cache_file) > (time() - $GLOBALS['conf']['cache'][$cache_type])) {
    return json_decode(file_get_contents($cache_file));
  }

  // Cache miss. Recreate.
  $all_results = array();
  $found_all_results = 0;
  $type_params = array(
    'id'          => $id,
    'to'          => '9999-12-31T23:59:59Z',
    'count'       => 100,
  );

  // Loop until out of pages.
  while ($found_all_results == 0) {
    $results = readmill_request($resource, $type_params);

    if (isset($results->status) && $results->status == 200) {
      foreach ($results->items as $result) {
        $all_results[$result->$resource_key->id] = $result->$resource_key;
      }

      if (!empty($results->pagination->next)) {
        parse_str(parse_url($results->pagination->next, PHP_URL_QUERY), $type_params);
      }
      else {
        $found_all_results = 1;
      }
    }
  }

  // Cache it for faster futures, McFly.
  @mkdir('../cache/' . $cache_type, 0755, TRUE);
  file_put_contents($cache_file, json_encode($all_results), LOCK_EX);

  return $all_results;
}

/**
 * Request a resource from the Readmill API.
 *
 * @param string $resource
 *   The Readmill resource ("books", "readings/1234/highlights", etc.) being
 *   requested, as documented in http://developers.readmill.com/api/docs/v2/.
 *   You don't need to specify the full URL here - just the relevant resource.
 * @param array $params
 *   An array of key/values to pass to the resource.
 *   The client_id will be added automatically.
 *
 * @return object
 *   If the request was an HTTP success, the decoded JSON.
 *   If the request was an HTTP failure, NULL.
 */
function readmill_request($resource, $params = array()) {
  $url = 'https://api.readmill.com/v2/' . $resource;

  $params = array_merge($params, array(
    'client_id' => $GLOBALS['conf']['client_id'],
  ));

  return json_decode(http_request($url, $params));
}

/**
 * Request a resource using HTTP GET.
 *
 * @param string $url
 *   The URL you're requesting.
 * @param array $params
 *   An array of key/values to build a query string from.
 *
 * @return string
 *   If the request was a success: the response body as a string.
 *   If the request failed or caused an exception: NULL. Errors will
 *   also be logged to PHP's system logger.
 */
function http_request($url, $params = array()) {
  require_once 'HTTP/Request2.php';
  $request = new HTTP_Request2($url);
  $request->setConfig(array(
    'ssl_verify_peer' => FALSE,
  ));

  if ($params) {
    $url = $request->getUrl();
    $url->setQueryVariables($params);
  }

  error_log("Readmilling request: $url");

  try {
    $response = $request->send();
    if ($response->getStatus() == 200) {
      return $response->getBody();
    }
    else {
      $status = $response->getStatus();
      $phrase = $response->getReasonPhrase();
      error_log("Readmilling error: $status $phrase");
      return NULL;
    }
  }
  catch (HTTP_Request2_Exception $e) {
    error_log("Readmilling error: " . $e->getMessage());
    return NULL;
  }
}

/**
 * Disable output buffering in Apache, PHP, and certain browsers.
 */
function disable_output_buffering() {
  @apache_setenv('no-gzip', 1);
  @ini_set('zlib.output_compression', 0);
  @ini_set('implicit_flush', 1);
  @ob_end_flush();
  @ob_implicit_flush(1);

  // Safari and IE need a payload of a certain number of bytes to start.
  // Safari might need to see the rise and fall of an HTML element.
  // print '<span class="buffering" />';
  print str_pad('', 1024);
}
