<!DOCTYPE html>
<html lang="ko">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>공간 초대</title>
  </head>
  <body style="margin: 0; padding: 0; background-color: #f3f5f7; color: #111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f5f7; padding: 32px 16px;">
      <tr>
        <td align="center">
          <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 600px; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 24px rgba(17, 24, 39, 0.08);">
            <tr>
              <td style="padding: 28px 32px 8px; background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 70%);">
                <div style="font-size: 12px; letter-spacing: 0.12em; color: #6b7280; font-weight: 700;">SPACE INVITATION</div>
                <h1 style="margin: 10px 0 0; font-size: 24px; line-height: 1.35; font-weight: 700; color: #111827;">
                  {{ $inviterName }}님이 당신을<br />
                  <span style="color: #2563eb;">{{ $spaceName }}</span> 공간으로 초대했습니다.
                </h1>
              </td>
            </tr>
            <tr>
              <td style="padding: 20px 32px 0;">
                <p style="margin: 0; font-size: 15px; line-height: 1.7; color: #374151;">
                  아래 버튼을 눌러 초대를 수락하고 공간에 참여하세요.
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding: 22px 32px 8px;">
                <a href="{{ $url }}"
                   style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px;">
                  초대 수락하기
                </a>
              </td>
            </tr>
            <tr>
              <td style="padding: 6px 32px 24px;">
                <p style="margin: 0; font-size: 13px; color: #6b7280;">
                  이 링크는 3일 동안 유효합니다.
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding: 0 32px 24px;">
                <div style="height: 1px; background: #e5e7eb;"></div>
              </td>
            </tr>
            <tr>
              <td style="padding: 0 32px 28px;">
                <p style="margin: 0 0 10px; font-size: 12px; color: #9ca3af;">
                  버튼이 작동하지 않으면 아래 링크를 복사해 브라우저에 붙여넣으세요.
                </p>
                <p style="margin: 0; font-size: 12px; color: #4b5563; word-break: break-all;">
                  {{ $url }}
                </p>
              </td>
            </tr>
          </table>
          <p style="margin: 18px 0 0; font-size: 12px; color: #9ca3af;">
            본 메일은 발신 전용입니다.
          </p>
        </td>
      </tr>
    </table>
  </body>
</html>

