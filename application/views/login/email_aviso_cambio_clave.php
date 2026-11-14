<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:24px;background:#f2f4f7;font-family:Arial,Helvetica,sans-serif;color:#1d2939;">
  <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e3e8ef;">
    <tr>
      <td style="background:#3d6b20;color:#ffffff;padding:18px 24px;font-size:18px;font-weight:bold;">Contraseña actualizada · dat@Resto Datamaule</td>
    </tr>
    <tr>
      <td style="padding:24px;">
        <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">La contraseña de tu cuenta de dat@Resto <strong><?php echo html_escape($correo); ?></strong> se cambió correctamente.</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;background:#f2f4f7;border-radius:10px;font-size:14px;">
          <tr>
            <td style="padding:10px 14px 4px;color:#475467;width:130px;">Fecha</td>
            <td style="padding:10px 14px 4px;font-weight:bold;"><?php echo html_escape($fecha); ?> a las <?php echo html_escape($hora); ?> (hora de Chile)</td>
          </tr>
          <tr>
            <td style="padding:4px 14px;color:#475467;">Cómo se hizo</td>
            <td style="padding:4px 14px;"><?php echo html_escape($medio); ?></td>
          </tr>
          <tr>
            <td style="padding:4px 14px;color:#475467;">Dirección IP</td>
            <td style="padding:4px 14px;"><?php echo html_escape($ip); ?></td>
          </tr>
          <tr>
            <td style="padding:4px 14px 10px;color:#475467;">Navegador</td>
            <td style="padding:4px 14px 10px;"><?php echo html_escape($navegador); ?></td>
          </tr>
        </table>
        <p style="margin:0 0 8px;font-size:14px;line-height:1.5;">Si fuiste tú, no tienes que hacer nada más. Ya puedes iniciar sesión con tu nueva contraseña.</p>
        <div style="margin:16px 0 18px;padding:14px 16px;background:#fef3f2;border:1px solid #fecdca;border-radius:10px;font-size:14px;line-height:1.5;color:#912018;">
          <strong>¿No fuiste tú?</strong> Entra a dat@Resto, usa <strong>«¿Olvidaste tu contraseña?»</strong> para crear una nueva de inmediato y avísanos a Datamaule (datamaule@gmail.com).
        </div>
        <?php if (!empty($url_login)) : ?>
        <p style="margin:0 0 18px;text-align:center;">
          <a href="<?php echo html_escape($url_login); ?>" style="display:inline-block;padding:12px 26px;background:#3d6b20;color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;">Ir a dat@Resto</a>
        </p>
        <?php endif; ?>
        <p style="margin:0;font-size:12px;color:#667085;">Este es un aviso automático de seguridad; no respondas este correo.</p>
      </td>
    </tr>
  </table>
</body>
</html>
