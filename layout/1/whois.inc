<?php
/**
 * Whois.
 */

require_once($_base . 'lib/Stats.php');

$start = (isset($_GET['u']) ? $_GET['u'][0] : 'a');
$users = Planworld::getAllUsersWithPlans($start);
$num_users = Stats::getNumPlans();

/* assemble a 4 column listing of people (specify as $cols) */
$cols = 4;
$userdata = '';
$i = 0;
while ($i < sizeof($users)) {
  $userdata .= "<tr class=\"entry\">\n";
  for ($j=0; $j<$cols; $j++) {
    if (!isset($users[$i])) {
      /* pad out the remainder with blank cells */
      $userdata .= "<td align=\"center\" width=\"25%\">&nbsp;</td>\n";
    } else {
      $userdata .= "<td align=\"center\" width=\"25%\"><a href=\"" . PW_URL_INDEX . "?id={$users[$i]}\" title=\"Finger {$users[$i]}\">{$users[$i++]}</a></td>\n";
    }
  }
  $userdata .= "</tr>\n";
}

?>
<span class="subtitle">Whois (<?php echo number_format($num_users); ?> users with plans)</span><br /><br />
<div align="center">
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=a">A</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=b">B</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=c">C</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=d">D</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=e">E</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=f">F</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=g">G</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=h">H</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=i">I</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=j">J</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=k">K</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=l">L</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=m">M</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=n">N</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=o">O</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=p">P</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=q">Q</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=r">R</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=s">S</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=t">T</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=u">U</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=v">V</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=w">W</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=x">X</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=y">Y</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=z">Z</a> |
<a href="<?php echo PW_URL_INDEX; ?>?id=whois&amp;u=%23">#</a>
</div>
<hr width="60%" noshade="noshade" align="center" />
<div style="float: left; margin-right: 10px;">
<?php
$i = 0;
$total = sizeof($users);
foreach ($users as $u) {
  if ($i > ($total / 5)) { ?>
</div>
<div style="float: left; margin-right: 10px; margin-left: 10px;">
<?php  $i = 0;
  } // if ($i % ($total / 5) == 0) ?>
<a href="<?php echo PW_URL_INDEX; ?>?id=<?php echo $u; ?>" title="Finger <?php echo $u; ?>"><?php echo $u; ?></a><br />
<?php $i++;
} // foreach ($users as $u) ?>
</div>
