<?php include("head.php") ?>
<div class="section">
<?php include("cred.php") ?>
<?php include("history-atop.php") ?>
<?php $sql = "SELECT id, DATE_FORMAT(timestamp, '%y/%m/%d %H:%i.%s') as TimeStamp, ipaddress, ban_reason, countrycode, country, flag FROM hm_fwban ORDER BY countrycode DESC LIMIT $offset, $no_of_records_per_page"; ?>
<?php include("history-abottom.php") ?>
</div>
<?php include("foot.php") ?>