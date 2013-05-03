<?php

/**
 * @file
 * Show the latest activity happening to a book.
 */

include_once("../includes/common.inc");

// Provide representational defaults.
$default_match_title  = 'Service Design: From Insight to Implementation';
$default_match_author = 'Andrew Polaine';
$match_title  = $default_match_title;
$match_author = $default_match_author;

// Override the defaults if we've been passed user data.
$errors = array(); // ALL THE INPUTS DAMN IT ALL THE INPUTS.
if (!empty($_REQUEST['title']) && empty($_REQUEST['author'])
   || !empty($_REQUEST['author']) && empty($_REQUEST['title'])) {
  $errors[] = 'Book title and author are both required.';
}
elseif (!empty($_REQUEST['title']) && !empty($_REQUEST['author'])) {
  $match_title  = $_REQUEST['title'];
  $match_author = $_REQUEST['author'];
}

// Grab *something*.
$data = new stdClass;
$data->book = readmill_book('match', array(
  'title'   => $match_title,
  'author'  => $match_author,
));
if (!$data->book) { // Fallback to a known and working example.
  $errors[] = 'The requested book could not be found.';
  $data->book = readmill_book('match', array(
    'title'   => $default_match_title,
    'author'  => $default_match_author,
  ));
}

if (count($errors)) { // Transmogrificate any errors into a renderable string.
  $errors = '<ul class="errors"><li>' . implode('</li><li>', $errors) . '</li></ul>';
}

// Fetch all the readings for this book. We're interested in readings
// with highlights (and comments), but we also want closing_remarks.
$data->readings = readmill_book_readings($data->book->id);

// For every reading, fetch all the highlights and comments.
$highlights_to_merge = array();
foreach ($data->readings as $reading) {
  if ($reading->highlights_count >= 1) {
    $highlights = readmill_reading_highlights($reading->id);

    foreach ($highlights as $highlight) {
      if ($highlight->comments_count >= 1) {
        $comments = readmill_highlight_comments($highlight->id);

        foreach ($comments as $comment) {
          $data->updates[strtotime($comment->posted_at)][] = array(
            'user'            => $comment->user,
            'highlight'       => $highlight,
            'comment'         => $comment,
            'content'         => htmlentities($comment->content, ENT_COMPAT, "UTF-8"),
            'permalink_url'   => 'http://readmill.com/' . $highlight->user->username
                                  . '/reads/' . $data->book->permalink . '/highlights/'
                                  . $highlight->permalink,
            'permalink_text'  => 'Reply to comment',
          );
        }
      }
      else { // No comment was left, just a happy highlight.
        $data->updates[strtotime($highlight->highlighted_at)][] = array(
          'user'            => $highlight->user,
          'highlight'       => $highlight,
          'content'         => 'shared a highlight.',
          'permalink_url'   => $highlight->permalink_url,
          'permalink_text'  => 'View highlight',
        );
      }
    }
  }
}

krsort($data->updates);
$data->updates = array_slice($data->updates, 0, 50, TRUE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Book updates for <?php print $data->book->title; ?> - Readmilling</title>
  <link rel="stylesheet" href="../misc/default.css" />
</head>
<body>
  <header class="page-toolbar toolbar">
    <div class="container">
      <div class="readmilling-explanation">
        <a href="http://www.disobey.com/d/code/readmilling/">Readmilling</a> is
        a <a href="https://github.com/morbus/readmilling">collection of scripts</a> for use with
        <a href="http://readmill.com/">Readmill's</a> API. Created by <a href="http://www.disobey.com/">Morbus Iff</a>.
        <span class="backpedaling">These scripts are not affiliated with or created by the fine chaps and chapettes at Readmill.com.</span>
      </div>
    </div>
  </header>

  <div class="page-content container">
    <?php print $errors ? $errors : NULL; ?>
    <section class="layout-primary">
      <div class="layout-primary-column">
        <header>
          <h1>
            <span class="header-group-primary"><a href="<?php print $data->book->permalink_url; ?>"><?php print $data->book->title; ?></a></span>
            <span class="header-group-secondary"><?php print $data->book->author; ?></span>
          </h1>
        </header>
        <section id="highlights">
          <h1>Latest updates</h1>
          <?php
            foreach ($data->updates as $timestamp => $updates) {
              foreach ($updates as $update) {
                print '<article class="highlight">';
                print   '<blockquote>' . htmlentities($update['highlight']->content, ENT_COMPAT, "UTF-8") . '</blockquote>';
                print   '<section class="comments">'; // Be sure to enforce UTF-8 on all htmlentities() due to PHP 5.3 defaults.
                print     '<article class="comment">';
                print       '<a href="' . $update['user']->permalink_url . '" class="image">';
                print         '<img src="' . $update['user']->avatar_url . '" alt="' . $update['user']->fullname . '" />';
                print       '</a>';
                print       '<div class="content">';
                print         '<a href="' . $update['user']->permalink_url . '" class="fullname">' . $update['user']->fullname . '</a> ';
                print         '<p>' . $update['content'] . '</p>';
                print         '<aside class="metadata">';
                print           '<time class="timestamp" datetime="' . date(DATE_ISO8601, $timestamp) . '">' . date('D, d M Y h:i:s a', $timestamp) . '</time> &middot; ';
                print           '<a href="' . $update['permalink_url'] . '">' . $update['permalink_text'] . '</a>';
                print         '</aside>';
                print       '</div>';
                print     '</article>';
                print   '</section>';
                print '</article>';
              }
            }
          ?>
        </section>
      </div>
    </section>

    <aside class="layout-secondary">
      <div class="layout-secondary-column">
        <section class="book-cover">
          <a href="<?php print $data->book->permalink_url; ?>"><img class="book-cover" src="<?php print str_replace('medium', 'original', $data->book->cover_url); ?>" /></a>
        </section>
        <section class="secondary-section">
          <form action="book-updates.php" accept-charset="UTF-8" method="get">
            <label for="title">Book title</label><input id="form-title" name="title" type="text" placeholder="<?php print htmlentities($match_title, ENT_COMPAT, "UTF-8"); ?>" required />
            <label for="author">Book author</label><input id="form-author" name="author" type="text" placeholder="<?php print htmlentities($match_author, ENT_COMPAT, "UTF-8"); ?>" required />
            <button>Load book</button>
          </form>
          <span class="warning"><strong>Be aware:</strong> If we've not seen this book before, or its data has expired, it might take 30 seconds or more before you'll get results.</span>
        </section>
        <section class="secondary-section">
          <h1>About this script</h1>
          <p>@todo</p>
        </section>
      </div>
    </aside>
  </div>

  <footer>
    <div class="container">
      <a href="http://www.disobey.com/d/code/readmilling/">Readmilling</a> is free software released
      under the <a href="http://www.gnu.org/licenses/quick-guide-gplv3.html">GPL v3</a> or later.
      <a href="https://github.com/morbus/readmilling">Browse the source code at Github.</a>
    </div>
  </footer>
</body>
</html>