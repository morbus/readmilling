<?php

/**
 * @file
 * Attempt to merge all comments on similar highlights into a single entry.
 */

include_once("../includes/common.inc");

// Provide representational defaults.
$default_match_title  = 'HTML5 for Web Designers';
$default_match_author = 'Jeremy Keith';
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
$data->book = readmill_book_match(array(
  'title'   => $match_title,
  'author'  => $match_author,
));
if (!$data->book) { // Fallback to a known and working example.
  $errors[] = 'The requested book could not be found.';
  $data->book = readmill_book_match(array(
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

// For every reading, fetch all the highlights and then any comments that
// have been left on those highlights. This is a comment-centric view -- if
// a highlight has no comments, we're not interested.
$highlights_to_merge = array();
foreach ($data->readings as $reading) {
  if ($reading->highlights_count >= 1) {
    $highlights = readmill_reading_highlights($reading->id);

    foreach ($highlights as $highlight) {
      if ($highlight->comments_count >= 1) {
        $comments = readmill_highlight_comments($highlight->id);

        // Store the highlight indexed at the highlight position. This allows us
        // to do a quick key sort to put all highlights in the order they were
        // positioned in the book (approximately, given multiple book formats). No GPS? End of the line.
        $highlight->locators->position = isset($highlight->locators->position) ? $highlight->locators->position : 9999;
        $position = (string) $highlight->locators->position; // String 'em so positions like 0.123 and 0.234 aren't merged.
        $data->highlights[$position][$highlight->id]['highlight'] = $highlight;

        foreach ($comments as $comment) {
          $timestamp = strtotime($comment->posted_at);

          // Create a permalink URL that persists after our highlight merging.
          // (Some values become inaccessible when things are merged together.)
          $comment->permalink_url = 'http://readmill.com/' . $highlight->user->username
            . '/reads/' . $data->book->permalink . '/highlights/' . $highlight->permalink;

          // Store the comment keyed to timestamp for re-sorting after highlight merge.
          $data->highlights[$position][$highlight->id]['comments'][$timestamp] = $comment;
        }

        // Duplicate our highlight storage, but by highlight length, for potential merging below.
        $highlights_to_merge[strlen($highlight->content) . '.' . rand(0,999)] = $data->highlights[$position][$highlight->id];
      }
    }
  }
}

// Sort by position.
ksort($data->highlights);

// Any user can highlight, and comment upon, the same bit of text, but they
// aren't merged together into a single entry inside Readmill. We'll try to
// do so here, looking for highlights that fit inside other highlights and
// merging the comments together for a more concise display.
ksort($highlights_to_merge); // Sort by smallest highlight.
while (count($highlights_to_merge) != 0) {
  $smallest_highlight = array_shift($highlights_to_merge);

  foreach ($highlights_to_merge as $length => $highlight) {
    // Highlighting the final bit of punctuation in a paragraph can sometimes
    // be difficult. If one person bothers to select it in a smaller highlight,
    // but another person doesn't in a larger highlight, we'd miss a proper
    // merge. To get around this, we shorten the smallest highlight string by
    // 1, hopefully removing any dastardly punctuation that might cause a miss.
    // Some examples of this include
    //   https://readmill.com/Tobystereo/reads/html5-for-web-designers/highlights/0uxg9q
    //   https://readmill.com/challberg/reads/html5-for-web-designers/highlights/6fd8
    //   https://readmill.com/vasilis/reads/html5-for-web-designers/highlights/117d
    //   https://readmill.com/asia/reads/html5-for-web-designers/highlights/2rasra
    $smallest_highlight_string = substr_replace($smallest_highlight['highlight']->content, '', -1);

    if (strpos($highlight['highlight']->content, $smallest_highlight_string) !== FALSE) {
      // A match!: We've a smaller highlight that is a snippet of a bigger one.
      // Merge the smaller's comments into the bigger and re-sort by timestamp.
      $bigger_position = (string) $highlight['highlight']->locators->position;
      $data->highlights[$bigger_position][$highlight['highlight']->id]['comments'] += $smallest_highlight['comments'];
      ksort($data->highlights[$bigger_position][$highlight['highlight']->id]['comments']);

      // Remove the smaller highlight as the bigger highlight has now eaten its comments.
      $smallest_position = (string) $smallest_highlight['highlight']->locators->position;
      unset($data->highlights[$smallest_position][$smallest_highlight['highlight']->id]);

      // All highlights for this position are merged. Remove the position.
      if (isset($data->highlights[$smallest_position]) && count($data->highlights[$smallest_position]) == 0) {
        unset($data->highlights[$smallest_position]);
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Book comments for <?php print $data->book->title; ?> - Readmilling</title>
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
          <h1>Comments</h1>
          <?php
            foreach ($data->highlights as $position => $highlights) {
              foreach ($highlights as $highlight_id => $highlight) {
                print '<article class="highlight">';
                print   '<blockquote>' . htmlentities($highlight['highlight']->content, ENT_COMPAT, "UTF-8") . '</blockquote>';
                print   '<section class="comments">'; // Be sure to enforce UTF-8 on all htmlentities() due to PHP 5.3 defaults.
                foreach ($highlight['comments'] as $timestamp => $comment) {
                  print   '<article class="comment">';
                  print     '<a href="' . $comment->user->permalink_url . '" class="image">';
                  print       '<img src="' . $comment->user->avatar_url . '" alt="' . $comment->user->fullname . '" />';
                  print     '</a>';
                  print     '<div class="content">'; // Display the comment as one single paragraph, even if it has lovable newlines of its own.
                  print       '<a href="' . $comment->user->permalink_url . '" class="fullname">' . $comment->user->fullname . '</a> ';
                  print       '<p>' . htmlentities($comment->content, ENT_COMPAT, "UTF-8") . '</p>';
                  print       '<aside class="metadata">';
                  print         '<time class="timestamp" datetime="' . date(DATE_ISO8601, $timestamp) . '">' . date('D, d M Y h:i:s a', $timestamp) . '</time> &middot; ';
                  print         '<a href="' . $comment->permalink_url . '">Reply to comment</a>';
                  print       '</aside>';
                  print     '</div>';
                  print   '</article>';
                }
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
          <a href="<?php print $data->book->permalink_url; ?>"><img class="book-cover" alt="Book cover for <?php print $data->book->title; ?>" src="<?php print str_replace('medium', 'original', $data->book->cover_url); ?>" /></a>
        </section>
        <section class="secondary-section">
          <form action="book-comments.php" accept-charset="UTF-8" method="get">
            <label for="form-title">Book title</label><input id="form-title" name="title" type="text" placeholder="<?php print htmlentities($match_title, ENT_COMPAT, "UTF-8"); ?>" required />
            <label for="form-author">Book author</label><input id="form-author" name="author" type="text" placeholder="<?php print htmlentities($match_author, ENT_COMPAT, "UTF-8"); ?>" required />
            <button>Load book</button>
          </form>
          <span class="warning"><strong>Be aware:</strong> If we've not seen this book before, or its
          data has expired, it might take 30 seconds or more before you'll get results.</span>
        </section>
        <section class="secondary-section">
          <h1>About this script</h1>
          <p><b>Book comments</b> tries to merge all comments left on similar highlights into a single
          entry. Different versions of the same book can have highlights in different locations, so the
          script can't guarantee the merged entries are in the same order they appear in the text.</p>
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
