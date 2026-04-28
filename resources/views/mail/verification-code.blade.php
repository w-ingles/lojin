<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f0f2f5;font-family:Arial,sans-serif">
  <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
    <div style="background:#1976d2;padding:28px 32px">
      <h1 style="margin:0;color:#fff;font-size:1.4rem">⚡ Lojin</h1>
    </div>
    <div style="padding:32px">
      <h2 style="margin-top:0;color:#1a1a1a">Confirme seu e-mail</h2>
      <p style="color:#555;margin-bottom:24px">Use o código abaixo para concluir seu cadastro. Ele é válido por <strong>10 minutos</strong>.</p>
      <div style="background:#f0f7ff;border:2px solid #1976d2;border-radius:8px;padding:20px;text-align:center;margin-bottom:24px">
        <span style="font-size:2.8rem;font-weight:800;letter-spacing:10px;color:#1976d2">{{ $code }}</span>
      </div>
      <p style="color:#888;font-size:.85rem;margin-bottom:0">Se você não solicitou este código, ignore este e-mail. Nenhuma ação é necessária.</p>
    </div>
    <div style="background:#f8f8f8;padding:16px 32px;border-top:1px solid #eee">
      <p style="margin:0;color:#aaa;font-size:.75rem;text-align:center">© {{ date('Y') }} Lojin · Plataforma de Atléticas Universitárias</p>
    </div>
  </div>
</body>
</html>