<?php

/**
 * @file
 * @todo
 */

include_once("../includes/common.inc");

$data = new stdClass;
$data->book = readmill_book('match', array(
  'title'   => 'HTML5 for Web Designers',
  'author'  => 'Jeremy Keith',
));
if (!$data->book) {
  // @todo
}

// Fetch all the readings for this book. We're interested in readings
// with highlights (and comments), but we also want closing_remarks.
$data->readings = readmill_book_readings($data->book->id);

// For every reading, fetch all the highlights and then any comments that
// have been left on those highlights. This is a comment-centric view -- if
// a highlight has no comments, we're not interested.
foreach ($data->readings as $reading) {
  if ($reading->highlights_count >= 1) {
    $highlights = readmill_reading_highlights($reading->id);

    foreach ($highlights as $highlight) {
      if ($highlight->comments_count >= 1) {
        $comments = readmill_highlight_comments($highlight->id);

        // Store the highlight indexed at the highlight position. This allows us
        // to do a quick key sort to put all highlights in the order they were
        // positioned in the book (approximately, given multiple book formats).
        $position = (string) $highlight->locators->position;
        $data->highlights[$position][$highlight->id]['highlight'] = $highlight;

        foreach ($comments as $comment) {
          $timestamp = strtotime($comment->posted_at);
          $data->highlights[$position][$highlight->id]['comments'][$timestamp] = $comment;
        }
      }
    }
  }
}

ksort($data->highlights);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Readmilling: Book comments</title>
  <link rel="stylesheet" href="../misc/default.css" />
</head>
<body>
  <header class="page-toolbar toolbar">
    <div class="container">
      <a href="/" class="readmill-logo">Readmill</a>
      <div class="readmilling-explanation">
        <a href="http://www.disobey.com/d/code/readmilling/">Readmilling</a> is
        a collection of scripts for use with <a href="http://readmill.com/">Readmill's</a>
        <a href="http://developers.readmill.com/">API</a>. Created by <a href="http://www.disobey.com/">Morbus Iff</a>.
      </div>
    </div>
  </header>

  <div class="page-content container" role="main">
    <section class="layout-primary">
      <div class="layout-primary-column">
        <header>
          <h1>
            <span class="header-group-primary"><?php print $data->book->title; ?></span>
            <span class="header-group-secondary"><?php print $data->book->author; ?></span>
          </h1>
        </header>
        <section id="highlights">
          <h2>Comments</h2>
          <?php
            foreach ($data->highlights as $position => $highlights) {
              foreach ($highlights as $highlight_id => $highlight) {
                print '<article class="highlight">';
                print   '<blockquote>' . htmlentities($highlight['highlight']->content) . '</blockquote>';
                print   '<section class="comments">';
                foreach ($highlight['comments'] as $timestamp => $comment) {
                  print   '<article class="comment">';
                  print     '<a href="' . $comment->user->permalink_url . '" class="image"><img src="' . $comment->user->avatar_url . '" /></a>';
                  print     '<div class="content">'; // Display the comment as one big paragraph, even if it has newlines of its own.
                  print       '<a href="' . $comment->user->permalink_url . '" class="fullname">' . $comment->user->fullname . '</a> ';
                  print       '<p>' . $comment->content . '</p>';
                  print       '<aside class="metadata">';
                  print         '<time class="timestamp" datetime="' . date(DATE_ISO8601, $timestamp) . '">' . date('D, d M Y', $timestamp) . '</time> &middot; ';
                  print         '<a href="http://readmill.com/' . $comment->user->username . '/reads/' . $data->book->permalink . '/highlights/' . $highlight['highlight']->permalink . '">Reply to comment</a>';
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
inside secondary column
    </aside>
  </div>

finish footer here
</body>
</html>
