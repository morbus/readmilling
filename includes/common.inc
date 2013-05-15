<?php

include_once('../settings.php');
date_default_timezone_set('GMT');

/**
 * Search for a book.
 *
 * @param $params
 *   An array of parameters for Readmill's 'match' API lookup.
 *
 * @return
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
 * @param $username
 *   The username to search for and, hopefully, match on exactly.
 *
 * @return
 *   The user record on success, NULL on failure.
 */
function readmill_user_search($user_search = NULL) {
  $result = readmill_request('users/search', array('query' => $user_search));
  if (isset($result->status) && $result->status == 200) {
    foreach ($result->items as $result) { // No fuzzies.
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
 * Retrieving all readings without any API filters might seem a bit costly.
 * Caching-wise, it's a lot easier to store (or re-request) all of a book's
 * readings than it is to pick through individual or filtered storage.
 *
 * @param $book_id
 *   The book ID to return readings for.
 * @param $reset
 *   Whether to reset the cache or not.
 *
 * @return
 *   An array of all readings for this book.
 */
function readmill_book_readings($book_id, $reset = FALSE) {
  $cache_file = "../cache/book-readings/$book_id.json";

  // If our cache is fresh enough per our configuration, use that.
  if (!$reset && file_exists($cache_file) && filemtime($cache_file) > (time() - $GLOBALS['conf']['cache']['book_readings'])) {
    return json_decode(file_get_contents($cache_file));
  }

  // Cache miss. Recreate.
  $all_readings = array();
  $found_all_readings = 0;
  $readings_params = array(
    'id'          => $book_id,
    'to'          => '9999-12-31T23:59:59Z',
    'order'       => 'touched_at',
    'count'       => 100,
  );

  while ($found_all_readings == 0) {
    $readings = readmill_request('books/' . $book_id . '/readings', $readings_params);

    if (isset($readings->status) && $readings->status == 200) {
      foreach ($readings->items as $reading) {
        $all_readings[$reading->reading->id] = $reading->reading;

        // Update boundary date for future requests.
        if (!empty($reading->reading->touched_at)) {
          $readings_params['to'] = $reading->reading->touched_at;
        }
      }

      // If it wasn't max count, we're done.
      if (count($readings->items) != 100) {
        $found_all_readings = 1;
      }
    }
  }

  // Cache it for faster futures, McFly.
  @mkdir('../cache/book-readings/', 0755, TRUE);
  file_put_contents($cache_file, json_encode($all_readings), LOCK_EX);

  return $all_readings;
}

/**
 * Retrieve all readings for a user.
 *
 * @param $user_id
 *   The user ID to return readings for.
 * @param $reset
 *   Whether to reset the cache or not.
 *
 * @return
 *   An array of all readings for this user.
 */
function readmill_user_readings($user_id, $reset = FALSE) {
  $cache_file = "../cache/user-readings/$user_id.json";

  // If our cache is fresh enough per our configuration, use that.
  if (!$reset && file_exists($cache_file) && filemtime($cache_file) > (time() - $GLOBALS['conf']['cache']['user_readings'])) {
    return json_decode(file_get_contents($cache_file));
  }

  // Cache miss. Recreate.
  $all_readings = array();
  $found_all_readings = 0;
  $readings_params = array(
    'id'          => $user_id,
    'to'          => '9999-12-31T23:59:59Z',
    'order'       => 'touched_at',
    'count'       => 100,
  );

  while ($found_all_readings == 0) {
    $readings = readmill_request('users/' . $user_id . '/readings', $readings_params);

    if (isset($readings->status) && $readings->status == 200) {
      foreach ($readings->items as $reading) {
        $all_readings[$reading->reading->id] = $reading->reading;

        // Update boundary date for future requests.
        if (!empty($reading->reading->touched_at)) {
          $readings_params['to'] = $reading->reading->touched_at;
        }
      }

      // If it wasn't max count, we're done.
      if (count($readings->items) != 100) {
        $found_all_readings = 1;
      }
    }
  }

  // Cache it for faster futures, McFly.
  @mkdir('../cache/user-readings/', 0755, TRUE);
  file_put_contents($cache_file, json_encode($all_readings), LOCK_EX);

  return $all_readings;
}

/**
 * Retrieve all highlights for a reading.
 *
 * @param $reading_id
 *   The reading ID to return highlights for.
 * @param $reset
 *   Whether to reset the cache or not.
 *
 * @return
 *   An array of all highlights for this reading.
 */
function readmill_reading_highlights($reading_id, $reset = FALSE) {
  $cache_file = "../cache/reading-highlights/$reading_id.json";

  // If our cache is fresh enough per our configuration, use that.
  if (!$reset && file_exists($cache_file) && filemtime($cache_file) > (time() - $GLOBALS['conf']['cache']['reading_highlights'])) {
    return json_decode(file_get_contents($cache_file));
  }

  // Cache miss. Recreate.
  $all_highlights = array();
  $found_all_highlights = 0;
  $highlights_params = array(
    'id'          => $reading_id,
    'to'          => '9999-12-31T23:59:59Z',
    'order'       => 'highlighted_at',
    'count'       => 100,
  );

  while ($found_all_highlights == 0) {
    $highlights = readmill_request('readings/' . $reading_id . '/highlights', $highlights_params);

    if (isset($highlights->status) && $highlights->status == 200) {
      foreach ($highlights->items as $highlight) {
        $all_highlights[$highlight->highlight->id] = $highlight->highlight;

        // Update boundary date for future requests.
        if (!empty($highlight->highlight->highlighted_at)) {
          $highlights_params['to'] = $highlight->highlight->highlighted_at;
        }
      }

      // If it wasn't max count, we're done.
      if (count($highlights->items) != 100) {
        $found_all_highlights = 1;
      }
    }
  }

  // Cache it for faster futures, Seldon.
  @mkdir('../cache/reading-highlights/', 0755, TRUE);
  file_put_contents($cache_file, json_encode($all_highlights), LOCK_EX);

  return $all_highlights;
}

/**
 * Retrieve all comments for a highlight.
 *
 * @param $highlight_id
 *   The highlight ID to return comments for.
 * @param $reset
 *   Whether to reset the cache or not.
 *
 * @return
 *   An array of all comments for this highlight.
 */
function readmill_highlight_comments($highlight_id, $reset = FALSE) {
  $cache_file = "../cache/highlight-comments/$highlight_id.json";

  // If our cache is fresh enough per our configuration, use that.
  if (!$reset && file_exists($cache_file) && filemtime($cache_file) > (time() - $GLOBALS['conf']['cache']['highlight_comments'])) {
    return json_decode(file_get_contents($cache_file));
  }

  // Cache miss. Recreate.
  $all_comments = array();
  $found_all_comments = 0;
  $comments_params = array(
    'id'          => $highlight_id,
    'to'          => '9999-12-31T23:59:59Z',
    'order'       => 'posted_at',
    'count'       => 100,
  );

  while ($found_all_comments == 0) {
    $comments = readmill_request('highlights/' . $highlight_id . '/comments', $comments_params);

    if (isset($comments->status) && $comments->status == 200) {
      foreach ($comments->items as $comment) {
        $all_comments[$comment->comment->id] = $comment->comment;

        // Update boundary date for future requests.
        if (!empty($comment->comment->posted_at)) {
          $comments_params['to'] = $comment->comment->posted_at;
        }
      }

      // If it wasn't max count, we're done.
      if (count($comments->items) != 100) {
        $found_all_comments = 1;
      }
    }
  }

  // Cache it for faster futures, Beckett.
  @mkdir('../cache/highlight-comments/', 0755, TRUE);
  file_put_contents($cache_file, json_encode($all_comments), LOCK_EX);

  return $all_comments;
}

/**
 * Request a resource from the Readmill API.
 *
 * @param $resource
 *   The Readmill resource ("books", "readings/1234/highlights", etc.) being
 *   requested, as documented in http://developers.readmill.com/api/docs/v2/.
 *   You don't need to specify the full URL here - just the relevant resource.
 * @param $params
 *   An array of key/values to pass to the resource.
 *   The client_id will be added automatically.
 *
 * @return
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
 * @param $url
 *   The URL you're requesting.
 * @param $params
 *   An array of key/values to build a query string from.
 *
 * @return
 *   If the request was a success: the response body as a string.
 *   If the request failed or caused an exception: NULL. Errors will
 *   also be logged to PHP's system logger.
 */
function http_request($url, $params = array()) {
  require_once('HTTP/Request2.php');
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
