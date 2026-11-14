<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:24px;background:#f2f4f7;font-family:Arial,Helvetica,sans-serif;color:#1d2939;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e3e8ef;">
    <tr>
      <td style="background:#3d6b20;color:#ffffff;padding:18px 24px;font-size:18px;font-weight:bold;"><?php echo html_escape($titulo); ?> · dat@Resto Datamaule</td>
    </tr>
    <tr>
      <td style="padding:24px;">
        <p style="margin:0 0 16px;font-size:15px;line-height:1.5;"><?php echo html_escape($texto); ?></p>
        <div style="margin:0 0 18px;padding:16px;text-align:center;font-size:30px;letter-spacing:8px;font-weight:bold;background:#f2f4f7;border-radius:10px;color:#3d6b20;"><?php echo html_escape($codigo); ?></div>
        <p style="margin:0 0 8px;font-size:13px;color:#475467;">El código vence en <?php echo (int) $minutos; ?> minutos. No lo compartas con nadie.</p>
        <p style="margin:0;font-size:13px;color:#475467;">Si no fuiste tú, ignora este correo.</p>
      </td>
    </tr>
  </table>
</body>
</html>
