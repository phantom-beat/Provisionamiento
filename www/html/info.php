<?php
$DB_HOST='192.168.56.11';
$DB_NAME='taller';
$DB_USER='webuser';
$DB_PASS='webpass';
$DB_PORT='5432';
$conn_string = sprintf("host=%s port=%s dbname=%s user=%s password=%s connect_timeout=5",$DB_HOST,$DB_PORT,$DB_NAME,$DB_USER,$DB_PASS);
$pgconn = @pg_connect($conn_string);
$users = [];
$connect_error = null;
if (!$pgconn) {
  $connect_error = "Unable to connect to the database at " . htmlspecialchars($DB_HOST) . ".";
} else {
  $res = @pg_query_params($pgconn, "SELECT id, nombre FROM public.usuarios ORDER BY id ASC", []);
  if ($res) {
    while ($row = pg_fetch_assoc($res)) {
      $users[] = $row;
    }
    pg_free_result($res);
  } else {
    $connect_error = "Query failed: " . htmlspecialchars(pg_last_error($pgconn));
  }
}
ob_start();
phpinfo();
$phpinfo = ob_get_clean();
$phpinfo = preg_replace('#^.*<body[^>]*>#si', '', $phpinfo);
$phpinfo = preg_replace('#</body>.*$#si', '', $phpinfo);
$phpinfo = preg_replace('#<style[^>]*>.*?</style>#si', '', $phpinfo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PHANTOM_BEAT • INFO</title>
  <style>
    :root{
      --bg:#000;
      --green:#00ff00;
      --muted:#6f9f6f;
      --panel-bg: rgba(0,0,0,0.85);
      --mono: 'Courier New', monospace;
    }
    body{
      margin:0;
      min-height:100vh;
      background: linear-gradient(180deg, #020202 0%, #0b0b0b 60%);
      color:var(--green);
      font-family:var(--mono);
      display:flex;
      align-items:flex-start;
      justify-content:center;
      padding:36px;
    }
    .wrap{
      width:92%;
      max-width:1100px;
    }
    .header{
      border:2px solid var(--green);
      padding:18px 22px;
      border-radius:8px;
      background:var(--panel-bg);
      box-shadow: 0 0 28px rgba(0,255,0,0.08);
      margin-bottom:18px;
    }
    .header h1{
      margin:0;
      font-size:1.6rem;
      letter-spacing:1px;
    }
    .sub{
      color:var(--muted);
      margin-top:6px;
      font-size:0.95rem;
    }
    .phpbox{
      border:1px solid rgba(0,255,0,0.12);
      border-radius:6px;
      overflow:auto;
      max-height:62vh;
      padding:18px;
      background: linear-gradient(180deg, rgba(0,0,0,0.6), rgba(0,0,0,0.72));
      box-shadow: inset 0 0 18px rgba(0,255,0,0.02);
    }
    table.users { width:100%; border-collapse:collapse; margin-bottom:12px; }
    table.users th, table.users td { padding:10px 8px; border-top:1px solid rgba(0,255,0,0.03); text-align:left; }
    table.users th { background: rgba(0,255,0,0.04); color:#dfffdc; }
    .error { color:#ff6b6b; background: rgba(255,0,0,0.04); padding:10px; border-radius:6px; margin-bottom:12px; }
    .muted { color:var(--muted); }
    .links { margin-top:12px; display:flex; gap:10px; }
    .btn {
      color: #000;
      background: var(--green);
      padding:8px 14px;
      border-radius:6px;
      text-decoration:none;
      font-weight:bold;
    }
    details { margin-top:12px; border-top:1px dashed rgba(0,255,0,0.06); padding-top:12px; }
    summary { cursor:pointer; font-weight:bold; color:var(--muted); }
    @media (max-width:720px){
      .phpbox { max-height:50vh; padding:12px; }
      .header h1{ font-size:1.2rem; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <h1>SYSTEM BREACHED BY PHANTOM_BEAT</h1>
      <div class="sub">ACCESS GRANTED — PHP &amp; DB PANEL</div>
      <div class="links" style="margin-top:10px;">
        <a class="btn" href="index.html">◀ BACK</a>
      </div>
    </div>

    <div class="phpbox">
      <?php if ($connect_error): ?>
        <div class="error">ERROR: <?php echo $connect_error; ?></div>
        <div class="muted">Check DB_HOST, network and PostgreSQL service. Example connection string used: <code><?php echo htmlspecialchars($conn_string); ?></code></div>
      <?php else: ?>
        <div style="margin-bottom:10px;">
          <strong>Connected to:</strong>
          <span class="muted"><?php echo htmlspecialchars($DB_NAME); ?>@<?php echo htmlspecialchars($DB_HOST); ?></span>
        </div>

        <h3 style="margin:8px 0 6px 0;">Usuarios (sample)</h3>

        <?php if (count($users) === 0): ?>
          <div class="muted">No users found in <code>public.usuarios</code>.</div>
        <?php else: ?>
          <table class="users" role="table">
            <thead>
              <tr><th>ID</th><th>Nombre</th></tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?php echo htmlspecialchars($u['id']); ?></td>
                  <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      <?php endif; ?>

      <details>
        <summary>Show phpinfo() output</summary>
        <div style="margin-top:10px;">
          <?php echo $phpinfo; ?>
        </div>
      </details>
    </div>
  </div>
</body>
</html>
