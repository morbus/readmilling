<?php

/**
 * @file
 * Export a Readmill user's data.
 */

include_once "../includes/common.inc";

disable_output_buffering();

$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';

// Retrieve, store, package.
function start_export($username) {
  $data = new stdClass;

  if (!$data->user = readmill_user_search($username)) {
    print 'The requested user could not be found.';
    return;
  }

  // Place everything here for packaging.
  $export_dir = '../exports/' . $username;
  @mkdir($export_dir, 0755, TRUE);

  // Load all the user's readings, highlights, and comments.
  $data->readings = readmill_user_readings($data->user->id);
  foreach ($data->readings as $rid => $reading) {
    print heartbeat();

    if ($reading->highlights_count >= 1) {
      $data->readings->$rid->highlights = readmill_reading_highlights($reading->id);

      foreach ($data->readings->$rid->highlights as $hid => $highlight) {
        print heartbeat();

        if ($highlight->comments_count >= 1) {
          $data->readings->$rid->highlights->$hid->comments = readmill_highlight_comments($highlight->id);
        }
      }
    }
  }

  // For ease of parsing by developers, a giant dump of everything combined.
  file_put_contents($export_dir . '/user-data.json', json_encode($data), LOCK_EX);

  print "<p>Your user data is available: <a href=\"$export_dir/user_data.json\">$export_dir/user_data.json</a>.</p>";
}

/**
 * Keep-alive ping.
 */
function heartbeat() {
  return '<span class="heartbeat">.</span> .';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>User data export - Readmilling</title>
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
    <?php if (!empty($username)) { ?>
    <section id="long-running-results">
      <h1>Progress report</h1>
      <?php start_export($username); ?>
    </section>
    <?php } ?>

    <section class="layout-primary">
      <div class="layout-primary-column">
        <header>
          <h1>
            <span class="header-group-primary">User data export</span>
            <span class="header-group-secondary">You put it in, now get it out.</span>
          </h1>
        </header>
        <section id="user-export-form">
          <form action="user-export.php" accept-charset="UTF-8" method="get">
            <label for="form-title">Username</label><input id="form-title" name="username" type="text" placeholder="<?php print htmlentities($username, ENT_COMPAT, "UTF-8"); ?>" required />
            <button>Export user data</button> <!-- NP: 'Let's Go Away (Daytona USA)' from Various Artists's album 'Segacon: The Best Of Sega Game Music Vol. 1' -->
          </form>
          <span class="warning"><strong>Be aware:</strong> If we've not seen this user before,
          or its data has expired, it might take a few minutes before you'll get results.</span>
        </section>
      </div>
    </section>

    <aside class="layout-secondary">
      <div class="layout-secondary-column">
        <section class="secondary-section">
          <h1>About this script</h1>
          <p>@todo</p>
        </section>
        <section class="secondary-section">
          <h1>Morbus Iff</h1>
          <p>Found bugs or have a request? Like it and want to give thanks?</p>
          <ul>
            <li><a href="https://twitter.com/morbusiff">@morbusiff</a></li>
            <li>E-mail and PayPal: <a href="mailto:morbus@disobey.com">morbus@disobey.com</a></li>
            <li><a href="http://www.amazon.com/gp/registry/25USVJDH68554">Amazon wishlist</a></li>
            <li><a href="http://www.disobey.com/">Disobey.com</a></li>
          </ul>
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
